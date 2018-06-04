<?php

namespace HudhaifaS\DOM\Extension;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Member;

/**
 *
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.0, Mar 3, 2017 - 9:33:52 AM
 * @deprecated use composer require hudhaifas/silverstripe-member-ownership
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
