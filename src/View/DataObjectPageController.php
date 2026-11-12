<?php

use HudhaifaS\DOM\Model\DiscoverableDataObject;
use HudhaifaS\DOM\Model\SociableDataObject;
use HudhaifaS\Forms\FrontendFileField;
use HudhaifaS\Forms\FrontendImageField;
use HudhaifaS\Forms\FrontendRichTextField;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\View\ThemeResourceLoader;

/**
 *
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.5, Jan 22, 2017 - 6:20:48 PM
 */
class DataObjectPageController
        extends PageController {

    private static $allowed_actions = [
        // Actions
        'show',
        'edit',
        'tabs',
        'tabcontent',
        'summary',
        'picture',
        'upload',
        'related',
        // Forms
        'ObjectEditForm',
        'doObjectEdit',
        'ImageEditForm',
        'doImageEdit',
        'doImageCancel',
        'ImportantEditForm',
        'doImportantEdit',
    ];
    private static $url_handlers = [
        'show/$ID' => 'show',
        'edit/$ID' => 'edit',
        'tabs/$ID' => 'tabs',
        'tabcontent/$ID/$Key' => 'tabcontent',
        'summary/$ID' => 'summary',
        'picture/$ID' => 'picture',
        'upload/$ID' => 'upload',
        'related/$ID' => 'related'
    ];

    /**
     * getSingle() results for this request, by ID
     *
     * @var array
     */
    private $singles = [];

    /**
     * Locales() for this request, false until it's built
     *
     * @var ArrayList|null|false
     */
    private $locales = false;

    public function init() {
        parent::init();

        FrontendFileField::init_scripts();

        Requirements::css("https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css");
        Requirements::css("hudhaifas/silverstripe-dataobject-manager: res/css/dataobject.css");
        Requirements::css("hudhaifas/silverstripe-dataobject-manager: res/css/lightbox.css");

        if ($this->isRTL()) {
            Requirements::css("hudhaifas/silverstripe-dataobject-manager: res/css/dataobject-rtl.css");
        }

        Requirements::javascript("https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js");
        Requirements::javascript("hudhaifas/silverstripe-dataobject-manager: res/js/lightbox.js");
        Requirements::javascript("hudhaifas/silverstripe-dataobject-manager: res/js/dataobject.manager.js");
    }

    public function index(HTTPRequest $request) {
        $start = microtime(true); // Start timing for performance measurement

        // Step 1: Get the full list
        $list = $this->getObjectsList();

        // Step 2: If search query exists, filter the list using it
        if ($query = $request->getVar('q')) {
            $list = $this->searchObjects($list, $query);
        }

        // Steps 3 and 4: Filter the list by permission and paginate it, in SQL when possible
        $filteredList = $this->filterViewable($list);
        if ($filteredList !== null) {
            // Narrowed in SQL: the database pages and counts it
            $count = $filteredList->count();
            $paginated = $this->paginate($filteredList, $request)->setTotalItems($count);
        } else if ($list instanceof DataList && $this->PageLength > 0) {
            // The database pages and counts the whole list, and only the page is checked: the count includes the
            // objects the visitor can't view, and a page with hidden objects on it is short
            $page = $this->paginate($list, $request);
            $count = $page->getTotalItems();
            $viewable = ArrayList::create();
            foreach ($page as $item) {
                if ($this->canViewSingle($item)) {
                    $viewable->push($item);
                }
            }
            $paginated = $this->paginate($viewable, $request)->setLimitItems(false)->setTotalItems($count);
        } else {
            // An in-memory list (or no paging): check every object
            $filteredList = $list->filterByCallback(function ($item) {
                return $this->canViewSingle($item);
            });
            $count = $filteredList->count();
            $paginated = $this->paginate($filteredList, $request);
        }

        $end = microtime(true); // End timing

        // Step 5: Prepare data for rendering
        $data = [
            'Results' => $paginated,
            'Count' => $count,
            'Seconds' => ($end - $start) / 1000,
        ];

        // Step 6: Handle AJAX vs full page request
        if ($request->isAjax()) {
            return $this->customise($data)->renderWith('Includes\ObjectsList');
        }

        return $data;
    }

    private function paginate($list, HTTPRequest $request) {
        return PaginatedList::create($this->breakSortTies($list), $request)
            ->setPageLength($this->PageLength)
            ->setPaginationGetVar('start');
    }

    /**
     * Sort a DataList by its ID after its own sort columns, so the database pages it in one fixed order: rows that tie
     * on every sort column could otherwise come back in a different order for each LIMIT, and repeat or go missing
     * across pages. A list that already sorts by ID keeps its order.
     */
    private function breakSortTies($list) {
        if (!$list instanceof DataList) {
            return $list;
        }

        return $list->alterDataQuery(function ($query) {
            $table = DataObject::getSchema()->baseDataTable($query->dataClass());
            foreach (array_keys($query->query()->getOrderBy()) as $column) {
                if (in_array(str_replace(['"', '`', ' '], '', $column), ['ID', "$table.ID"])) {
                    return;
                }
            }

            $query->sort("\"$table\".\"ID\"", 'ASC', false);
        });
    }

    /**
     * Return $list narrowed in SQL to the objects the current member can view (or $list itself when every object in
     * it is viewable), so the index paginates and counts in the database and checks nothing in PHP.
     *
     * Subclasses override this for the DataObject they list, returning exactly the objects canViewSingle() would keep.
     * The default, null, means there is no such filter: a DataList is then paged in SQL and only the page is checked.
     *
     * @return \SilverStripe\ORM\SS_List|null
     */
    protected function filterViewable($list) {
        return null;
    }

    protected function showSingle($single) {
        if (!$single) {
            return $this->httpError(404, 'That object could not be found!');
        }

        if (!$this->canViewSingle($single)) {
            return Security::permissionFailure($this);
        }

        $this->preRenderSingle($single);

        if ($single->hasMethod('addView')) {
            $single->addView();
        }

        return $this
                        ->customise([
                            'Single' => $single,
                            'Title' => $single->Title
                        ])
                        ->renderWith(['DataObjectPage_Show', 'DataObjectPage', 'Page']);
    }

    protected function editSingle($single) {
        if (!$single) {
            return $this->httpError(404, 'That object could not be found!');
        }

        if (!$single->canEdit()) {
            return Security::permissionFailure($this);
        }

        $this->preRenderSingle($single);

        return $this
                        ->customise([
                            'Single' => $single,
                            'Title' => $single->Title
                        ])
                        ->renderWith(['DataObjectPage_Edit', 'DataObjectPage', 'Page']);
    }

    public function getObjectsList() {
        return DataObject::get('Page');
    }

    protected function getSingle($singleID = null) {
        if ($singleID instanceof HTTPRequest) {
            $id = $singleID->postVar('ObjectID');
        } else {
            $id = $singleID;
        }

        if (!$id) {
            $id = $this->getRequest()->param('ID');
        }

        // No ID, e.g. on the list page: nothing to look up
        if (!$id) {
            return null;
        }

        $list = $this->getObjectsList();
        if (!$list) {
            return null;
        }

        return $list->filter([
                    'ID' => $id
                ])->first();
    }

    /**
     * getSingle() once per ID per request. A show page asks for its object from the action, RichSnippets, OpenGraph,
     * SocialDescription and Canonical; this makes them one lookup.
     */
    protected function getCachedSingle($singleID = null) {
        $id = $singleID instanceof HTTPRequest ? $singleID->postVar('ObjectID') : $singleID;
        if (!$id) {
            $id = $this->getRequest()->param('ID');
        }

        $key = (string) $id;
        if (!array_key_exists($key, $this->singles)) {
            $this->singles[$key] = $this->getSingle($singleID);
        }

        return $this->singles[$key];
    }

    public function searchObjects($list, $keywords) {
        if (!$list) {
            return null;
        }

        return $list->filter([
                    'Title:PartialMatch' => $keywords
        ]);
    }

    public function isSearchable() {
        return true;
    }

    protected function IsVerticalList() {
        return false;
    }

    /**
     *
     * $lists = [
     *     [
     *         'Title' => 'Categories',
     *         'Items' => $this->getObjectsList()
     *     ],
     *     [
     *         'Title' => 'Categories',
     *         'Items' => $this->getObjectsList()->Limit(6)
     *     ]
     * ];
     * return new ArrayList($lists);
     *
     * @return type
     */
    protected function getFiltersList() {
        return null;
    }

    public function restrictFields() {
        return [];
    }

    protected function preRenderList() {

    }

    protected function preRenderSingle($single) {

    }

    public function canViewSingle($single) {
        if(!$single) {
            return false;
        }

        if ($single->hasMethod('canPublicView')) {
            return $single->canPublicView();
        }

        return $single->canView();
    }

    /**
     * The record an edit form works on, only when the current member can edit it
     */
    protected function getEditableSingle($singleID) {
        $single = $this->getCachedSingle($singleID);

        return $single && $single->canEdit() ? $single : null;
    }

    /// Actions
    public function show() {
        $single = $this->getCachedSingle();

        return $this->showSingle($single);
    }

    public function edit() {
        $single = $this->getCachedSingle();

        return $this->editSingle($single);
    }

    public function tabs() {
        $single = $this->getCachedSingle();

        $tabs = $single->getObjectTabs();

        // Validate unique TabKeys
        $seenKeys = [];
        foreach ($tabs as $tab) {
            if (!isset($tab['Key']) || !$tab['Key']) {
                throw new \LogicException('Tab is missing required TabKey');
            }
            $tabKey = $tab['Key'];
            if (isset($seenKeys[$tabKey])) {
                throw new \LogicException("Duplicate tab Key detected: {$tabKey}");
            }
            $seenKeys[$tabKey] = true;
        }

        return $this
                        ->customise([
                            'ObjectTabs' => new ArrayList($tabs)
                        ])
                        ->renderWith('Includes\Single_Tabs');
    }

    public function tabcontent() {
        $request = $this->getRequest();
        $tab = $request->param('Key');
        $page = $request->getVar('page') ?? 1;

        if ($tab) {
            $content = $this->getCachedSingle()->getObjectTabContent($tab, $page);

            return $this
                            ->customise([
                                'Content' => $content
                            ])
                            ->renderWith('Includes/Single_TabContent');
        }
        return $this->httpError(400, 'Invalid tab content request');
    }

    public function summary() {
        $single = $this->getCachedSingle();

        return $this
                        ->customise([
                            'Single' => $single
                        ])
                        ->renderWith('Includes\Single_Summary');
    }

    public function picture() {
        $single = $this->getCachedSingle();

        return $this
                        ->customise([
                            'ObjectImage' => $single->getObjectImage(),
                            'ObjectDefaultImage' => $single->getObjectDefaultImage(),
                            'CanPublicView' => $single->canPublicView(),
                            'ObjectTitle' => $single->getObjectTitle()
                        ])
                        ->renderWith('Includes\Single_Image');
    }

    public function upload() {
        $single = $this->getCachedSingle();

        return $this
                        ->customise([
                            'ObjectImage' => $single->getObjectImage(),
                            'ObjectDefaultImage' => $single->getObjectDefaultImage(),
                            'CanPublicView' => $single->canPublicView(),
                            'ObjectTitle' => $single->getObjectTitle()
                        ])
                        ->renderWith('Includes\Single_Image_Upload');
    }

    public function related() {
        $single = $this->getCachedSingle();

        return $this
                        ->customise([
                            'Single' => $single
                        ])
                        ->renderWith('Includes\Single_Related');
    }

    /// Links
    public function TabsLink($id) {
        return $this->Link("tabs/$id");
    }

    public function TabContentLink($id, $key) {
        return $this->Link("tabcontent/$id/$key");
    }

    public function SummaryLink($id) {
        return $this->Link("summary/$id");
    }

    public function PictureLink($id) {
        return $this->Link("picture/$id");
    }

    public function UploadLink($id) {
        return $this->Link("upload/$id");
    }

    public function RelatedLink($id) {
        return $this->Link("related/$id");
    }

    /// Forms
    public function ObjectSearchForm() {
        if ($this->hasMethod('getGoogleSiteSearchForm')) {
            return $this->getGoogleSiteSearchForm()->setTemplate('Form_GoogleSearch');
        }

        $data = $this->request->getVars();

        $form = Form::create(
                        $this, 'ObjectSearchForm', FieldList::create(
                                TextField::create('q')
                        ), FieldList::create(
                                FormAction::create('doObjectSearch')
                        )
        );

        $form
                ->disableSecurityToken()
                ->setFormMethod('GET')
                ->setFormAction($this->Link())
                ->setTemplate('Includes\Form_ObjectSearch')
                ->loadDataFrom($data);

        return $form;
    }

    public function ObjectEditForm($singleID) {
        if ($singleID instanceof HTTPRequest) {
            $id = $singleID->postVar('ObjectID');
        } else {
            $id = $singleID;
        }
        // No form for someone who can't edit the record
        $single = $this->getEditableSingle($id);
        if (!$single) {
            return null;
        }

        // Create fields
        $fields = new FieldList();
        $fields->push(HiddenField::create('ObjectID', 'ObjectID', $single->ID));
        $this->getRecordFields($single, $fields);

        // Create action
        $actions = new FieldList();
        $this->getRecordActions($single, $actions);

        // Create Validators
        $validator = new RequiredFields();

        $form = Form::create($this, 'ObjectEditForm', $fields, $actions, $validator);

        return $form;
    }

    public function doObjectEdit($data, $form) {
        $single = $this->getEditableSingle($data['ObjectID'] ?? 0);
        if (!$single) {
            return Security::permissionFailure($this);
        }

        // Only the form's fields are saved, not whatever was posted
        $form->saveInto($single);
        $single->write();
        return $this->owner->redirectBack();
    }

    public function ImageEditForm($singleID) {
        if ($singleID instanceof HTTPRequest) {
            $id = $singleID->postVar('ObjectID');
        } else {
            $id = $singleID;
        }
        // No form for someone who can't edit the record
        $single = $this->getEditableSingle($id);
        $imageName = $single ? $single->getObjectEditableImageName() : null;
        if (!$imageName) {
            return null;
        }

        // Create fields
        $fields = new FieldList();
        $fields->push(HiddenField::create('ObjectID', 'ObjectID', $single->ID));

        // Upload Field
        $field = FrontendImageField::create($imageName, '', $single->getObjectImage());
        $field->getValidator()->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
        if ($single->hasMethod('getFolderName')) {
            $field->setFolderName($single->getFolderName());
        }
        $fields->push($field);

        // Create action
        $actions = new FieldList();
        $actions->push(
                FormAction::create('doImageEdit', _t('DataObjectPage.SAVE', 'Save'))
                        ->addExtraClass('btn btn-primary')
        );

        // Create Validators
        $validator = new RequiredFields();

        $form = Form::create($this, 'ImageEditForm', $fields, $actions, $validator);

        return $form;
    }

    public function doImageEdit($data, $form) {
        $single = $this->getEditableSingle($data['ObjectID'] ?? 0);
        if (!$single) {
            return Security::permissionFailure($this);
        }

        $form->saveInto($single);
        $single->write();
        return $this->owner->redirectBack();
    }

    public function doImageCancel($data, $form) {
        return $this->owner->redirectBack();
    }

    public function ImportantEditForm($singleID) {
        if ($singleID instanceof HTTPRequest) {
            $id = $singleID->postVar('ObjectID');
        } else {
            $id = $singleID;
        }
        // No form for someone who can't edit the record
        $single = $this->getEditableSingle($id);
        $items = $single ? $single->getImportantItems() : null;
        if (!$items) {
            return null;
        }

        $items[] = HiddenField::create('ObjectID', 'ObjectID', $single->ID);

        // Create fields
        $fields = new FieldList($items);

        // Create action
        $actions = new FieldList(
                FormAction::create('doImageEdit', _t('DataObjectPage.SAVE', 'Save'))
                        ->addExtraClass('btn btn-primary')
        );

        // Create Validators
        $validator = new RequiredFields();

        return Form::create($this, 'ImportantEditForm', $fields, $actions, $validator);
    }

    public function doImportantEdit($data, $form) {
        $single = $this->getEditableSingle($data['ObjectID'] ?? 0);
        if (!$single) {
            return Security::permissionFailure($this);
        }

        $form->saveInto($single);
        $single->write();
        return $this->owner->redirectBack();
    }

    protected final function getRecordActions($record, &$actions) {
        if ($record->hasMethod('canEdit') && $record->canEdit()) {
            $actions->push(
                    FormAction::create('doObjectEdit', _t('DataObjectPage.SAVE', 'Save'))
                            ->addExtraClass('btn btn-primary btn-block')
            );
        }

        if ($record->ID && $record->hasMethod('canDelete') && $record->canDelete()) {
            $actions->push(
                    FormAction::create('doDelete', _t('DataObjectPage.DELETE', 'Delete'))
                            ->addExtraClass('btn btn-danger btn-block')
            );
        }
    }

    protected final function getRecordFields($record, &$fields) {
        if (!$record) {
            return;
        }

        $config = $record->config();
        $dbFields = $config->get('db');
        $restrictFields = $config->restrict_fields;
        if ($restrictFields) {
            $restrictFields = array_merge(['Version'], $restrictFields);
        } else {
            $restrictFields = ['Version'];
        }

        // Names are looked up in a set rather than with in_array() on every field. in_array() compares loosely, which
        // only differs from an exact match for values that aren't non-numeric strings, so those are still compared
        // with in_array()
        $restricted = [];
        $looselyRestricted = [];
        foreach ($restrictFields as $restrictField) {
            if (is_string($restrictField) && !is_numeric($restrictField)) {
                $restricted[$restrictField] = true;
            } else {
                $looselyRestricted[] = $restrictField;
            }
        }

        // iterate database fields
        foreach ($dbFields as $fieldName => $fieldType) {
            if (isset($restricted[$fieldName]) || ($looselyRestricted && in_array($fieldName, $looselyRestricted))) {
                continue;
            }

            $fieldObject = $record->dbObject($fieldName)->scaffoldFormField(null);

            // Allow fields to opt-out of scaffolding
            if (!$fieldObject) {
                continue;
            }

            if ($fieldObject instanceof HTMLEditorField) {
                $fieldObject = FrontendRichTextField::create($fieldName);
            } else if ($fieldObject instanceof DateField) {

            }

            $fieldObject->setTitle($record->fieldLabel($fieldName));
            $fieldObject->setValue($record->$fieldName);

            $fields->push($fieldObject);
        }
    }

    /// Utilities
    public function ExtraClasses() {
        return '';
    }

    public function ExtraMetaTags() {
        return $this->renderWith('Includes\Page_ExtraTags');
    }

    public function RichSnippets() {
        $single = $this->getCachedSingle();

        if ($single && $single instanceof DiscoverableDataObject) {
            $schema = $single->getObjectMarkup();
            $schema['@context'] = "http://schema.org";

            // The JSON goes inside a <script> tag raw, so <, > and & are escaped: a name holding "</script>" can't end it
            $text = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

            // Collapse excessive whitespace. There are no line breaks to remove: json_encode() escapes them
            $text = preg_replace('/\s{2,}/u', ' ', $text);

            return DBField::create_field('HTMLText', trim($text));
        }
    }

    public function OpenGraph() {
        $single = $this->getCachedSingle();

        if ($single) {
            return $this
                            ->customise([
                                'Single' => $single
                            ])
                            ->renderWith('Includes\Single_OpenGraph');
        } else {
            return $this->renderWith('Includes\Page_OpenGraph');
        }
    }

    public function SocialDescription() {
        $single = $this->getCachedSingle();

        if ($single && $single->hasMethod('getObjectDescription')) {
            $text = strip_tags($single->getObjectDescription());
            $text = preg_replace('/\s+/', ' ', $text); // Normalize all whitespace
            $text = preg_replace('/\s*\(\s*/', ' (', $text); // Clean spaces after '('
            $text = preg_replace('/\s*\)\s*/', ') ', $text); // Clean spaces before ')'
            return trim($text);
        }

        return $this->MetaDescription;
    }

    public function Canonical() {
        $single = $this->getCachedSingle();

        if ($single) {
            return $this->FullURL($single->getObjectLink());
        } else {
            return $this->FullURL($this->Link());
        }
    }

    public function FullURL($url) {
        return Director::absoluteURL($url);
    }

    public function Align() {
        return $this->isRTL() == 'rtl' ? 'right' : 'left';
    }

    public function isRTL() {
        return i18n::get_script_direction(i18n::get_locale()) == 'rtl';
    }

    /**
     * Generates a list of alternate locales and localized links for the current DataObject view.
     *
     * This function is intended to be used in multilingual websites that utilize the
     * TractorCow Fluent module. It builds an `ArrayList` of available locales, each containing:
     * - The locale code (e.g., 'en_US', 'fr_FR')
     * - The locale title (e.g., 'English (US)', 'French (France)')
     * - A localized URL to the current action and parameters (e.g., 'show/123')
     * - A `LinkingMode` to indicate whether the locale is the current one
     * - The hreflang value for SEO (`HrefLang`)
     *
     * This method dynamically appends the current request's action path (e.g., `show/123`)
     * to the localized page's base link, ensuring that cross-locale navigation maintains context.
     *
     * @return \SilverStripe\ORM\ArrayList|null List of locales and their corresponding links,
     *                                          or null if Fluent is not installed or not active
     */
    public function Locales(): ?ArrayList
    {
        if ($this->locales !== false) {
            return $this->locales;
        }

        if (!class_exists(\TractorCow\Fluent\State\FluentState::class) || !class_exists(TractorCow\Fluent\Model\Locale::class)) {
            return $this->locales = null;
        }

        $fluent = TractorCow\Fluent\State\FluentState::singleton();
        $current = $fluent->getLocale();
        if (!$current) {
            return $this->locales = null;
        }

        $locales = TractorCow\Fluent\Model\Locale::getCached();

        $request = $this->getRequest();
        $action = $request->param('Action');
        $id = $request->param('ID');
        $otherId = $request->param('OtherID');

        $list = ArrayList::create();

        foreach ($locales as $locale) {
            // Reload localised page in the correct locale
            $page = $fluent->withState(function (TractorCow\Fluent\State\FluentState $state) use ($locale) {
                $state->setLocale($locale->getLocale());
                return $this->dataRecord->get()->byID($this->dataRecord->ID);
            });

            $link = Controller::join_links($page->Link(), $action, $id, $otherId);

            $list->push(ArrayData::create([
                'Locale' => $locale->getLocale(),
                'Title' => $locale->getTitle(),
                'Link' => $link,
                'AbsoluteLink' => $link ? Director::absoluteURL($link) : null,
                'LinkingMode' => $locale->getLocale() === $current ? 'current' : 'link',
                'HrefLang' => $locale->getHrefLang(),
            ]));
        }

        return $this->locales = $list;
    }

    public function Strip($html) {
        return strip_tags($html);
    }
}
