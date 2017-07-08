<?php

/**
 * Fetches the name of the current module folder name.
 *
 * @return string
 */
if (!defined('DATAOBJECTPAGE_DIR')) {
    define('DATAOBJECTPAGE_DIR', ltrim(Director::makeRelative(realpath(__DIR__)), DIRECTORY_SEPARATOR));
}