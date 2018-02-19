<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FrontendUploadField
 *
 * @author hudha
 */
class FrontendUploadField
        extends UploadField {

    public function Field($properties = array()) {
//        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
        Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/ssui.core.js');
        Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');

        Requirements::combine_files('uploadfield.js', array(
            // @todo jquery templates is a project no longer maintained and should be retired at some point.
            THIRDPARTY_DIR . '/javascript-templates/tmpl.js',
            THIRDPARTY_DIR . '/javascript-loadimage/load-image.js',
            THIRDPARTY_DIR . '/jquery-fileupload/jquery.iframe-transport.js',
            THIRDPARTY_DIR . '/jquery-fileupload/cors/jquery.xdr-transport.js',
            THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload.js',
            THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload-ui.js',
            FRAMEWORK_DIR . '/javascript/UploadField_uploadtemplate.js',
            FRAMEWORK_DIR . '/javascript/UploadField_downloadtemplate.js',
            FRAMEWORK_DIR . '/javascript/UploadField.js',
        ));
        Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css'); // TODO hmmm, remove it?
        Requirements::css(FRAMEWORK_DIR . '/css/UploadField.css');

        // Calculated config as per jquery.fileupload-ui.js
        $allowedMaxFileNumber = $this->getAllowedMaxFileNumber();
        $config = array(
            'url' => $this->Link('upload'),
            'urlSelectDialog' => $this->Link('select'),
            'urlAttach' => $this->Link('attach'),
            'urlFileExists' => $this->link('fileexists'),
            'acceptFileTypes' => '.+$',
            // Fileupload treats maxNumberOfFiles as the max number of _additional_ items allowed
            'maxNumberOfFiles' => $allowedMaxFileNumber ? ($allowedMaxFileNumber - count($this->getItemIDs())) : null,
            'replaceFile' => $this->getUpload()->getReplaceFile(),
        );

        // Validation: File extensions
        if ($allowedExtensions = $this->getAllowedExtensions()) {
            $config['acceptFileTypes'] = '(\.|\/)(' . implode('|', $allowedExtensions) . ')$';
            $config['errorMessages']['acceptFileTypes'] = _t(
                    'File.INVALIDEXTENSIONSHORT', 'Extension is not allowed'
            );
        }

        // Validation: File size
        if ($allowedMaxFileSize = $this->getValidator()->getAllowedMaxFileSize()) {
            $config['maxFileSize'] = $allowedMaxFileSize;
            $config['errorMessages']['maxFileSize'] = _t(
                    'File.TOOLARGESHORT', 'File size exceeds {size}', array('size' => File::format_size($config['maxFileSize']))
            );
        }

        // Validation: Number of files
        if ($allowedMaxFileNumber) {
            if ($allowedMaxFileNumber > 1) {
                $config['errorMessages']['maxNumberOfFiles'] = _t(
                        'UploadField.MAXNUMBEROFFILESSHORT', 'Can only upload {count} files', array('count' => $allowedMaxFileNumber)
                );
            } else {
                $config['errorMessages']['maxNumberOfFiles'] = _t(
                        'UploadField.MAXNUMBEROFFILESONE', 'Can only upload one file'
                );
            }
        }

        // add overwrite warning error message to the config object sent to Javascript
        if ($this->getOverwriteWarning()) {
            $config['errorMessages']['overwriteWarning'] = _t('UploadField.OVERWRITEWARNING', 'File with the same name already exists');
        }

        $mergedConfig = array_merge($config, $this->ufConfig);
        return parent::Field(array(
                    'configString' => str_replace('"', "&quot;", Convert::raw2json($mergedConfig)),
                    'config' => new ArrayData($mergedConfig),
                    'multiple' => $allowedMaxFileNumber !== 1
        ));
    }

}
