<?php

namespace HudhaifaS\DOM\Model;

/**
 *
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.0, Jan 23, 2017 - 7:56:34 AM
 */
interface ManageableDataObject {

    public function getObjectItem();

    public function getObjectTitle();

    public function getObjectLink();

    public function getObjectEditLink();

    public function getObjectEditableImageName();

    public function getObjectImage();

    public function getObjectDefaultImage();

    public function getObjectSummary();

    public function getObjectNav();

    public function getObjectRelated();

    public function getObjectTabs();

    /**
     * Return the HTML content of a specific tab.
     *
     * @param string $tabKey
     * @param int $page (optional) — for paginated tabs
     * @return string HTML content
     */
    public function getObjectTabContent($tabKey, $page = 1);

    public function canPublicView();
}
