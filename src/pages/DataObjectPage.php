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

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;

/**
 *
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.5, Jan 22, 2017 - 6:20:48 PM
 */
class DataObjectPage
        extends Page {

    private static $db = array(
        'PageLength' => 'Int',
        'FbAppId' => 'Varchar(100)',
        'TwitterSite' => 'Varchar(100)',
        'DefaultSocialDesc' => 'Varchar(255)',
    );
    private static $has_one = array(
        'DefaultSocialImage' => 'Image'
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

        $fields->addFieldToTab('Root.Main', NumericField::create('PageLength', _t('DataObjectPage.PAGE_LENGTH', 'Page Length'), $this->PageLength));
        $fields->addFieldToTab('Root.Main', TextField::create('FbAppId', _t('DataObjectPage.FB_APP_ID', 'Facebook App ID'), $this->FbAppId));
        $fields->addFieldToTab('Root.Main', TextField::create('TwitterSite', _t('DataObjectPage.TWITTER_SITE', 'Twitter Site'), $this->TwitterSite));
        $fields->addFieldToTab('Root.Main', TextareaField::create('DefaultSocialDesc', _t('DataObjectPage.DEFAULT_SOCIAL_DESC', 'Default Social Description'), $this->DefaultSocialDesc));
        $fields->addFieldToTab('Root.Main', $uploadField = UploadField::create(
                        'DefaultSocialImage', //
                        _t('DataObjectPage.DEFAULT_SOCIAL_IMAGE', 'Default Social Image')
        ));

        return $fields;
    }

}
