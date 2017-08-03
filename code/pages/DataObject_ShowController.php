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
 * Description of DataObjectShow_Controller
 *
 * @author hudha
 */
class DataObject_ShowController
        extends DataObject_ListController {

    private static $allowed_actions = array(
        'show',
        'handleAction',
    );
    private static $url_handlers = array(
//        '$Map/show/$ID' => 'show',
        '$Action//$ID/$Map' => 'handleAction',
//        '$Action//$ID/$OtherID' => 'handleAction',
    );
    protected $currentID;
    protected $currentRecord;

    public function handleAction($request, $action) {
        $this->currentID = (int) $this->request->param('ID');
        $this->currentClass = $this->mapped_class();
        $this->currentRecord = $this->getViewableRecord();

        if ($this->currentID && !$this->currentRecord) {
            return Security::permissionFailure($this, "You do not have permission to that");
        }
        return parent::handleAction($request, $action);
    }

    public function show() {
        $record = $this->currentRecord;
        return $this
                        ->customise(array(
                            'Record' => $record,
                            'Title' => $record->Title
                        ))
                        ->renderWith(array('DataObject_Show', 'Page'));
    }

    protected final function getRecordID() {
        return $this->getRequest()->param('ID');
    }

    protected final function getRecordActions($record, &$actions) {
        if ($record->hasMethod('canEdit') && $record->canEdit()) {
            $actions->push(
                    FormAction::create('doSave', _t('DataObjectPage.SAVE', 'Save'))
            );
        }

        if ($record->ID && $record->hasMethod('canDelete') && $record->canDelete()) {
            $actions->push(
                    FormAction::create('doDelete', _t('DataObjectPage.DELETE', 'Delete'))
            );
        }
    }

    protected final function getRecordLink($action = 'show', $id = null, $map = null) {
        if (!$id) {
            $id = $this->getRequest()->param('ID');
        }

        if (!$map) {
            $map = $this->getRequest()->param('Map');
        }

        return $this->Link("{$action}/{$id}/{$map}");
    }

    protected final function getRecord() {
        if ($this->currentRecord) {
            return $this->currentRecord;
        }

        $id = $this->currentID;
        $className = $this->currentClass;

        if (is_numeric($id)) {
            $record = DataList::create($className)->byID($id);
//            $record = DataObject::get_by_id($className, $id);
        } else {
            if (!singleton($className)->canCreate()) {
                Security::permissionFailure($this);
            }

            $record = new $className();
        }
        return $record;
    }

    protected final function getViewableRecord() {
        $record = $this->getRecord();

        if (!$record) {
            $this->httpError(403, 'That object could not be found!');
        }

        if ($record && !$record->canView()) {
            Security::permissionFailure($this);
        }

        return $record;
    }

}
