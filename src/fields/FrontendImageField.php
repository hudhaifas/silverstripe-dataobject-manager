<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.0, Apr 20, 2018 - 5:38:24 PM
 */
class FrontendImageField
        extends FrontendFileField {

    /**
     * @var array Collection of extensions.
     * Extension-names are treated case-insensitive.
     *
     * Example:
     * <code>
     *    array("jpg","GIF")
     * </code>
     */
    public $allowedExtensions = array("jpg", "GIF", "png");

    protected function getAcceptFileTypes() {
        $this->getValidator()->setAllowedExtensions($this->allowedExtensions);

        parent::getAcceptFileTypes();
    }

}
