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

/**
 *
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.0, Oct 13, 2017 - 9:15:12 PM
 */
class DataObject_ViewsCounterExtension
        extends DataExtension {

    private static $db = array(
        'ViewsCount' => 'Int',
    );

    public function updateSummaryFields(&$fields) {
        $fields['ViewsCount'] = _t('DataObjectExtension.VIEWS_COUNT', 'Views Count');
    }

    public function updateFieldLabels(&$labels) {
        $labels['ViewsCount'] = _t('DataObjectExtension.VIEWS_COUNT', 'Views Count');
    }

    public function updateCMSFields(FieldList $fields) {
        $fields->removeFieldFromTab('Root.Main', 'ViewsCount');
    }

    public function objectViewd() {
        $this->owner->ViewsCount = $this->owner->ViewsCount + 1;
        $this->owner->write();
    }

}
