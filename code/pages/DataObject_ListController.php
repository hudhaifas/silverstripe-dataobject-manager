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

    public function ObjectSearchForm() {
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

    protected function getObjectsList() {
        return DataObject::get('Page');
    }

    protected function searchObjects($list, $keywords) {
        return $list->filter(array(
                    'Title:PartialMatch' => $keywords
        ));
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

}
