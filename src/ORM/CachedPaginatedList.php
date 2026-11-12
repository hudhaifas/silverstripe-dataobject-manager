<?php

namespace HudhaifaS\DOM\ORM;

use ArrayIterator;
use Iterator;
use IteratorIterator;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\PaginatedList;

/**
 * A PaginatedList that queries its page of a DataList once, however often it is looped.
 *
 * The SS 5.4 template engine loops a list twice: once for the items and again to count them. On a PaginatedList each
 * loop limits a clone of the DataList, so the page query runs twice. This list keeps the page it loaded and reuses
 * it until the page start, the page length, the limiting or the list changes. Everything else (count, exists, first,
 * the total) still describes the whole list, exactly as on PaginatedList.
 *
 * An in-memory list is not cached: looping it again costs no query, and it could change between loops.
 */
class CachedPaginatedList extends PaginatedList {

    private $pageKey;
    private $pageItems;

    public function getIterator(): Iterator {
        if (!$this->getList() instanceof DataList) {
            return parent::getIterator();
        }

        $key = [spl_object_id($this->getList()), $this->getPageStart(), $this->getPageLength(), $this->getLimitItems()];
        if ($this->pageItems === null || $this->pageKey !== $key) {
            $this->pageItems = iterator_to_array(parent::getIterator(), false);
            $this->pageKey = $key;
        }

        return new IteratorIterator(new ArrayIterator($this->pageItems));
    }

}
