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
 * @version 1.0, Jul 27, 2017 - 8:36:23 AM
 */
class DataObject_ListController
        extends Page_Controller {

    private static $allowed_actions = array(
        'handleAction',
    );
    private static $url_handlers = array(
        '$Map' => 'index',
        '$Action//$ID/$OtherID' => 'handleAction',
    );
    private static $dataobjects_map = array();
    protected $currentClass = false;
    protected $isRTL = false;

    public function init() {
        parent::init();

        $this->isRTL = i18n::get_script_direction(i18n::get_locale()) == 'rtl';

        Requirements::css(DATAOBJECT_MANAGER_DIR . "/css/dataobject.css");
        if ($this->isRTL) {
            Requirements::css(DATAOBJECT_MANAGER_DIR . "/css/dataobject-rtl.css");
        }

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
                )
//                ->setPageLength($this->PageLength)
                ->setPageLength(36)
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

//        return $data;
        return $this
                        ->customise($data)
                        ->renderWith(array('DataObject_List', 'Page'));
    }

    public function handleAction($request, $action) {
        $this->currentClass = $this->mapped_class();

        if (!$this->currentClass) {
            return $this->httpError(404, 'That object could not be found!');
        }

        return parent::handleAction($request, $action);
    }

    public function ObjectSearchForm() {
        $data = $this->request->getVars();
        $map = $this->getRequest()->param('Map');

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
                ->setFormAction($this->Link($map))
                ->setTemplate('Form_ObjectSearch')
                ->loadDataFrom($data);

        return $form;
    }

    protected function getObjectsList() {
        $className = $this->mapped_class();
        if (!$className) {
            return null;
        }
        return DataObject::get($className);
    }

    protected function searchObjects($list, $keywords) {
        $search_filters = Config::inst()->forClass($this->mapped_class())->search_filters;

        $filters = array();
        foreach ($search_filters as $field => $filter) {
            $filter = "{$field}:{$filter}";
            $filters[$filter] = $keywords;
        }

        return $list->filterAny($filters);
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

    protected final function mapped_class() {
        $map = $this->getRequest()->param('Map');

        $dataobjects = $this->config()->dataobjects_map;
        if (!$dataobjects || !$map || !isset($dataobjects[$map]) || !$dataobjects[$map]) {
//            $this->httpError(403, 'That object could not be found!');
            return null;
        }

        return $dataobjects[$map];
    }

    protected final function mapped_config() {
        return Config::inst()->forClass($this->mapped_class());
    }

}
