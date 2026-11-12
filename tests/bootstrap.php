<?php

/**
 * Stand-ins for the few SilverStripe, frontend-fields and Fluent classes DataObjectPageController touches, so its
 * functions can be unit tested on plain PHP: no framework, no database, nothing installed.
 *
 * Each stub does only what the tests need: it returns the values the tests give it and records what it was asked.
 */

namespace SilverStripe\Control {

    class HTTPResponse_Exception extends \Exception {

    }

    class HTTPRequest {

        private $getVars;
        private $postVars;
        private $params = [];
        private $ajax = false;

        public function __construct($method = 'GET', $url = '', $getVars = [], $postVars = []) {
            $this->getVars = $getVars;
            $this->postVars = $postVars;
        }

        public function getVar($name) {
            return $this->getVars[$name] ?? null;
        }

        public function getVars() {
            return $this->getVars;
        }

        public function postVar($name) {
            return $this->postVars[$name] ?? null;
        }

        public function param($name) {
            return $this->params[$name] ?? null;
        }

        public function setRouteParams($params) {
            $this->params = $params;
            return $this;
        }

        public function setAjax($ajax) {
            $this->ajax = $ajax;
            return $this;
        }

        public function isAjax() {
            return $this->ajax;
        }

    }

    class Controller {

        public static function join_links(...$parts) {
            $parts = array_filter(array_map('strval', $parts), 'strlen');
            return preg_replace('#/{2,}#', '/', implode('/', $parts));
        }

    }

    class Director {

        public static function absoluteURL($url) {
            return 'https://example.test/' . ltrim((string) $url, '/');
        }

    }

}

namespace SilverStripe\ORM {

    class ArrayList implements \IteratorAggregate, \Countable {

        protected $items;

        public function __construct(array $items = []) {
            $this->items = array_values($items);
        }

        public static function create(...$args) {
            return new static(...$args);
        }

        public function getIterator(): \Iterator {
            return new \ArrayIterator($this->items);
        }

        public function count(): int {
            return count($this->items);
        }

        public function toArray() {
            return $this->items;
        }

        public function push($item) {
            $this->items[] = $item;
        }

        public function first() {
            return $this->items[0] ?? null;
        }

        public function byID($id) {
            return $this->filter(['ID' => $id])->first();
        }

        /**
         * Field => value matching, plus the "Field:PartialMatch" modifier searchObjects() uses
         */
        public function filter($filters) {
            return new static(array_filter($this->items, function ($item) use ($filters) {
                foreach ($filters as $key => $value) {
                    [$field, $modifier] = array_pad(explode(':', $key, 2), 2, null);
                    $actual = $item->$field;

                    if ($modifier === 'PartialMatch') {
                        if (stripos((string) $actual, (string) $value) === false) {
                            return false;
                        }
                    } else if ($actual != $value) {
                        return false;
                    }
                }

                return true;
            }));
        }

        public function filterByCallback($callback) {
            $kept = [];
            foreach ($this->items as $item) {
                if ($callback($item, $this)) {
                    $kept[] = $item;
                }
            }

            return new ArrayList($kept);
        }

    }

    /**
     * A database-backed list: counting and limiting happen in SQL, and only iterating loads (and builds) records.
     * $log counts the queries and loaded rows; lists derived from this one share it.
     */
    class DataList implements \IteratorAggregate, \Countable {

        public $log;
        private $dataClass;
        private $items;

        /**
         * ORDER BY column => direction; it is applied only to a list made with sortedBy()
         */
        public $orderBy = [];
        private $sorted = false;

        public function __construct($dataClass, array $items = [], $log = null) {
            $this->dataClass = $dataClass;
            $this->items = array_values($items);
            $this->log = $log ?? (object) ['counts' => 0, 'selects' => 0, 'rowsLoaded' => 0];
        }

        /**
         * Test helper: this list ordered by $orderBy as MySQL would, so rows that tie on every sort column come back in
         * a different order for each LIMIT offset unless a later column breaks the tie
         */
        public function sortedBy(array $orderBy) {
            $list = $this->derive($this->items);
            $list->orderBy = $orderBy;
            $list->sorted = true;
            return $list;
        }

        public function alterDataQuery($callback) {
            $list = $this->derive($this->items);
            $callback(new DataQuery($list), $list);
            return $list;
        }

        private function ordered($offset) {
            if (!$this->sorted) {
                return $this->items;
            }

            $items = $this->items;
            usort($items, function ($a, $b) use ($offset) {
                foreach ($this->orderBy as $column => $direction) {
                    $field = trim(substr($column, strrpos('.' . $column, '.')), '"`');
                    $order = $a->$field <=> $b->$field;
                    if ($order) {
                        return strtoupper($direction) === 'DESC' ? -$order : $order;
                    }
                }

                return crc32("$offset:{$a->ID}") <=> crc32("$offset:{$b->ID}");
            });

            return $items;
        }

        public function dataClass() {
            return $this->dataClass;
        }

        /**
         * Test helper, not on the framework's DataList: the records, without counting a query
         */
        public function records() {
            return $this->items;
        }

        private function derive(array $items) {
            $list = new static($this->dataClass, $items, $this->log);
            $list->orderBy = $this->orderBy;
            $list->sorted = $this->sorted;
            return $list;
        }

        public function getIterator(): \Iterator {
            $this->log->selects++;
            $this->log->rowsLoaded += count($this->items);
            return new \ArrayIterator($this->ordered(0));
        }

        public function count(): int {
            $this->log->counts++;
            return count($this->items);
        }

        public function exists() {
            return $this->count() > 0;
        }

        public function first() {
            foreach ($this->limit(1) as $item) {
                return $item;
            }

            return null;
        }

        public function limit($length, $offset = 0) {
            $page = $this->derive(array_slice($this->ordered($offset), $offset, $length));
            $page->sorted = false;
            return $page;
        }

        public function filter($filters) {
            return $this->derive((new ArrayList($this->items))->filter($filters)->toArray());
        }

        /**
         * As on the framework's DataList: loads every row, then keeps what $callback accepts in an ArrayList
         */
        public function filterByCallback($callback) {
            $kept = [];
            foreach ($this as $item) {
                if ($callback($item, $this)) {
                    $kept[] = $item;
                }
            }

            return new ArrayList($kept);
        }

    }

    /**
     * The part of the framework's DataQuery that changes the ORDER BY of the list it belongs to
     */
    class DataQuery {

        private $list;

        public function __construct(DataList $list) {
            $this->list = $list;
        }

        public function dataClass() {
            return $this->list->dataClass();
        }

        public function query() {
            $list = $this->list;
            return new class($list) {
                private $list;

                public function __construct($list) {
                    $this->list = $list;
                }

                public function getOrderBy() {
                    return $this->list->orderBy;
                }
            };
        }

        public function sort($sort = null, $direction = null, $clear = true) {
            if ($clear) {
                $this->list->orderBy = [];
            }
            $this->list->orderBy[$sort] = strtoupper($direction ?? 'ASC');
            return $this;
        }

    }

    /**
     * Same paging rules as the framework's: the start comes from the request's pagination variable when it's above
     * zero, and only a page of the list is iterated unless limitItems is off
     */
    class PaginatedList implements \IteratorAggregate, \Countable {

        private $list;
        private $request;
        private $pageLength = 10;
        private $pageStart;
        private $totalItems;
        private $limitItems = true;
        private $getVar = 'start';

        public function __construct($list, $request = null) {
            $this->list = $list;
            $this->request = $request;
        }

        public static function create(...$args) {
            return new static(...$args);
        }

        public function getList() {
            return $this->list;
        }

        public function setPageLength($length) {
            $this->pageLength = (int) $length;
            return $this;
        }

        public function getPageLength() {
            return $this->pageLength;
        }

        public function setPaginationGetVar($var) {
            $this->getVar = $var;
            return $this;
        }

        public function getPaginationGetVar() {
            return $this->getVar;
        }

        public function setPageStart($start) {
            $this->pageStart = (int) $start;
            return $this;
        }

        public function getPageStart() {
            if ($this->pageStart === null) {
                $start = $this->request ? $this->request->getVar($this->getVar) : null;
                $this->pageStart = $start !== null && $start > 0 ? (int) $start : 0;
            }

            return $this->pageStart;
        }

        public function setTotalItems($total) {
            $this->totalItems = (int) $total;
            return $this;
        }

        public function getTotalItems() {
            if ($this->totalItems === null) {
                $this->totalItems = count($this->list);
            }

            return $this->totalItems;
        }

        public function setLimitItems($limit) {
            $this->limitItems = (bool) $limit;
            return $this;
        }

        public function getLimitItems() {
            return $this->limitItems;
        }

        public function getIterator(): \Iterator {
            // The framework limits a cloned list; a DataList turns that into an SQL LIMIT
            if ($this->list instanceof DataList && $this->limitItems && $this->pageLength) {
                return $this->list->limit($this->pageLength, $this->getPageStart())->getIterator();
            }

            $items = [];
            foreach ($this->list as $item) {
                $items[] = $item;
            }

            if ($this->limitItems && $this->pageLength) {
                $items = array_slice($items, $this->getPageStart(), $this->pageLength);
            }

            return new \ArrayIterator($items);
        }

        /// As on the framework's ListDecorator: these describe the whole list, not the current page ///
        public function count(): int {
            return count($this->list);
        }

        public function exists() {
            return count($this->list) > 0;
        }

        public function first() {
            return $this->list->first();
        }

        public function filterByCallback($callback) {
            $kept = [];
            foreach ($this->list as $item) {
                if ($callback($item, $this->list)) {
                    $kept[] = $item;
                }
            }

            return new ArrayList($kept);
        }

    }

    class DataObject {

        /**
         * Class name => list returned by DataObject::get()
         */
        public static $lists = [];

        public static function get($class) {
            return self::$lists[$class] ?? new ArrayList();
        }

        public static function getSchema() {
            return new class {
                /**
                 * The table of the class's base data class; here, the class's short name
                 */
                public function baseDataTable($class) {
                    return substr(strrchr('\\' . $class, '\\'), 1);
                }
            };
        }

    }

}

namespace SilverStripe\ORM\FieldType {

    class DBField {

        public $type;
        public $value;

        public static function create_field($type, $value) {
            $field = new static();
            $field->type = $type;
            $field->value = $value;
            return $field;
        }

    }

}

namespace SilverStripe\View {

    class ArrayData {

        private $map;

        public function __construct($map = []) {
            $this->map = $map;
        }

        public static function create(...$args) {
            return new static(...$args);
        }

        public function toMap() {
            return $this->map;
        }

    }

    class Requirements {

        public static function __callStatic($name, $args) {

        }

    }

    class ThemeResourceLoader {

    }

}

namespace SilverStripe\Security {

    class Security {

        public static function permissionFailure($controller = null) {
            return 'PERMISSION_FAILURE';
        }

    }

}

namespace SilverStripe\i18n {

    class i18n {

        public static $locale = 'en_US';

        public static function get_locale() {
            return self::$locale;
        }

        public static function get_script_direction($locale) {
            return strpos($locale, 'ar') === 0 ? 'rtl' : 'ltr';
        }

    }

}

namespace SilverStripe\Core {

    class Convert {

    }

}

namespace SilverStripe\Forms {

    class FormField {

        public $name;
        public $title;
        public $value;
        public $extraClasses = [];

        public function __construct($name = null, $title = null, $value = null) {
            $this->name = $name;
            $this->title = $title;
            $this->value = $value;
        }

        public static function create(...$args) {
            return new static(...$args);
        }

        public function getName() {
            return $this->name;
        }

        public function setTitle($title) {
            $this->title = $title;
            return $this;
        }

        public function setValue($value) {
            $this->value = $value;
            return $this;
        }

        public function addExtraClass($class) {
            $this->extraClasses[] = $class;
            return $this;
        }

    }

    class TextField extends FormField {

    }

    class HiddenField extends FormField {

    }

    class DateField extends FormField {

    }

    class FormAction extends FormField {

    }

    class FieldList implements \IteratorAggregate, \Countable {

        private $fields = [];

        public function __construct(...$fields) {
            $this->fields = isset($fields[0]) && is_array($fields[0]) ? array_values($fields[0]) : $fields;
        }

        public static function create(...$args) {
            return new static(...$args);
        }

        public function push($field) {
            $this->fields[] = $field;
        }

        public function toArray() {
            return $this->fields;
        }

        public function getIterator(): \Iterator {
            return new \ArrayIterator($this->fields);
        }

        public function count(): int {
            return count($this->fields);
        }

    }

    class RequiredFields {

    }

    class Form {

        public $controller;
        public $name;
        public $fields;
        public $actions;
        public $validator;

        public function __construct($controller, $name, $fields, $actions, $validator = null) {
            $this->controller = $controller;
            $this->name = $name;
            $this->fields = $fields;
            $this->actions = $actions;
            $this->validator = $validator;
        }

        public static function create(...$args) {
            return new static(...$args);
        }

        /**
         * The fluent setters (setTemplate, setFormMethod, loadDataFrom...) record their argument and return the form
         */
        public $settings = [];

        public function __call($name, $args) {
            $this->settings[$name] = $args[0] ?? true;
            return $this;
        }

    }

}

namespace SilverStripe\Forms\HTMLEditor {

    class HTMLEditorField extends \SilverStripe\Forms\FormField {

    }

}

namespace HudhaifaS\Forms {

    class FrontendFileField {

        public static function init_scripts() {

        }

    }

    class FrontendRichTextField extends \SilverStripe\Forms\FormField {

    }

    class UploadValidator {

        public $allowedExtensions;

        public function setAllowedExtensions($extensions) {
            $this->allowedExtensions = $extensions;
        }

    }

    class FrontendImageField extends \SilverStripe\Forms\FormField {

        public $folderName;
        private $validator;

        public function getValidator() {
            return $this->validator ?: $this->validator = new UploadValidator();
        }

        public function setFolderName($folder) {
            $this->folderName = $folder;
            return $this;
        }

    }

}

namespace TractorCow\Fluent\State {

    class FluentState {

        private static $instance;
        private $locale;

        public static function singleton() {
            return self::$instance ?: self::$instance = new static();
        }

        public static function reset() {
            self::$instance = null;
        }

        public function getLocale() {
            return $this->locale;
        }

        public function setLocale($locale) {
            $this->locale = $locale;
            return $this;
        }

        public function withState($callback) {
            $previous = $this->locale;
            try {
                return $callback($this);
            } finally {
                $this->locale = $previous;
            }
        }

    }

}

namespace TractorCow\Fluent\Model {

    class Locale {

        public static $cached = [];
        private $locale;
        private $title;
        private $hrefLang;

        public function __construct($locale, $title, $hrefLang) {
            $this->locale = $locale;
            $this->title = $title;
            $this->hrefLang = $hrefLang;
        }

        public static function getCached() {
            return self::$cached;
        }

        public function getLocale() {
            return $this->locale;
        }

        public function getTitle() {
            return $this->title;
        }

        public function getHrefLang() {
            return $this->hrefLang;
        }

    }

}

namespace {

    use SilverStripe\Control\HTTPResponse_Exception;

    /**
     * What customise()->renderWith() returns: the template asked for and the data it was given
     */
    class Rendered {

        public $template;
        public $data;

        public function __construct($template, $data) {
            $this->template = $template;
            $this->data = $data;
        }

    }

    class Customised {

        private $data;

        public function __construct($data) {
            $this->data = $data;
        }

        public function renderWith($template) {
            return new Rendered($template, $this->data);
        }

    }

    /**
     * The page record: the controller reads PageLength and MetaDescription from it, and Locales() reloads it
     */
    #[\AllowDynamicProperties]
    class Page {

        public $ID = 1;
        public $PageLength = 36;
        public $MetaDescription = 'Page meta description';
        public $URLSegment = 'objects';
        public $Locale;

        /**
         * Locale => the page as loaded in that locale (null when it doesn't exist there)
         */
        public static $byLocale = [];
        public static $loads = 0;

        public function Link($action = null) {
            return \SilverStripe\Control\Controller::join_links('/' . $this->URLSegment, $action);
        }

        public function get() {
            return new PageFinder();
        }

    }

    class PageFinder {

        public function byID($id) {
            Page::$loads++;
            $locale = \TractorCow\Fluent\State\FluentState::singleton()->getLocale();
            return array_key_exists($locale, Page::$byLocale) ? Page::$byLocale[$locale] : null;
        }

    }

    function _t($entity, $default = '') {
        return $default;
    }

    class PageController {

        public $dataRecord;
        public $owner;
        protected $request;

        public function __construct($dataRecord = null) {
            $this->dataRecord = $dataRecord ?: new Page();
            $this->request = new \SilverStripe\Control\HTTPRequest();
            $this->owner = $this;
        }

        public function redirectBack() {
            return 'REDIRECT_BACK';
        }

        public function init() {

        }

        public function getRequest() {
            return $this->request;
        }

        public function setRequest($request) {
            $this->request = $request;
            return $this;
        }

        public function customise($data) {
            return new Customised($data);
        }

        public function renderWith($template) {
            return new Rendered($template, []);
        }

        public function httpError($code, $message = null) {
            throw new HTTPResponse_Exception($message ?? '', $code);
        }

        public function Link($action = null) {
            return $this->dataRecord->Link($action);
        }

        public function hasMethod($method) {
            return method_exists($this, $method);
        }

        public function __get($name) {
            return $this->dataRecord->$name ?? null;
        }

    }

    require_once __DIR__ . '/../src/Model/ManageableDataObject.php';
    require_once __DIR__ . '/../src/Model/DiscoverableDataObject.php';
    require_once __DIR__ . '/../src/ORM/CachedPaginatedList.php';

    // The controller under test: the module's own unless a path is given, e.g. a copy of an older version
    require_once getenv('DOM_CONTROLLER') ?: __DIR__ . '/../src/View/DataObjectPageController.php';

    require_once __DIR__ . '/Fakes.php';
}
