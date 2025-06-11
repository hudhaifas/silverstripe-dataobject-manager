<?php

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
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

        $fields->addFieldToTab('Root.Main',
            NumericField::create('PageLength', _t('DataObjectPage.PAGE_LENGTH', 'Page Length'),
                $this->PageLength));
        $fields->addFieldToTab('Root.OpenGraph',
            TextField::create('FbAppId', _t('DataObjectPage.FB_APP_ID', 'Facebook App ID'), $this->FbAppId));
        $fields->addFieldToTab('Root.OpenGraph',
            TextField::create('TwitterSite', _t('DataObjectPage.TWITTER_SITE', 'Twitter Site'),
                $this->TwitterSite));
        $fields->addFieldToTab('Root.OpenGraph',
            TextareaField::create('DefaultSocialDesc',
                _t('DataObjectPage.DEFAULT_SOCIAL_DESC', 'Default Social Description'),
                $this->DefaultSocialDesc));
        $fields->addFieldToTab('Root.OpenGraph',
            $uploadField = UploadField::create(
                'DefaultSocialImage', //
                _t('DataObjectPage.DEFAULT_SOCIAL_IMAGE', 'Default Social Image')
            ));

        return $fields;
    }

    /**
     * Returns the localized link for the current DataObject view.
     *
     * If Fluent is installed and a locale is provided, it will generate a localized URL
     * to the currently viewed object's 'show' action using the provided locale.
     * If no ID is present or Fluent is not installed, it falls back to the default page link.
     *
     * This method ensures compatibility with both Fluent-enabled and non-Fluent installations.
     *
     * @param string $locale The target locale (e.g., 'en', 'fr', 'ar')
     * @return string The localized URL to the object view or fallback page URL
     */
    public function LocaleLink($locale)
    {
        $id = Controller::curr()->getRequest()->param('ID');
        // Only handle Fluent if the class exists and locale is defined

        if (!class_exists(\TractorCow\Fluent\State\FluentState::class) || !$locale || !$id) {
            return $this->Link();
        }

        // Switch locale temporarily using Fluent
        return \TractorCow\Fluent\State\FluentState::singleton()->withState(function ($state) use ($locale, $id) {
            $state->setLocale($locale);
            return $this->Link("show/{$id}");
        });
    }
}
