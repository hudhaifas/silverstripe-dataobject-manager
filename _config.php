<?php

/**
 * Fetches the name of the current module folder name.
 *
 * @return string
 */
if (!defined('DATAOBJECT_MANAGER_DIR')) {
    define('DATAOBJECT_MANAGER_DIR', ltrim(Director::makeRelative(realpath(__DIR__)), DIRECTORY_SEPARATOR));
}