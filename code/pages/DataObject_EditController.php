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
 * Description of DataObject_Controller
 *
 * @author hudha
 */
class DataObject_EditController
        extends DataObject_ShowController {

    private static $allowed_actions = array(
        'edit',
        'ObjectEditForm',
        'doSave',
        'doDelete',
    );
    private static $url_handlers = array(
        '$Map/edit/$ID' => 'edit'
    );
    // TinyMCE Cloud API Key
    private static $tinymce_key = '';

    public function init() {
        parent::init();

        $key = $this->config()->tinymce_key;

        Requirements::javascript("https://cloud.tinymce.com/stable/tinymce.min.js?apiKey={$key}");
        Requirements::customScript(<<<JS
            tinymce.init({
                selector: 'textarea[tinymce=true]',
                width: "100%"
            });
JS
        );
    }

    public function edit() {
        $form = $this->ObjectEditForm();
        $record = $this->getViewableRecord();

        return $this
                        ->customise(array(
                            'Record' => $record,
                            'Title' => $record->Title,
                            'ObjectForm' => $form
                        ))
                        ->renderWith(array('DataObject_Edit', 'Page'));
    }

    public function ObjectEditForm() {
        $className = $this->mapped_class();
        if (!$className) {
            return $this->getEmptyForm();
        }

        $id = $this->getRecordID();

        $record = $this->getRecord();

        if (!$record) {
            $this->httpError(403, 'That object could not be found!');
        }

        if ($record && !$record->canView()) {
            Security::permissionFailure($this);
        }

        // Create action
        $actions = new FieldList();
        $this->getRecordActions($record, $actions);

        // Create fields          
        $fields = new FieldList();
        $fields->push(HiddenField::create('ObjectID', 'ObjectID', $id));
        $fields->push(HiddenField::create('ObjectClass', 'ObjectClass', $record->ClassName));

        $this->getRecordFields($record, $fields);

        // Create Validators
        $validator = new RequiredFields();

        $form = Form::create($this, 'ObjectEditForm', $fields, $actions, $validator);

        return $form;
    }

    public function doSave($data, $form) {
        // Existing or new record?
        $id = $data['ObjectID'];
        $className = $data['ObjectClass'];

        $record = $this->getRecord($id, $className);

        if ($record && !$record->canEdit()) {
            Security::permissionFailure($this);
        }

        // save form data into record
//        $form->saveInto($record, true);
        foreach ($data as $key => $value) {
            $record->$key = $value;
        }

        $record->write();

//        return $this->redirect($this->getRecordLink('show', $record->ID));
        return $this->owner->redirectBack();
    }

    public function doDelete($data, $form) {
        // Existing or new record?
        $id = $data['ObjectID'];
        $className = $data['ObjectClass'];

        $record = DataObject::get_by_id($className, $id);
        if (!$record || !$record->ID) {
            $this->httpError(404, "Bad record ID #" . (int) $id);
        }

        if ($record && !$record->canDelete()) {
            Security::permissionFailure();
        }


        $record->delete();

        return $this->owner->redirect($this->Link());
    }

    protected final function getEmptyForm() {
        // Create action
        $actions = new FieldList();
        // Create fields          
        $fields = new FieldList();

        // Create Validators
        $validator = new RequiredFields();

        $form = Form::create($this, 'ObjectEditForm', $fields, $actions, $validator);

        return $form;
    }

    protected final function getRecordFields($record, &$fields) {
        if ($record) {
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

                if ($fieldObject instanceof DateField) {
                    $fieldObject->setConfig('showcalendar', true);
//                    $fieldObject->setConfig('showdropdown', true);
//                    $fieldObject->setConfig('dateformat', 'dd-MM-yyyy');
                }
                $fields->push($fieldObject);
            }
        }
    }

    protected final function restrictFields($record) {
        return $record->config()->restrictFields;
    }

}
