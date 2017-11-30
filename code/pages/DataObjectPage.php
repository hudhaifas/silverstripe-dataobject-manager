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

/**
 *
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.5, Jan 22, 2017 - 6:20:48 PM
 */
class DataObjectPage
        extends Page {

    private static $db = array(
        'PageLength' => 'Int',
    );
    private static $defaults = array(
        'PageLength' => 36,
    );
    private static $icon = "dataobjectpage/images/wrap.png";

    public function canCreate($member = false) {
        return false;
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();

        $fields->removeFieldFromTab("Root.Main", "Content");

        $fields->addFieldToTab('Root.Main', new NumericField('PageLength', _t('DataObjectPage.PAGE_LENGTH', 'Page Length'), $this->PageLength));

        return $fields;
    }

}

class DataObjectPage_Controller
        extends Page_Controller {

    private static $allowed_actions = array(
        'show',
        'edit',
        'tabs',
        'ObjectEditForm',
        'doObjectEdit',
    );
    private static $url_handlers = array(
        'show/$ID' => 'show',
        'edit/$ID' => 'edit',
        'tabs/$ID' => 'tabs'
    );

    public function init() {
        parent::init();

        Requirements::css(DATAOBJECT_MANAGER_DIR . "/css/dataobject.css");
        if ($this->isRTL()) {
            Requirements::css(DATAOBJECT_MANAGER_DIR . "/css/dataobject-rtl.css");
        }

        Requirements::javascript(DATAOBJECT_MANAGER_DIR . "/js/dataobject.manager.js");
        Requirements::javascript(DATAOBJECT_MANAGER_DIR . "/js/jquery.imgzoom.js");
    }

    public function index(SS_HTTPRequest $request) {
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
                            ->renderWith('ObjectsList');
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
                        ->renderWith('Single_Tabs');
    }

    public function TabsLink($id) {
        return $this->Link("tabs/$id");
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

    public function ObjectEditForm($class, $id) {
        $single = DataObject::get_by_id($class, $id);

        // Create fields          
        $fields = new FieldList();
        $fields->push(HiddenField::create('ObjectID', 'ObjectID', $id));

        if ($single) {
            $dbFields = $single->db();

            // iterate database fields
            foreach ($dbFields as $fieldName => $fieldType) {
                if ($this->restrictFields() && !in_array($fieldName, $this->restrictFields())) {
                    continue;
                }

                $fieldObject = $single->dbObject($fieldName)->scaffoldFormField(null);

                $fieldObject->setTitle($single->fieldLabel($fieldName));
                $fieldObject->setValue($single->$fieldName);
                $fields->push($fieldObject);
            }
        }

        // Create action
        $actions = new FieldList(
                FormAction::create('doObjectEdit', _t('DataObjectPage.SAVE', 'Save'))
        );

        // Create Validators
        $validator = new RequiredFields();

        $form = Form::create($this, 'ObjectEditForm', $fields, $actions, $validator);

        return $form;
    }

    public function doObjectEdit($data, $form) {
        $objectID = $data['ObjectID'];

        $single = $this->getObjectsList()->filter(array(
                    'ID' => $objectID
                ))->first();


        foreach ($data as $key => $value) {
            $single->$key = $value;
        }

        $single->write();
        return $this->owner->redirectBack();
    }

    protected function showSingle($single) {
        if ($single && $single->canView()) {
            $this->preRenderSingle($single);

            return $this
                            ->customise(array(
                                'Single' => $single,
                                'Title' => $single->Title
                            ))
                            ->renderWith(array('DataObjectPage_Show', 'Page'));
        } else {
            return $this->httpError(404, 'That object could not be found!');
        }
    }

    protected function editSingle($single) {
        if ($single && $single->canEdit()) {
            $this->preRenderSingle($single);

            return $this
                            ->customise(array(
                                'Single' => $single,
                                'Title' => $single->Title
                            ))
                            ->renderWith(array('DataObjectPage_Edit', 'Page'));
        } else {
            return $this->httpError(404, 'That object could not be found!');
        }
    }

    protected function getObjectsList() {
        return DataObject::get('Page');
    }

    public function RichSnippets() {
        $single = $this->getSingle();
        $schema = $single->getObjectRichSnippets();
        $schema['@context'] = "http://schema.org";
        
//        return json_encode($schema, JSON_UNESCAPED_UNICODE);
        return Convert::array2json($schema);
    }

    protected function searchObjects($list, $keywords) {
        return $list->filter(array(
                    'Title:PartialMatch' => $keywords
        ));
    }

    protected function getSingle() {
        $id = $this->getRequest()->param('ID');
        return $this->getObjectsList()->filter(array(
                    'ID' => $id
                ))->first();
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
        $align = $this->isRTL() == 'rtl' ? 'right' : 'left';

        Requirements::customScript(<<<JS
                $(document).ready(function () {
                    $('.imgBox').imgZoom({
                        boxWidth: 500,
                        boxHeight: 400,
                        marginLeft: 5,
                        align: '{$align}',
                        origin: 'data-origin'
                    });
                });
JS
        );
    }

    public function isRTL() {
        return i18n::get_script_direction(i18n::get_locale()) == 'rtl';
    }

}
