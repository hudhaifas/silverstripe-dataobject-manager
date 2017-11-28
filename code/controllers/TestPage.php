<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TestPage
 *
 * @author hudha
 */
class TestPage
        extends Page {
    //put your code here
}

class TestPage_Controller
        extends DataObject_EditController {

    private static $dataobjects_map = array(
        'a' => 'EtymologyWord',
        'b' => 'Male',
    );

}
