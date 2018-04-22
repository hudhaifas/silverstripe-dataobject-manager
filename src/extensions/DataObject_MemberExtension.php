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

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Member;

/**
 *
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.0, Mar 3, 2017 - 9:33:52 AM
 */
class DataObject_MemberExtension
        extends DataExtension {

    private static $has_one = [
        'CreatedBy' => Member::class,
        'EditedBy' => Member::class,
    ];

    public function updateSummaryFields(&$fields) {
        $fields['CreatedBy.Title'] = _t('DataObjectExtension.CREATED_BY', 'Created By');
        $fields['EditedBy.Title'] = _t('DataObjectExtension.EDITED_BY', 'Edited By');
    }

    public function updateFieldLabels(&$labels) {
        $labels['CreatedBy'] = _t('DataObjectExtension.CREATED_BY', 'Created By');
        $labels['CreatedBy.Title'] = _t('DataObjectExtension.CREATED_BY', 'Created By');

        $labels['EditedBy'] = _t('DataObjectExtension.EDITED_BY', 'Edited By');
        $labels['EditedBy.Title'] = _t('DataObjectExtension.EDITED_BY', 'Edited By');
    }

    public function updateCMSFields(FieldList $fields) {
        $fields->removeFieldFromTab('Root.Main', 'CreatedByID');
        $fields->removeFieldFromTab('Root.Main', 'EditedByID');
    }

    public function onBeforeWrite() {
        if (Member::currentUserID()) {
            if (!$this->owner->CreatedByID) {
                $this->owner->CreatedByID = Member::currentUserID();
            }

            $this->owner->EditedByID = Member::currentUserID();
        }
    }

}
