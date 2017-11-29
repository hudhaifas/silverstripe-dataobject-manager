<?php

/*
 * MIT License
 *  
 * Copyright (c) 2016 Hudhaifa Shatnawi
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 *
 * @author Hudhaifa Shatnawi <hudhaifa.shatnawi@gmail.com>
 * @version 1.0, Aug 7, 2017 - 11:32:11 PM
 */
class DataObject_PrivicyExtension
        extends DataExtension {

    private static $db = array(
        // Permession Level
        "CanViewType" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers', 'Anyone')",
        "CanEditType" => "Enum('LoggedInUsers, OnlyTheseUsers', 'OnlyTheseUsers')",
    );
    private static $defaults = array(
        "CanViewType" => "Anyone",
        "CanEditType" => "OnlyTheseUsers"
    );
    private static $cache_permissions = array();

    public function updateFieldLabels(&$labels) {
        // Privacy
        $labels['CanViewType'] = _t('DataObjectExtension.CAN_VIEW_TYPE', 'Who can view this person');
        $labels['CanEditType'] = _t('DataObjectExtension.CAN_EDIT_TYPE', 'Who can edit this person');
    }

    public function updateCMSFields(FieldList $fields) {
        $this->owner->getPrivacyFields($fields);
    }

    public function getPrivacyFields(FieldList $fields) {
        // Prepare groups and members lists
        $groupsMap = array();
        foreach (Group::get() as $group) {
            // Listboxfield values are escaped, use ASCII char instead of &raquo;
            $groupsMap[$group->ID] = $group->getBreadcrumbs(' > ');
        }
        asort($groupsMap);

        $membersMap = array();
        foreach (Member::get() as $member) {
            // Listboxfield values are escaped, use ASCII char instead of &raquo;
            $membersMap[$member->ID] = $member->getTitle();
        }
        asort($membersMap);

        // Prepare Options
        $viewersOptionsSource = array(
            "Anyone" => _t('DataObjectExtension.ACCESSANYONE', "Anyone"),
            "LoggedInUsers" => _t('DataObjectExtension.ACCESSLOGGEDIN', "All Logged-in users"),
            "OnlyTheseUsers" => _t('DataObjectExtension.ACCESSONLYTHESE', "Only these people (choose from list)")
        );

        $editorsOptionsSource = array(
            "LoggedInUsers" => _t('DataObjectExtension.ACCESSLOGGEDIN', "All Logged-in users"),
            "OnlyTheseUsers" => _t('DataObjectExtension.ACCESSONLYTHESE', "Only these people (choose from list)")
        );

        // Remove existing fields
        $fields->removeFieldFromTab('Root.Main', 'CanViewType');
        $fields->removeFieldFromTab('Root.ViewerGroups', 'ViewerGroups');
        $fields->removeFieldFromTab('Root', 'ViewerGroups');
        $fields->removeFieldFromTab('Root.ViewerMembers', 'ViewerMembers');
        $fields->removeFieldFromTab('Root', 'ViewerMembers');

        $fields->removeFieldFromTab('Root.Main', 'CanViewType');
        $fields->removeFieldFromTab('Root.EditorGroups', 'EditorGroups');
        $fields->removeFieldFromTab('Root', 'EditorGroups');
        $fields->removeFieldFromTab('Root.EditorMembers', 'EditorMembers');
        $fields->removeFieldFromTab('Root', 'EditorMembers');

        // Prepare Privacy tab
        $privacyTab = new Tab('PrivacyTab', _t('DataObjectExtension.PRIVACY', 'Privacy'));
        $fields->insertAfter('Main', $privacyTab);

        $fields->addFieldsToTab('Root.PrivacyTab', array(
            OptionsetField::create(
                    "CanViewType", _t('DataObjectExtension.CAN_VIEW_TYPE', 'Who can view this person?')
            )->setSource($viewersOptionsSource), //
            ListboxField::create("ViewerGroups", _t('DataObjectExtension.VIEWER_GROUPS', "Viewer Groups"))
                    ->setMultiple(true)
                    ->setSource($groupsMap)
                    ->setAttribute('data-placeholder', _t('DataObjectExtension.GROUP_PLACEHOLDER', 'Click to select group')), //
            ListboxField::create("ViewerMembers", _t('DataObjectExtension.VIEWER_MEMBERS', "Viewer Users"))
                    ->setMultiple(true)
                    ->setSource($membersMap)
                    ->setAttribute('data-placeholder', _t('DataObjectExtension.MEMBER_PLACEHOLDER', 'Click to select user')), //
            OptionsetField::create(
                    "CanEditType", _t('DataObjectExtension.CAN_EDIT_TYPE', 'Who can edit this person?')
            )->setSource($editorsOptionsSource), //
            ListboxField::create("EditorGroups", _t('DataObjectExtension.EDITOR_GROUPS', "Editor Groups"))
                    ->setMultiple(true)
                    ->setSource($groupsMap)
                    ->setAttribute('data-placeholder', _t('DataObjectExtension.GROUP_PLACEHOLDER', 'Click to select group')), //
            ListboxField::create("EditorMembers", _t('DataObjectExtension.EDITOR_MEMBERS', "Editor Users"))
                    ->setMultiple(true)
                    ->setSource($membersMap)
                    ->setAttribute('data-placeholder', _t('DataObjectExtension.MEMBER_PLACEHOLDER', 'Click to select user'))
        ));
    }

    /// Permissions ///
    public function canCreate($member = null) {
        if (!$this->owner->isCreatable()) {
            return false;
        }

        if (!$member) {
            $member = Member::currentUserID();
        }

        if ($member && is_numeric($member)) {
            $member = DataObject::get_by_id('Member', $member);
        }

        $cachedPermission = self::cache_permission_check('create', $member, $this->owner->ID);
        if (isset($cachedPermission)) {
            return $cachedPermission;
        }

        if ($member && Permission::checkMember($member, "ADMIN")) {
            return true;
        }

        $extended = $this->owner->extendedCan('canCreateLocations', $member);
        if ($extended !== null) {
            return $extended;
        }

        return false;
    }

    public function canView($member = false) {
        if (!$member) {
            $member = Member::currentUserID();
        }

        if ($member && is_numeric($member)) {
            $member = DataObject::get_by_id('Member', $member);
        }

        $cachedPermission = self::cache_permission_check('view', $member, $this->owner->ID);
        if (isset($cachedPermission)) {
            return $cachedPermission;
        }

        if ($this->owner->canEdit($member)) {
            return self::cache_permission_check('view', $member, $this->owner->ID, true);
        }

        $extended = $this->owner->extendedCan('canViewLocations', $member);
        if ($extended !== null) {
            return self::cache_permission_check('view', $member, $this->owner->ID, $extended);
        }

        if (!$this->owner->CanViewType || $this->owner->CanViewType == 'Anyone') {
            return self::cache_permission_check('view', $member, $this->owner->ID, true);
        }

        // check for any logged-in users
        if ($this->owner->CanViewType === 'LoggedInUsers' && $member) {
            return self::cache_permission_check('view', $member, $this->owner->ID, true);
        }

        // check for specific groups && users
        if ($this->owner->CanViewType === 'OnlyTheseUsers' && $member && ($member->inGroups($this->owner->ViewerGroups()) || $this->owner->ViewerMembers()->byID($member->ID))) {
            return self::cache_permission_check('view', $member, $this->owner->ID, true);
        }

        return self::cache_permission_check('view', $member, $this->owner->ID, false);
    }

    public function canDelete($member = false) {
        if (!$member) {
            $member = Member::currentUserID();
        }

        if ($member && is_numeric($member)) {
            $member = DataObject::get_by_id('Member', $member);
        }

        $cachedPermission = self::cache_permission_check('delete', $member, $this->owner->ID);
        if (isset($cachedPermission)) {
            return $cachedPermission;
        }

        if ($member && Permission::checkMember($member, "ADMIN")) {
            return true;
        }

        $extended = $this->owner->extendedCan('canDeleteLocations', $member);
        if ($extended !== null) {
            return $extended;
        }

        return false;
    }

    public function canEdit($member = false) {
        if (!$member) {
            $member = Member::currentUserID();
        }

        if ($member && is_numeric($member)) {
            $member = DataObject::get_by_id('Member', $member);
        }

        $cachedPermission = self::cache_permission_check('edit', $member, $this->owner->ID);
        if (isset($cachedPermission)) {
            return $cachedPermission;
        }

        if ($member && Permission::checkMember($member, "ADMIN")) {
            return self::cache_permission_check('edit', $member, $this->owner->ID, true);
        }

        if ($member && $this->owner->hasMethod('CreatedBy') && $member == $this->owner->CreatedBy()) {
            return self::cache_permission_check('edit', $member, $this->owner->ID, true);
        }

        $extended = $this->owner->extendedCan('canEditLocations', $member);
        if ($extended !== null) {
            return self::cache_permission_check('edit', $member, $this->owner->ID, $extended);
        }

        // check for any logged-in users with CMS access
        if ($this->owner->CanEditType === 'LoggedInUsers' && Permission::checkMember($member, $this->owner->config()->required_permission)) {
            return self::cache_permission_check('edit', $member, $this->owner->ID, true);
        }

        // check for specific groups
        if ($this->owner->CanEditType === 'OnlyTheseUsers' && $member && ($member->inGroups($this->owner->EditorGroups()) || $this->owner->EditorMembers()->byID($member->ID))) {
            return self::cache_permission_check('edit', $member, $this->owner->ID, true);
        }

        return self::cache_permission_check('edit', $member, $this->owner->ID, false);
    }

    public function isCreatable() {
        return false;
    }

    public static function cache_permission_check($typeField, $member, $personID, $result = null) {
        if (!$member) {
            $member = Member::currentUserID();
        }

        if ($member && is_numeric($member)) {
            $member = DataObject::get_by_id('Member', $member);
        }

        $memberID = $member ? $member->ID : '?';

        // This is the name used on the permission cache
        // converts something like 'CanEditType' to 'edit'.
        $cacheKey = strtolower($typeField) . "-$memberID-$personID";

        if (isset(self::$cache_permissions[$cacheKey])) {
            $cachedValues = self::$cache_permissions[$cacheKey];
            return $cachedValues;
        }

        self::$cache_permissions[$cacheKey] = $result;

        return self::$cache_permissions[$cacheKey];
    }

}
