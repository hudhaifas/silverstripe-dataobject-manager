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
        extends Page_Controller {

    private static $allowed_actions = array(
        'show',
    );
    private static $url_handlers = array(
        'show/$ID/$Map' => 'show',
    );
    private static $dataobjects_map = array();
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

    public function show() {
        $record = $this->getViewableRecord();
        return $this
                        ->customise(array(
                            'Record' => $record,
                            'Title' => $record->Title
                        ))
                        ->renderWith(array('DataObject_Show', 'Page'));
    }

    protected final function getRecord($id, $className) {
        if (is_numeric($id)) {
            $record = DataObject::get_by_id($className, $id);
        } else {
            if (!singleton($className)->canCreate()) {
                Security::permissionFailure($this);
            }

            $record = new $className();
        }
        return $record;
    }

    protected final function getViewableRecord() {
        $className = $this->getRecordClass();
        if (!$className) {
            return null;
        }

        $id = $this->getRecordID();

        $record = $this->getRecord($id, $className);

        if (!$record) {
            $this->httpError(403, 'That object could not be found!');
        }

        if ($record && !$record->canView()) {
            Security::permissionFailure($this);
        }

        return $record;
    }

    protected final function getRecordClass() {
        $map = $this->getRequest()->param('Map');

        $dataobjects = $this->config()->dataobjects_map;
        if (!$dataobjects || !$map || !isset($dataobjects[$map]) || !$dataobjects[$map]) {
//            $this->httpError(403, 'That object could not be found!');
            return null;
        }

        return $dataobjects[$map];
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

}
