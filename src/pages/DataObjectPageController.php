<?php

/*
 * MIT License
 *  
 * Copyright (c) 2017 Hudhaifa Shatnawi
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayList;
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

    private static $allowed_actions = array(
        'show',
        'edit',
        'tabs',
        'summary',
        'picture',
        'related',
        'ObjectEditForm',
        'doObjectEdit',
        'ImageEditForm',
        'doImageEdit',
        'doImageCancel',
    );
    private static $url_handlers = array(
        'show/$ID' => 'show',
        'edit/$ID' => 'edit',
        'tabs/$ID' => 'tabs',
        'summary/$ID' => 'summary',
        'picture/$ID' => 'picture',
        'related/$ID' => 'related'
    );

    public function init() {
        parent::init();

        Requirements::css("hudhaifas/silverstripe-dataobject-manager: client/css/dataobject.css");
        Requirements::css("hudhaifas/silverstripe-dataobject-manager: client/css/lightbox.css");
        if ($this->isRTL()) {
            Requirements::css("hudhaifas/silverstripe-dataobject-manager: client/css/dataobject-rtl.css");
        }

        Requirements::javascript("hudhaifas/silverstripe-dataobject-manager: client/js/lightbox.js");
        Requirements::javascript("hudhaifas/silverstripe-dataobject-manager: client/js/dataobject.manager.js");
    }

    public function index(HTTPRequest $request) {
        $start = microtime(true); // time in Microseconds

        $results = $this->getObjectsList();

        if ($query = $request->getVar('q')) {
            $results = $this->searchObjects($results, $query);
        }

        if (!$results) {
            return array();
        }

        $paginated = PaginatedList::create(
                        $results, $request
                )->setPageLength($this->PageLength)
                ->setPaginationGetVar('s');

        $end = microtime(true); // time in Microseconds

        $data = array(
            'Results' => $paginated,
            'Seconds' => ($end - $start) / 1000
        );

        if ($request->isAjax()) {
            return $this->customise($data)
                            ->renderWith('Includes\ObjectsList');
        }

        return $data;
    }

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
                        ->customise(array(
                            'ObjectTabs' => $single->getObjectTabs()
                        ))
                        ->renderWith('Includes\Single_Tabs');
    }

    public function summary() {
        $single = $this->getSingle();

        return $this
                        ->customise(array(
                            'Single' => $single
                        ))
                        ->renderWith('Includes\Single_Summary');
    }

    public function picture() {
        $single = $this->getSingle();

        return $this
                        ->customise(array(
                            'ObjectImage' => $single->getObjectImage(),
                            'ObjectDefaultImage' => $single->getObjectDefaultImage(),
                            'CanPublicView' => $single->canPublicView(),
                            'ObjectTitle' => $single->getObjectTitle()
                        ))
                        ->renderWith('Includes\Single_Image');
    }

    public function related() {
        $single = $this->getSingle();

        return $this
                        ->customise(array(
                            'Single' => $single
                        ))
                        ->renderWith('Includes\Single_Related');
    }

    public function TabsLink($id) {
        return $this->Link("tabs/$id");
    }

    public function SummaryLink($id) {
        return $this->Link("summary/$id");
    }

    public function PictureLink($id) {
        return $this->Link("picture/$id");
    }

    public function RelatedLink($id) {
        return $this->Link("related/$id");
    }

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
                ->setTemplate('Form_ObjectSearch')
                ->loadDataFrom($data);

        return $form;
    }

    public function ObjectEditForm($singleID) {
        if ($singleID instanceof SS_HTTPRequest) {
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
        if ($singleID instanceof SS_HTTPRequest) {
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
        $images = new ArrayList();
        $images->push($single->getObjectImage());

        $field = FrontendUploadField::create($single->getObjectEditableImageName(), '', $images);
        $field->setCanAttachExisting(false); // Block access to SilverStripe assets library
        $field->setCanPreviewFolder(false); // Don't show target filesystem folder on upload field
        $field->setAllowedMaxFileNumber(1);
        $field->setPreviewMaxHeight(160);
        $field->setPreviewMaxWidth(160);

        $fields->push($field);

        // Create action
        $actions = new FieldList();
        $actions->push(
                FormAction::create('doImageEdit', _t('DataObjectPage.SAVE', 'Save'))
                        ->addExtraClass('btn btn-primary')
        );
        $actions->push(
                FormAction::create(null, _t('DataObjectPage.CANCEL', 'Cancel'))
                        ->addExtraClass('btn btn-default btn-hide-form')
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

    protected function showSingle($single) {
        if (!$single) {
            return $this->httpError(404, 'That object could not be found!');
        }

        if (!$single->canPublicView()) {
            return Security::permissionFailure($this);
        }

        $this->preRenderSingle($single);

        return $this
                        ->customise(array(
                            'Single' => $single,
                            'Title' => $single->Title
                        ))
                        ->renderWith(array('DataObjectPage_Show', 'DataObjectPage', 'Page'));
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
                        ->customise(array(
                            'Single' => $single,
                            'Title' => $single->Title
                        ))
                        ->renderWith(array('DataObjectPage_Edit', 'DataObjectPage', 'Page'));
    }

    protected function getObjectsList() {
        return DataObject::get('Page');
    }

    protected function getSingle($id = null) {
        $list = $this->getObjectsList();
        if (!$list) {
            return null;
        }

        if (!$id) {
            $id = $this->getRequest()->param('ID');
        }

        return $list->filter(array(
                    'ID' => $id
                ))->first();
    }

    public function ExtraTags() {
        return $this->renderWith('Includes\Page_ExtraTags');
    }

    public function ExtraClasses() {
        return '';
    }

    public function RichSnippets() {
        $single = $this->getSingle();

        if ($single && $single instanceof SearchableDataObject) {
            $schema = $single->getObjectRichSnippets();
            $schema['@context'] = "http://schema.org";

//        return json_encode($schema, JSON_UNESCAPED_UNICODE);
            return Convert::array2json($schema);
        }
    }

    public function OpenGraph() {
        $single = $this->getSingle();

        if ($single) {
            return $this
                            ->customise(array(
                                'Single' => $single
                            ))
                            ->renderWith('Includes\Single_OpenGraph');
        } else {
            return $this->renderWith('Includes\Page_OpenGraph');
        }
    }

    public function FullURL($url) {
        return Director::absoluteURL($url);
    }

    public function ThemedURL($url) {
        return Director::absoluteURL($this->ThemeDir() . $url);
    }

    protected function searchObjects($list, $keywords) {
        if (!$list) {
            return null;
        }

        return $list->filter(array(
                    'Title:PartialMatch' => $keywords
        ));
    }

    protected function IsVerticalList() {
        return false;
    }

    /**
     * 
     * $lists = array(
     *     array(
     *         'Title' => 'Categories',
     *         'Items' => $this->getObjectsList()
     *     ),
     *     array(
     *         'Title' => 'Categories',
     *         'Items' => $this->getObjectsList()->Limit(6)
     *     )
     * );
     * return new ArrayList($lists);
     * 
     * @return type
     */
    protected function getFiltersList() {
        return null;
    }

    public function restrictFields() {
        return array();
    }

    /**
     * 
     * $lists = array(
     *     array(
     *         'Title' => 'Header 1',
     *         'Content' => 'Content 1'
     *     ),
     *     array(
     *         'Title' => 'Header 2',
     *         'Content' => 'Content 2'
     *     ),
     *     array(
     *         'Title' => 'Header 3',
     *         'Content' => 'Content 3'
     *     ),
     * );
     * return new ArrayList($lists);
     */
    protected function getTabsList() {
        return null;
    }

    protected function preRenderList() {
        
    }

    protected function preRenderSingle($single) {
        
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

        $dbFields = $record->db();
        $restrictFields = $record->config()->restrict_fields;

        // iterate database fields
        foreach ($dbFields as $fieldName => $fieldType) {
            if ($restrictFields && in_array($fieldName, $restrictFields)) {
                continue;
            }

            $fieldObject = $record->dbObject($fieldName)->scaffoldFormField(null);

            $fieldObject->setTitle($record->fieldLabel($fieldName));
            $fieldObject->setValue($record->$fieldName);

            if ($fieldObject instanceof HtmlEditorField) {
                
            } else if ($fieldObject instanceof DateField) {
                $fieldObject->setConfig('showcalendar', true);
//                    $fieldObject->setConfig('showdropdown', true);
//                    $fieldObject->setConfig('dateformat', 'dd-MM-yyyy');
            }
            $fields->push($fieldObject);
        }
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
