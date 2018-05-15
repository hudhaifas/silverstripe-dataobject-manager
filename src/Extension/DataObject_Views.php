<?php

namespace HudhaifaS\DOM\Extension;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Security;

/**
 *
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.0, May 14, 2018 - 10:10:11 PM
 */
class DataObject_Views
        extends DataExtension {

    private static $db = [
        'NumView' => 'Int',
    ];
    private static $restrict_fields = [
        'NumView'
    ];

    public function updateSummaryFields(&$fields) {
        $fields['NumView'] = _t('DataObjectExtension.VIEWS', 'Views');
    }

    public function updateFieldLabels(&$labels) {
        $labels['NumView'] = _t('DataObjectExtension.VIEWS', 'Views');
    }

    public function updateCMSFields(FieldList $fields) {
        $fields->removeFieldFromTab('Root.Main', 'NumView');
    }

    public function addView() {
        // Increament the views counter
        $this->owner->NumView++;
        // Finds the specific class that directly holds the given field and returns the table
        $table = DataObject::getSchema()->tableForField($this->owner->ClassName, 'NumView');

        if (Security::database_is_ready()) {
            DB::prepared_query(
                    sprintf('UPDATE "%s" SET "NumView" = ? WHERE "ID" = ?', $table), [
                $this->owner->NumView,
                $this->owner->ID
            ]);
        }
    }

}
