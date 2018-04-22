<?php

namespace HudhaifaS\DOM\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

/**
 *
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.0, Oct 13, 2017 - 9:15:12 PM
 */
class DataObject_ViewsCounterExtension
        extends DataExtension {

    private static $db = [
        'ViewsCount' => 'Int',
    ];

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
