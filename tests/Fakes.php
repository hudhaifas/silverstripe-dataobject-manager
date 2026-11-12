<?php

use HudhaifaS\DOM\Model\DiscoverableDataObject;
use HudhaifaS\DOM\Model\ManageableDataObject;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;

/**
 * A managed object whose answers are set by the test, and which counts the calls made to it
 */
#[\AllowDynamicProperties]
class FakeObject implements ManageableDataObject, DiscoverableDataObject {

    public $ID;
    public $Title;
    public $publicView = true;
    public $editable = false;
    public $deletable = false;
    public $views = 0;
    public $tabs = [];
    public $editableImageName = null;
    public $importantItems = null;
    public $markup = [];
    public $description = '';
    public $db = [];
    public $restrictFields = null;
    public $calls = [];

    public function __construct($id, $title = null, $values = []) {
        $this->ID = $id;
        $this->Title = $title ?? "Object $id";
        foreach ($values as $key => $value) {
            $this->$key = $value;
        }
    }

    private function called($method) {
        $this->calls[$method] = ($this->calls[$method] ?? 0) + 1;
    }

    public function hasMethod($method) {
        return method_exists($this, $method);
    }

    public function canPublicView() {
        $this->called('canPublicView');
        return $this->publicView;
    }

    public function canView() {
        return $this->publicView;
    }

    public function canEdit() {
        return $this->editable;
    }

    public function canDelete() {
        return $this->deletable;
    }

    public function write() {
        $this->called('write');
    }

    public function addView() {
        $this->views++;
    }

    public function getObjectItem() {
        return "item-{$this->ID}";
    }

    public function getObjectTitle() {
        $this->called('getObjectTitle');
        return $this->Title;
    }

    public function getObjectLink() {
        return "/objects/show/{$this->ID}";
    }

    public function getObjectEditLink() {
        return "/objects/edit/{$this->ID}";
    }

    public function getObjectEditableImageName() {
        $this->called('getObjectEditableImageName');
        return $this->editableImageName;
    }

    public function getObjectImage() {
        $this->called('getObjectImage');
        return "image-{$this->ID}";
    }

    public function getObjectDefaultImage() {
        $this->called('getObjectDefaultImage');
        return 'default.jpg';
    }

    public function getObjectSummary() {
        return [];
    }

    public function getObjectNav() {
        return null;
    }

    public function getObjectRelated() {
        return null;
    }

    public function getObjectTabs() {
        return $this->tabs;
    }

    public function getObjectTabContent($tabKey, $page = 1) {
        return ['tab' => $tabKey, 'page' => $page];
    }

    public function getImportantItems() {
        $this->called('getImportantItems');
        return $this->importantItems;
    }

    public function getObjectMarkup() {
        return $this->markup;
    }

    public function getObjectDescription() {
        return $this->description;
    }

    /// What getRecordFields() reads ///
    public function config() {
        $this->called('config');
        return new FakeConfig(['db' => $this->db, 'restrict_fields' => $this->restrictFields]);
    }

    public function dbObject($fieldName) {
        return new FakeDBField($fieldName, $this->db[$fieldName]);
    }

    /**
     * An unset field reads as null, as on a DataObject
     */
    public function __get($name) {
        return null;
    }

    public function fieldLabel($fieldName) {
        return "Label $fieldName";
    }

}

/**
 * An object that isn't a ManageableDataObject: only canView() decides who sees it
 */
#[\AllowDynamicProperties]
class PlainObject {

    public $ID;
    public $Title;
    public $viewable;

    public function __construct($id, $viewable) {
        $this->ID = $id;
        $this->Title = "Plain $id";
        $this->viewable = $viewable;
    }

    public function hasMethod($method) {
        return method_exists($this, $method);
    }

    public function canView() {
        return $this->viewable;
    }

}

class FakeImageObject extends FakeObject {

    public function getFolderName() {
        return 'object-images';
    }

}

/**
 * A model with a list-narrowing method of its own, which the controller must not call: filtering is the controller's job
 */
class SelfFilteringObject extends FakeObject {

    public static $filtered = 0;

    public function filterPublicViewable($list) {
        static::$filtered++;
        return $list->filter(['publicView' => true]);
    }

}

class FakeConfig {

    private $values;

    public function __construct($values) {
        $this->values = $values;
    }

    public function get($name) {
        return $this->values[$name] ?? null;
    }

    public function __get($name) {
        return $this->get($name);
    }

}

/**
 * Scaffolds the form field a database type maps to; "NoScaffold" opts out
 */
class FakeDBField {

    private $name;
    private $type;

    public function __construct($name, $type) {
        $this->name = $name;
        $this->type = $type;
    }

    public function scaffoldFormField($title = null) {
        switch ($this->type) {
            case 'HTMLText':
                return HTMLEditorField::create($this->name, $title);
            case 'Date':
                return DateField::create($this->name, $title);
            case 'NoScaffold':
                return null;
            default:
                return TextField::create($this->name, $title);
        }
    }

}

/**
 * The controller under test, listing the objects the test gives it and counting getSingle() lookups
 */
class TestController extends DataObjectPageController {

    public $objects;
    public $lookups = 0;
    public $listLoads = 0;

    public function getObjectsList() {
        $this->listLoads++;
        return $this->objects;
    }

    protected function getSingle($singleID = null) {
        $this->lookups++;
        return parent::getSingle($singleID);
    }

}

/**
 * A controller that narrows a DataList to its publicly viewable objects in SQL, keeping exactly what canPublicView()
 * keeps, as PeoplePageController does for Gender
 */
class SqlFilteredController extends TestController {

    public $filtered = 0;

    protected function filterViewable($list) {
        if (!$list instanceof DataList) {
            return null;
        }

        $this->filtered++;
        return $list->filter(['publicView' => true]);
    }

}

/**
 * A controller every object of which is publicly viewable, like the Etymology, Urbanism and GenderCategory pages
 */
class AlwaysViewableController extends TestController {

    protected function filterViewable($list) {
        return $list;
    }

}

/**
 * Lists the module's default getObjectsList() returns are read from DataObject::$lists
 */
class DefaultListController extends DataObjectPageController {

}

function objects(...$objects) {
    return new ArrayList($objects);
}

/**
 * A database-backed list of $count objects of $class with IDs 1..$count; the IDs in $hidden aren't publicly viewable
 */
function numberedData($count, $hidden = [], $class = FakeObject::class) {
    $list = [];
    for ($id = 1; $id <= $count; $id++) {
        $list[] = new $class($id, null, ['publicView' => !in_array($id, $hidden)]);
    }

    return new DataList($class, $list);
}

/**
 * A database-backed list of objects with IDs 1..count($names), whose IndexedName values are $names, sorted by $orderBy
 * as MySQL would: rows tied on every sort column come back in a different order on each page
 */
function sortedData(array $names, array $orderBy, $hidden = []) {
    $list = [];
    foreach (array_values($names) as $i => $name) {
        $id = $i + 1;
        $list[] = new FakeObject($id, null, ['IndexedName' => $name, 'publicView' => !in_array($id, $hidden)]);
    }

    return (new DataList(FakeObject::class, $list))->sortedBy($orderBy);
}

/**
 * The IDs of the objects in a DataList that were asked canPublicView(), read without querying
 */
function checkedIDs($list) {
    $checked = array_filter($list->records(), function ($object) {
        return ($object->calls['canPublicView'] ?? 0) > 0;
    });

    return array_values(array_map(function ($object) {
        return $object->ID;
    }, $checked));
}
