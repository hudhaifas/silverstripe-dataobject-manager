<?php

namespace HudhaifaS\DOM\Model;

/**
 * This interface marks a DataObject as searchable and discoverable by search engines and social media 
 * and provide the proper data about the object.
 * 
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.0, Nov 30, 2017 - 2:50:09 PM
 */
interface DiscoverableDataObject {

    /**
     * Structured data markup added to the existing HTML, which in turn allow search engines to better understand 
     * what information is contained on each dataobject page.
     */
    public function getObjectMarkup();

    /**
     * A one to two sentence description of the object, shows below the link title on social (Facebook and Twitter), 
     * and meta description tag, also appended to the Rich Snippet markup. 
     */
    public function getObjectDescription();
}
