<?php

namespace HudhaifaS\DOM;

use SilverStripe\Forms\FileField;

/**
 * Description of FrontendUploadField
 *
 * @author hudha
 */
class FrontendFileField
        extends FileField {

    public $fileUrl;

    /**
     * @param array $properties
     * @return string
     */
    public function Field($properties = array()) {
        $properties = array_merge($properties, array(
            'FileURL' => $this->fileUrl
        ));

        return parent::Field($properties);
    }

    public function setValue($value, $data = null) {
        if ($value && !is_array($value)) {
            $this->fileUrl = $value->Link();
        }
        parent::setValue($value, $data);
    }

}
