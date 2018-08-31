<?php

use HudhaifaS\DOM\Model\SearchableDataObject;
use HudhaifaS\DOM\Model\SociableDataObject;
use HudhaifaS\Forms\FrontendFileField;
use HudhaifaS\Forms\FrontendImageField;
use HudhaifaS\Forms\FrontendRichTextField;
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
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;

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
        'summary/$ID' => 'summary',
        'picture/$ID' => 'picture',
        'upload/$ID' => 'upload',
        'related/$ID' => 'related'
    ];

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
        $start = microtime(true); // time in Microseconds

        $results = $this->getObjectsList();

        if ($query = $request->getVar('q')) {
            $results = $this->searchObjects($results, $query);
        }

        if (!$results) {
            return [];
        }

        $paginated = PaginatedList::create(
                        $results, $request
                )->setPageLength($this->PageLength)
                ->setPaginationGetVar('s');

        $end = microtime(true); // time in Microseconds

        $data = [
            'Results' => $paginated,
            'Seconds' => ($end - $start) / 1000
        ];

        if ($request->isAjax()) {
            return $this->customise($data)
                            ->renderWith('Includes\ObjectsList');
        }

        return $data;
    }

    protected function showSingle($single) {
        if (!$single) {
            return $this->httpError(404, 'That object could not be found!');
        }

        if (!$single->canPublicView()) {
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

        $list = $this->getObjectsList();
        if (!$list) {
            return null;
        }

        if (!$id) {
            $id = $this->getRequest()->param('ID');
        }

        return $list->filter([
                    'ID' => $id
                ])->first();
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

    /// Actions
    public function show() {
        $single = $this->getSingle();

        return $this->showSingle($single);
    }

    public function edit() {
        $single = $this->getSingle();

        return $this->editSingle($single);
    }

    public function tabs() {
        $single = $this->getSingle();

        return $this
                        ->customise([
                            'ObjectTabs' => $single->getObjectTabs()
                        ])
                        ->renderWith('Includes\Single_Tabs');
    }

    public function summary() {
        $single = $this->getSingle();

        return $this
                        ->customise([
                            'Single' => $single
                        ])
                        ->renderWith('Includes\Single_Summary');
    }

    public function picture() {
        $single = $this->getSingle();

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
        $single = $this->getSingle();

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
        $single = $this->getSingle();

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
                        $this, 'ObjectSearchForm',
                        FieldList::create(
                                TextField::create('q')
                        ),
                        FieldList::create(
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
        $single = $this->getSingle($id);

        if (!$single) {
            $this->httpError(404, 'That object could not be found!');
        }

        if ($single && !$single->canPublicView()) {
            Security::permissionFailure($this);
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
        $objectID = $data['ObjectID'];

        $single = $this->getSingle($objectID);

        foreach ($data as $key => $value) {
            $single->$key = $value;
        }

        $single->write();
        return $this->owner->redirectBack();
    }

    public function ImageEditForm($singleID) {
        if ($singleID instanceof HTTPRequest) {
            $id = $singleID->postVar('ObjectID');
        } else {
            $id = $singleID;
        }
        $single = $this->getSingle($id);

        if (!$single) {
            $this->httpError(404, 'That object could not be found!');
        }

        if ($single && !$single->canEdit()) {
            Security::permissionFailure($this);
        }

        if ($single && !$single->getObjectEditableImageName()) {
            return null;
        }

        // Create fields          
        $fields = new FieldList();
        $fields->push(HiddenField::create('ObjectID', 'ObjectID', $single->ID));

        // Upload Field
        $field = FrontendImageField::create($single->getObjectEditableImageName(), '',
                        $single->getObjectImage());
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
        $objectID = $data['ObjectID'];
        $single = $this->getSingle($objectID);

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
        $single = $this->getSingle($id);

        if (!$single) {
            $this->httpError(404, 'That object could not be found!');
        }

        if ($single && !$single->canEdit()) {
            Security::permissionFailure($this);
        }

        if ($single && !$single->getImportantItems()) {
            return null;
        }

        $items = $single->getImportantItems();
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
        $objectID = $data['ObjectID'];
        $single = $this->getSingle($objectID);

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

        $dbFields = $record->config()->get('db');
        $restrictFields = array_merge(['Version'], $record->config()->restrict_fields);

        // iterate database fields
        foreach ($dbFields as $fieldName => $fieldType) {
            if ($restrictFields && in_array($fieldName, $restrictFields)) {
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
        $single = $this->getSingle();

        if ($single && $single instanceof SearchableDataObject) {
            $schema = $single->getObjectRichSnippets();
            $schema['@context'] = "http://schema.org";

            return Convert::array2json($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    public function OpenGraph() {
        $single = $this->getSingle();

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
        $single = $this->getSingle();

        if ($single && $single instanceof SociableDataObject) {
            return $single->getSocialDescription();
        }
    }

    public function Canonical() {
        $single = $this->getSingle();

        if ($single) {
            return $single->getObjectLink();
        } else {
            return $this->Link();
        }
    }

    public function FullURL($url) {
        return Director::absoluteURL($url);
    }

    public function ThemedURL($url) {
        return Director::absoluteURL($this->ThemeDir() . $url);
    }

    public function Align() {
        return $this->isRTL() == 'rtl' ? 'right' : 'left';
    }

    public function isRTL() {
        return i18n::get_script_direction(i18n::get_locale()) == 'rtl';
    }

    public function Strip($html) {
        return strip_tags($html);
    }

}
