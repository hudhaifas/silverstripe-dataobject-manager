<?php

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
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

    private static $table_name = 'DataObjectPage';
    private static $db = [
        'PageLength' => 'Int',
        'FbAppId' => 'Varchar(100)',
        'TwitterSite' => 'Varchar(100)',
        'DefaultSocialDesc' => 'Varchar(255)',
    ];
    private static $has_one = [
        'DefaultSocialImage' => Image::class
    ];
    private static $defaults = [
        'PageLength' => 36,
    ];
    private static $icon = "hudhaifas/silverstripe-dataobject-manager: res/images/wrap.png";

    public function canCreate($member = null, $context = []) {
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
