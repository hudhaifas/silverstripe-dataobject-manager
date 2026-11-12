<?php

/**
 * What each DataObjectPageController function returns (or throws) for a given input.
 *
 * These pin the controller's current behaviour, quirks included, so a refactor that passes them changes nothing a
 * caller can see. A test marked "Current behaviour" pins something that is arguably a bug: fixing it should be a
 * deliberate change to the test, not a side effect.
 */

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\DateField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\View\ArrayData;
use HudhaifaS\Forms\FrontendImageField;
use HudhaifaS\Forms\FrontendRichTextField;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;

/**
 * A controller listing $objects, with $params as the route params of the current request
 */
function controller($objects = null, $params = [], $getVars = [], $page = null) {
    $controller = new TestController($page);
    $controller->objects = $objects ?? objects();
    $controller->setRequest((new HTTPRequest('GET', '', $getVars))->setRouteParams($params));
    return $controller;
}

function request($getVars = [], $ajax = false) {
    return (new HTTPRequest('GET', '', $getVars))->setAjax($ajax);
}

/**
 * $count objects with IDs 1..$count; the IDs in $hidden aren't publicly viewable
 */
function numbered($count, $hidden = []) {
    $list = [];
    for ($id = 1; $id <= $count; $id++) {
        $list[] = new FakeObject($id, null, ['publicView' => !in_array($id, $hidden)]);
    }

    return new ArrayList($list);
}

function fieldNames($fields) {
    return array_map(function ($field) {
        return $field->getName();
    }, items($fields));
}

/// index

function test_index_returns_results_count_and_seconds() {
    $data = controller(numbered(3))->index(request());

    assertSame(['Results', 'Count', 'Seconds'], array_keys($data));
    assertInstanceOf(PaginatedList::class, $data['Results']);
    assertSame(3, $data['Count']);
    assertTrue(is_float($data['Seconds']) && $data['Seconds'] >= 0, 'Seconds is a non-negative float');
}

function test_index_leaves_out_objects_the_visitor_cant_view() {
    $data = controller(numbered(6, [2, 5]))->index(request());

    assertSame([1, 3, 4, 6], ids($data['Results']));
    assertSame(4, $data['Count']);
    assertSame(4, $data['Results']->getTotalItems());
}

function test_index_uses_canView_for_objects_without_canPublicView() {
    $list = objects(new PlainObject(1, true), new PlainObject(2, false), new PlainObject(3, true));

    $data = controller($list)->index(request());

    assertSame([1, 3], ids($data['Results']));
    assertSame(2, $data['Count']);
}

function test_index_pages_by_the_page_length_and_start_var() {
    $page = new Page();
    $page->PageLength = 2;
    $controller = controller(numbered(7, [1]), [], [], $page);

    $first = $controller->index(request());
    $third = $controller->index(request(['start' => 4]));

    assertSame(2, $first['Results']->getPageLength());
    assertSame('start', $first['Results']->getPaginationGetVar());
    assertSame([2, 3], ids($first['Results']));
    assertSame([6, 7], ids($third['Results']));
    assertSame(6, $third['Count']);
    assertSame(6, $third['Results']->getTotalItems());
}

function test_index_results_count_and_first_describe_every_viewable_object() {
    $page = new Page();
    $page->PageLength = 2;

    $results = controller(numbered(7, [1]), [], [], $page)->index(request(['start' => 4]))['Results'];

    assertSame([6, 7], ids($results), 'the page');
    assertSame(6, $results->count(), 'not the page size');
    assertSame(true, $results->exists());
    assertSame(2, $results->first()->ID, 'not the first on the page');
    assertSame([2, 3, 4, 5, 6, 7], ids($results->getList()));
}

function test_index_refilters_a_paginated_search_result() {
    $controller = new class extends TestController {

        public function searchObjects($list, $keywords) {
            // e.g. a fulltext search engine returning its own paginated list
            return PaginatedList::create($list)->setPageLength(2);
        }

    };
    $controller->objects = numbered(5, [2]);

    $data = $controller->index(request(['q' => 'x']));

    assertSame([1, 3, 4, 5], ids($data['Results']), 'its whole list, paginated again by the page length');
    assertSame(4, $data['Count']);
    assertSame(36, $data['Results']->getPageLength());
}

function test_index_past_the_last_page_is_empty_but_keeps_the_count() {
    $page = new Page();
    $page->PageLength = 2;

    $data = controller(numbered(3), [], [], $page)->index(request(['start' => 10]));

    assertSame([], ids($data['Results']));
    assertSame(3, $data['Count']);
}

function test_index_with_no_page_length_lists_everything() {
    $page = new Page();
    $page->PageLength = 0;

    $data = controller(numbered(5, [3]), [], [], $page)->index(request());

    assertSame([1, 2, 4, 5], ids($data['Results']));
    assertSame(4, $data['Count']);
}

function test_index_searches_titles_when_q_is_given() {
    $list = objects(new FakeObject(1, 'Amman'), new FakeObject(2, 'Irbid'), new FakeObject(3, 'Old Amman', ['publicView' => false]), new FakeObject(4, 'AMMAN east'));

    $data = controller($list)->index(request(['q' => 'amman']));

    assertSame([1, 4], ids($data['Results']));
    assertSame(2, $data['Count']);
}

function test_index_ignores_an_empty_q() {
    $data = controller(numbered(3))->index(request(['q' => '']));

    assertSame([1, 2, 3], ids($data['Results']));
}

function test_index_keeps_the_list_order() {
    $list = objects(new FakeObject(9), new FakeObject(2), new FakeObject(5));

    assertSame([9, 2, 5], ids(controller($list)->index(request())['Results']));
}

function test_index_renders_the_list_include_for_ajax() {
    $rendered = controller(numbered(3, [2]))->index(request([], true));

    assertInstanceOf(Rendered::class, $rendered);
    assertSame('Includes\ObjectsList', $rendered->template);
    assertSame(['Results', 'Count', 'Seconds'], array_keys($rendered->data));
    assertSame([1, 3], ids($rendered->data['Results']));
    assertSame(2, $rendered->data['Count']);
}

function pagedController($list, $pageLength, $class = TestController::class) {
    $page = new Page();
    $page->PageLength = $pageLength;
    $controller = new $class($page);
    $controller->objects = $list;
    $controller->setRequest(new HTTPRequest('GET', ''));
    return $controller;
}

function test_index_on_a_data_list_pages_in_sql_and_checks_only_the_page() {
    // Deliberate change: the count and the pages are the database's, so they include objects the visitor can't view,
    // and a page shows fewer than PageLength objects when some on it are hidden
    $list = numberedData(7, [1, 6]);
    $controller = pagedController($list, 2);

    $first = $controller->index(request());

    assertSame([2], ids($first['Results']), 'object 1 is hidden, so the first page is short');
    assertSame(7, $first['Count']);
    assertSame(7, $first['Results']->getTotalItems());
    assertSame(2, $first['Results']->getPageLength());
    assertSame('start', $first['Results']->getPaginationGetVar());
    assertSame([1, 2], checkedIDs($list));

    $third = $controller->index(request(['start' => 4]));

    assertSame([5], ids($third['Results']));
    assertSame(4, $third['Results']->getPageStart());
    assertSame([1, 2, 5, 6], checkedIDs($list));
}

function test_index_on_a_data_list_past_the_last_page_is_empty_but_keeps_the_count() {
    $list = numberedData(3);

    $data = pagedController($list, 2)->index(request(['start' => 10]));

    assertSame([], ids($data['Results']));
    assertSame(3, $data['Count']);
    assertSame([], checkedIDs($list));
}

function test_index_on_a_data_list_with_no_page_length_checks_every_object() {
    // With nothing to page, every object is loaded anyway, so the count stays exact
    $list = numberedData(5, [3]);

    $data = pagedController($list, 0)->index(request());

    assertSame([1, 2, 4, 5], ids($data['Results']));
    assertSame(4, $data['Count']);
}

function test_index_on_a_data_list_uses_the_controllers_sql_filter() {
    // Deliberate change: a controller that narrows the list to its viewable objects in SQL is asked to, and then
    // nothing is checked in PHP
    $list = numberedData(5, [2, 4]);
    $controller = pagedController($list, 2, SqlFilteredController::class);

    $data = $controller->index(request(['start' => 2]));

    assertSame([5], ids($data['Results']));
    assertSame(3, $data['Count']);
    assertSame(3, $data['Results']->getTotalItems());
    assertSame(1, $controller->filtered);
    assertSame([], checkedIDs($list));
}

function test_index_on_an_always_viewable_data_list_is_unchanged() {
    // A controller whose filterViewable() returns the list as is gets the same pages and count as before
    $list = numberedData(7);
    $controller = pagedController($list, 2, AlwaysViewableController::class);

    $data = $controller->index(request(['start' => 2]));

    assertSame([3, 4], ids($data['Results']));
    assertSame(7, $data['Count']);
    assertSame(7, $data['Results']->getTotalItems());
    assertSame([1, 2, 3, 4, 5, 6, 7], ids($data['Results']->getList()));
}

function test_index_results_give_the_same_page_each_time_they_are_looped() {
    // The template loops Results more than once (SS 5.4 counts the items with a second loop), and every loop sees
    // the same page, whichever branch of index() built it
    foreach ([TestController::class, SqlFilteredController::class, AlwaysViewableController::class] as $class) {
        $results = pagedController(numberedData(9, [4]), 3, $class)->index(request(['start' => 3]))['Results'];

        $first = ids($results);
        assertSame($first, ids($results), $class);
        assertSame($class === TestController::class ? [5, 6] : ($class === SqlFilteredController::class ? [5, 6, 7] : [4, 5, 6]), $first, $class);
        assertTrue($results->exists(), $class);
        assertSame($class === SqlFilteredController::class ? 8 : 9, $results->getTotalItems(), $class);
    }
}

function test_index_results_on_an_empty_page_loop_as_empty() {
    $results = pagedController(numberedData(4), 3, SqlFilteredController::class)->index(request(['start' => 30]))['Results'];

    assertSame([], ids($results));
    assertSame([], ids($results));
    assertTrue($results->exists());
    assertSame(4, $results->getTotalItems());
}

/**
 * The IDs on every page of the index, in order, from start=0 until a page comes back empty
 */
function allPages($controller, $pageLength) {
    $ids = [];
    for ($start = 0; $start < 100; $start += $pageLength) {
        $page = ids($controller->index(request(['start' => $start]))['Results']);
        if (!$page && $start >= $controller->index(request())['Count']) {
            break;
        }
        $ids = array_merge($ids, $page);
    }

    return $ids;
}

function test_index_on_a_data_list_breaks_sort_ties_by_ID_so_pages_neither_repeat_nor_skip() {
    // Deliberate change: the database pages the list, and MySQL may order rows that tie on every sort column
    // differently for each LIMIT, so the index adds the ID as the last sort column
    $list = sortedData(array_fill(0, 10, 'Same'), ['"FakeObject"."IndexedName"' => 'ASC']);

    assertSame(range(1, 10), allPages(pagedController($list, 3), 3));
}

function test_index_with_the_controllers_sql_filter_breaks_sort_ties_by_ID() {
    $list = sortedData(array_fill(0, 10, 'Same'), ['"FakeObject"."IndexedName"' => 'ASC'], [4, 7]);

    assertSame([1, 2, 3, 5, 6, 8, 9, 10], allPages(pagedController($list, 3, SqlFilteredController::class), 3));
}

function test_index_keeps_the_lists_own_sort_ahead_of_the_ID() {
    $list = sortedData(['B', 'A', 'B', 'A', 'C', 'A'], ['"FakeObject"."IndexedName"' => 'DESC']);

    assertSame([5, 1, 3, 2, 4, 6], allPages(pagedController($list, 2, AlwaysViewableController::class), 2));
}

function test_index_keeps_the_direction_of_a_list_already_sorted_by_ID() {
    $qualified = sortedData(array_fill(0, 5, 'Same'), ['"FakeObject"."ID"' => 'DESC']);
    $plain = sortedData(array_fill(0, 5, 'Same'), ['ID' => 'DESC']);

    assertSame([5, 4, 3, 2, 1], allPages(pagedController($qualified, 2), 2));
    assertSame([5, 4, 3, 2, 1], allPages(pagedController($plain, 2), 2));
}

function test_index_renders_a_data_list_page_for_ajax() {
    $rendered = pagedController(numberedData(3, [2]), 2)->index(request([], true));

    assertInstanceOf(Rendered::class, $rendered);
    assertSame(['Results', 'Count', 'Seconds'], array_keys($rendered->data));
    assertSame([1], ids($rendered->data['Results']));
    assertSame(3, $rendered->data['Count']);
}

function test_index_never_asks_the_model_to_filter_the_list() {
    // Filtering a list is the controller's job: without a filterViewable() override the base controller pages in SQL
    // and checks the page, whatever methods the model has
    $list = numberedData(5, [2], SelfFilteringObject::class);

    $data = pagedController($list, 2)->index(request());

    assertSame([1], ids($data['Results']));
    assertSame(5, $data['Count']);
    assertSame(0, SelfFilteringObject::$filtered);
    assertSame([1, 2], checkedIDs($list));
}

function test_filterViewable_returns_null_by_default() {
    $controller = controller(numberedData(3));

    assertNull(invoke($controller, 'filterViewable', numberedData(3)));
    assertNull(invoke($controller, 'filterViewable', numbered(3)));
}

function test_index_on_an_array_list_keeps_the_per_object_check_when_the_controller_filters_only_data_lists() {
    $list = objects(new FakeObject(1), new FakeObject(2, null, ['publicView' => false]));
    $controller = pagedController($list, 2, SqlFilteredController::class);

    $data = $controller->index(request());

    assertSame([1], ids($data['Results']));
    assertSame(1, $data['Count']);
    assertSame(0, $controller->filtered);
}

/// getSingle

function test_getSingle_finds_by_id() {
    $controller = controller(numbered(3));

    assertSame(2, invoke($controller, 'getSingle', 2)->ID);
    assertSame(3, invoke($controller, 'getSingle', '3')->ID);
}

function test_getSingle_is_null_for_an_unknown_id() {
    assertNull(invoke(controller(numbered(3)), 'getSingle', 99));
}

function test_getSingle_reads_ObjectID_from_a_posted_request() {
    $posted = new HTTPRequest('POST', '', [], ['ObjectID' => 2]);

    assertSame(2, invoke(controller(numbered(3)), 'getSingle', $posted)->ID);
}

function test_getSingle_falls_back_to_the_route_ID() {
    $controller = controller(numbered(3), ['ID' => 3]);

    assertSame(3, invoke($controller, 'getSingle')->ID);
    assertSame(3, invoke($controller, 'getSingle', 0)->ID);
    assertSame(3, invoke($controller, 'getSingle', new HTTPRequest('POST'))->ID, 'a post without ObjectID uses the route ID');
}

function test_getSingle_is_null_without_any_id() {
    assertNull(invoke(controller(numbered(3)), 'getSingle'));
}

function test_getSingle_is_null_when_there_is_no_list() {
    $controller = controller(null, ['ID' => 1]);
    $controller->objects = null;

    assertNull(invoke($controller, 'getSingle', 1));
    assertNull(invoke($controller, 'getSingle'));
}

function test_getSingle_does_not_check_permissions() {
    $controller = controller(numbered(3, [2]));

    assertSame(2, invoke($controller, 'getSingle', 2)->ID);
}

/// The overridable defaults

function test_getObjectsList_defaults_to_all_pages() {
    $pages = objects(new FakeObject(1));
    DataObject::$lists['Page'] = $pages;

    assertSame($pages, (new DefaultListController())->getObjectsList());
}

function test_searchObjects_matches_part_of_the_title() {
    $list = objects(new FakeObject(1, 'Amman'), new FakeObject(2, 'Zarqa'), new FakeObject(3, 'New amman'));

    assertSame([1, 3], ids(controller()->searchObjects($list, 'Amm')));
}

function test_searchObjects_is_null_without_a_list() {
    assertNull(controller()->searchObjects(null, 'x'));
}

function test_canViewSingle() {
    $controller = controller();

    assertSame(false, $controller->canViewSingle(null));
    assertSame(true, $controller->canViewSingle(new FakeObject(1)));
    assertSame(false, $controller->canViewSingle(new FakeObject(1, null, ['publicView' => false])));
    assertSame(true, $controller->canViewSingle(new PlainObject(1, true)));
    assertSame(false, $controller->canViewSingle(new PlainObject(1, false)));
}

function test_canViewSingle_prefers_canPublicView() {
    $object = new FakeObject(1);

    controller()->canViewSingle($object);

    assertSame(1, $object->calls['canPublicView'] ?? 0);
}

function test_getEditableSingle() {
    $controller = controller(objects(new FakeObject(1, null, ['editable' => true]), new FakeObject(2)));

    assertSame(1, invoke($controller, 'getEditableSingle', 1)->ID);
    assertNull(invoke($controller, 'getEditableSingle', 2), 'not editable');
    assertNull(invoke($controller, 'getEditableSingle', 3), 'not found');
}

function test_constant_defaults() {
    $controller = controller();

    assertSame(true, $controller->isSearchable());
    assertSame(false, invoke($controller, 'IsVerticalList'));
    assertNull(invoke($controller, 'getFiltersList'));
    assertSame([], $controller->restrictFields());
    assertNull(invoke($controller, 'preRenderList'));
    assertNull(invoke($controller, 'preRenderSingle', new FakeObject(1)));
    assertSame('', $controller->ExtraClasses());
}

/// show and edit

function test_show_renders_the_object() {
    $rendered = controller(numbered(3), ['ID' => 2])->show();

    assertSame(['DataObjectPage_Show', 'DataObjectPage', 'Page'], $rendered->template);
    assertSame(['Single', 'Title'], array_keys($rendered->data));
    assertSame(2, $rendered->data['Single']->ID);
    assertSame('Object 2', $rendered->data['Title']);
}

function test_show_counts_a_view() {
    $list = numbered(3);

    controller($list, ['ID' => 2])->show();

    assertSame(1, $list->byID(2)->views);
}

function test_show_404s_for_an_unknown_object() {
    $error = assertThrows(HTTPResponse_Exception::class, function () {
        controller(numbered(3), ['ID' => 9])->show();
    });

    assertSame(404, $error->getCode());
}

function test_show_refuses_an_object_the_visitor_cant_view() {
    $list = numbered(3, [2]);

    assertSame('PERMISSION_FAILURE', controller($list, ['ID' => 2])->show());
    assertSame(0, $list->byID(2)->views, 'no view is counted');
}

function test_show_calls_preRenderSingle_with_the_object() {
    $controller = new class extends TestController {

        public $preRendered = [];

        protected function preRenderSingle($single) {
            $this->preRendered[] = $single->ID;
        }

    };
    $controller->objects = numbered(3);
    $controller->setRequest((new HTTPRequest())->setRouteParams(['ID' => 3]));

    $controller->show();

    assertSame([3], $controller->preRendered);
}

function test_edit_renders_an_editable_object() {
    $list = objects(new FakeObject(1, 'Editable', ['editable' => true]));

    $rendered = controller($list, ['ID' => 1])->edit();

    assertSame(['DataObjectPage_Edit', 'DataObjectPage', 'Page'], $rendered->template);
    assertSame(1, $rendered->data['Single']->ID);
    assertSame('Editable', $rendered->data['Title']);
    assertSame(0, $list->first()->views, 'editing is not a view');
}

function test_edit_refuses_an_object_the_member_cant_edit() {
    assertSame('PERMISSION_FAILURE', controller(numbered(1), ['ID' => 1])->edit());
}

function test_edit_404s_for_an_unknown_object() {
    $error = assertThrows(HTTPResponse_Exception::class, function () {
        controller(numbered(1), ['ID' => 2])->edit();
    });

    assertSame(404, $error->getCode());
}

/// The lazily loaded fragments

function test_tabs_renders_the_tabs() {
    $tabs = [['Key' => 'family', 'Title' => 'Family'], ['Key' => 'events', 'Title' => 'Events']];
    $list = objects(new FakeObject(1, null, ['tabs' => $tabs]));

    $rendered = controller($list, ['ID' => 1])->tabs();

    assertSame('Includes\Single_Tabs', $rendered->template);
    assertSame(['ObjectTabs'], array_keys($rendered->data));
    assertInstanceOf(ArrayList::class, $rendered->data['ObjectTabs']);
    assertSame($tabs, items($rendered->data['ObjectTabs']));
}

function test_tabs_renders_no_tabs() {
    $rendered = controller(numbered(1), ['ID' => 1])->tabs();

    assertSame([], items($rendered->data['ObjectTabs']));
}

function test_tabs_rejects_a_tab_without_a_key() {
    foreach ([[['Title' => 'No key']], [['Key' => '', 'Title' => 'Empty key']], [['Key' => 'a'], ['Key' => 0]]] as $tabs) {
        $error = assertThrows(LogicException::class, function () use ($tabs) {
            controller(objects(new FakeObject(1, null, ['tabs' => $tabs])), ['ID' => 1])->tabs();
        });

        assertSame('Tab is missing required TabKey', $error->getMessage());
    }
}

function test_tabs_rejects_duplicate_keys() {
    $tabs = [['Key' => 'a'], ['Key' => 'b'], ['Key' => 'a']];

    $error = assertThrows(LogicException::class, function () use ($tabs) {
        controller(objects(new FakeObject(1, null, ['tabs' => $tabs])), ['ID' => 1])->tabs();
    });

    assertSame('Duplicate tab Key detected: a', $error->getMessage());
}

function test_tabs_for_an_unknown_object_is_an_error() {
    // Current behaviour: no 404, the call on null fails
    assertThrows(Error::class, function () {
        controller(numbered(1), ['ID' => 2])->tabs();
    });
}

function test_tabcontent_renders_the_tab_content() {
    $rendered = controller(numbered(2), ['ID' => 2, 'Key' => 'family'])->tabcontent();

    assertSame('Includes/Single_TabContent', $rendered->template);
    assertSame(['Content' => ['tab' => 'family', 'page' => 1]], $rendered->data);
}

function test_tabcontent_passes_the_page_var_through() {
    $rendered = controller(numbered(2), ['ID' => 2, 'Key' => 'events'], ['page' => '3'])->tabcontent();

    assertSame(['tab' => 'events', 'page' => '3'], $rendered->data['Content']);
}

function test_tabcontent_without_a_key_is_a_400() {
    $error = assertThrows(HTTPResponse_Exception::class, function () {
        controller(numbered(2), ['ID' => 2])->tabcontent();
    });

    assertSame(400, $error->getCode());
    assertSame('Invalid tab content request', $error->getMessage());
}

function test_tabcontent_without_a_key_is_a_400_even_for_an_unknown_object() {
    $error = assertThrows(HTTPResponse_Exception::class, function () {
        controller(numbered(2), ['ID' => 9])->tabcontent();
    });

    assertSame(400, $error->getCode());
}

function test_summary_and_related_render_the_object() {
    $controller = controller(numbered(3), ['ID' => 3]);

    foreach (['summary' => 'Includes\Single_Summary', 'related' => 'Includes\Single_Related'] as $action => $template) {
        $rendered = $controller->$action();

        assertSame($template, $rendered->template);
        assertSame(['Single'], array_keys($rendered->data));
        assertSame(3, $rendered->data['Single']->ID);
    }
}

function test_summary_and_related_for_an_unknown_object_render_nothing() {
    // Current behaviour: no 404, the include gets a null Single
    $controller = controller(numbered(1), ['ID' => 9]);

    assertNull($controller->summary()->data['Single']);
    assertNull($controller->related()->data['Single']);
}

function test_picture_and_upload_render_the_image_values() {
    $list = objects(new FakeObject(4, 'Photo', ['publicView' => false]));
    $controller = controller($list, ['ID' => 4]);
    $expected = [
        'ObjectImage' => 'image-4',
        'ObjectDefaultImage' => 'default.jpg',
        'CanPublicView' => false,
        'ObjectTitle' => 'Photo',
    ];

    $picture = $controller->picture();
    $upload = $controller->upload();

    assertSame('Includes\Single_Image', $picture->template);
    assertSame($expected, $picture->data);
    assertSame('Includes\Single_Image_Upload', $upload->template);
    assertSame($expected, $upload->data);
}

/// Links

function test_fragment_links() {
    $controller = controller();

    assertSame('/objects/tabs/5', $controller->TabsLink(5));
    assertSame('/objects/tabcontent/5/family', $controller->TabContentLink(5, 'family'));
    assertSame('/objects/summary/5', $controller->SummaryLink(5));
    assertSame('/objects/picture/5', $controller->PictureLink(5));
    assertSame('/objects/upload/5', $controller->UploadLink(5));
    assertSame('/objects/related/5', $controller->RelatedLink(5));
}

/// Forms

function test_ObjectSearchForm() {
    $controller = controller(null, [], ['q' => 'amman']);

    $form = $controller->ObjectSearchForm();

    assertInstanceOf(Form::class, $form);
    assertSame('ObjectSearchForm', $form->name);
    assertSame(['q'], fieldNames($form->fields));
    assertSame(['doObjectSearch'], fieldNames($form->actions));
    assertSame('GET', $form->settings['setFormMethod']);
    assertSame('/objects', $form->settings['setFormAction']);
    assertSame('Includes\Form_ObjectSearch', $form->settings['setTemplate']);
    assertSame(['q' => 'amman'], $form->settings['loadDataFrom']);
    assertSame(true, $form->settings['disableSecurityToken']);
}

function test_ObjectSearchForm_uses_the_google_form_when_there_is_one() {
    $google = new Form(null, 'Google', null, null);
    $controller = new class extends TestController {

        public $google;

        public function getGoogleSiteSearchForm() {
            return $this->google;
        }

    };
    $controller->google = $google;

    $form = $controller->ObjectSearchForm();

    assertSame($google, $form);
    assertSame('Form_GoogleSearch', $form->settings['setTemplate']);
}

function editable($values = []) {
    return new FakeObject(7, 'Seven', $values + ['editable' => true]);
}

function test_ObjectEditForm_is_null_for_a_record_the_member_cant_edit() {
    $controller = controller(objects(new FakeObject(7)));

    assertNull($controller->ObjectEditForm(7));
    assertNull($controller->ObjectEditForm(8));
}

function test_ObjectEditForm_has_a_field_per_db_field() {
    $record = editable([
        'db' => ['Name' => 'Varchar', 'Version' => 'Int', 'Bio' => 'HTMLText', 'Born' => 'Date', 'Secret' => 'Varchar', 'Hidden' => 'NoScaffold'],
        'restrictFields' => ['Secret'],
        'Name' => 'Ali',
        'Born' => '1990-01-01',
        'Bio' => '<p>Bio</p>',
    ]);

    $form = controller(objects($record))->ObjectEditForm(7);

    assertSame('ObjectEditForm', $form->name);
    assertSame(['ObjectID', 'Name', 'Bio', 'Born'], fieldNames($form->fields));

    [$id, $name, $bio, $born] = items($form->fields);
    assertInstanceOf(HiddenField::class, $id);
    assertSame(7, $id->value);
    assertInstanceOf(TextField::class, $name);
    assertSame('Label Name', $name->title);
    assertSame('Ali', $name->value);
    assertSame(FrontendRichTextField::class, get_class($bio), 'rich text is edited with the frontend field');
    assertSame('Label Bio', $bio->title);
    assertSame('<p>Bio</p>', $bio->value);
    assertInstanceOf(DateField::class, $born);
    assertSame('1990-01-01', $born->value);
}

function test_ObjectEditForm_leaves_out_Version_without_restrict_fields() {
    $record = editable(['db' => ['Version' => 'Int', 'Name' => 'Varchar']]);

    assertSame(['ObjectID', 'Name'], fieldNames(controller(objects($record))->ObjectEditForm(7)->fields));
}

function test_ObjectEditForm_reads_ObjectID_from_a_posted_request() {
    $form = controller(objects(editable()))->ObjectEditForm(new HTTPRequest('POST', '', [], ['ObjectID' => 7]));

    assertSame(['ObjectID'], fieldNames($form->fields));
}

function test_ObjectEditForm_actions() {
    $save = fieldNames(controller(objects(editable()))->ObjectEditForm(7)->actions);
    $delete = fieldNames(controller(objects(editable(['deletable' => true])))->ObjectEditForm(7)->actions);

    assertSame(['doObjectEdit'], $save);
    assertSame(['doObjectEdit', 'doDelete'], $delete);
}

function recordFieldNames($restrictFields, $db = ['Name' => 'Varchar', 'Title' => 'Varchar', 'Version' => 'Int']) {
    $fields = new FieldList();
    $record = editable(['db' => $db, 'restrictFields' => $restrictFields]);

    (function () use ($record, &$fields) {
        $this->getRecordFields($record, $fields);
    })->call(controller());

    return fieldNames($fields);
}

function test_getRecordFields_matches_restricted_names_exactly() {
    assertSame(['Name', 'Title'], recordFieldNames(['name', 'Title ', 'Titl']), 'case and whitespace matter');
    assertSame(['Title'], recordFieldNames(['Name', 'Name']));
    assertSame([], recordFieldNames(['Title', 'Name']));
    assertSame(['Name', 'Title'], recordFieldNames([]));
    assertSame(['Name', 'Title'], recordFieldNames(['Version']));
}

function test_getRecordFields_compares_non_string_restrictions_loosely() {
    // Current behaviour: in_array() is loose, so true matches every field and 0 or null match none
    assertSame([], recordFieldNames([true]));
    assertSame(['Name', 'Title'], recordFieldNames([0, null, false, 1.5]));
    assertSame(['Title'], recordFieldNames([0, 'Name']));
}

function test_getRecordFields_compares_numeric_names_loosely() {
    assertSame(['Name'], recordFieldNames(['1e1'], ['Name' => 'Varchar', '10' => 'Varchar']));
    assertSame(['Name'], recordFieldNames([10], ['Name' => 'Varchar', '10' => 'Varchar']));
    assertSame(['Name'], recordFieldNames([' 10'], ['Name' => 'Varchar', '10' => 'Varchar']));
    assertSame(['Name', 10], recordFieldNames(['10abc'], ['Name' => 'Varchar', '10' => 'Varchar']));
}

function test_getRecordFields_fails_for_a_restrict_fields_that_isnt_a_list() {
    assertThrows(TypeError::class, function () {
        recordFieldNames('Name');
    });
}

function test_getRecordFields_does_nothing_without_a_record() {
    $fields = new FieldList();
    $controller = controller();

    (function () use (&$fields) {
        $this->getRecordFields(null, $fields);
    })->call($controller);

    assertSame(0, count($fields));
}

class FakeForm {

    public $savedInto = [];

    public function saveInto($record) {
        $this->savedInto[] = $record->ID;
    }

}

function test_the_save_handlers_save_and_redirect_back() {
    foreach (['doObjectEdit', 'doImageEdit', 'doImportantEdit'] as $handler) {
        $record = editable();
        $form = new FakeForm();

        $result = controller(objects($record))->$handler(['ObjectID' => 7], $form);

        assertSame('REDIRECT_BACK', $result, $handler);
        assertSame([7], $form->savedInto, $handler);
        assertSame(1, $record->calls['write'] ?? 0, $handler);
    }
}

function test_the_save_handlers_refuse_a_record_the_member_cant_edit() {
    foreach (['doObjectEdit', 'doImageEdit', 'doImportantEdit'] as $handler) {
        foreach ([['ObjectID' => 7], ['ObjectID' => 8], []] as $data) {
            $record = new FakeObject(7);
            $form = new FakeForm();

            assertSame('PERMISSION_FAILURE', controller(objects($record))->$handler($data, $form), $handler);
            assertSame([], $form->savedInto, $handler);
            assertSame(0, $record->calls['write'] ?? 0, $handler);
        }
    }
}

function test_doImageCancel_redirects_back() {
    assertSame('REDIRECT_BACK', controller()->doImageCancel([], new FakeForm()));
}

function test_ImageEditForm_is_null_without_an_editable_image() {
    assertNull(controller(objects(editable()))->ImageEditForm(7), 'no image field');
    assertNull(controller(objects(new FakeObject(7, null, ['editableImageName' => 'Photo'])))->ImageEditForm(7), 'not editable');
    assertNull(controller(objects(editable(['editableImageName' => 'Photo'])))->ImageEditForm(8), 'not found');
}

function test_ImageEditForm() {
    $form = controller(objects(editable(['editableImageName' => 'Photo'])))->ImageEditForm(7);

    assertSame('ImageEditForm', $form->name);
    assertSame(['ObjectID', 'Photo'], fieldNames($form->fields));
    [$id, $image] = items($form->fields);
    assertSame(7, $id->value);
    assertInstanceOf(FrontendImageField::class, $image);
    assertSame('', $image->title);
    assertSame('image-7', $image->value);
    assertSame(['jpg', 'jpeg', 'png', 'gif'], $image->getValidator()->allowedExtensions);
    assertNull($image->folderName);

    [$save] = items($form->actions);
    assertSame('doImageEdit', $save->getName());
    assertSame('Save', $save->title);
}

function test_ImageEditForm_uploads_to_the_record_folder() {
    $record = new FakeImageObject(7, null, ['editable' => true, 'editableImageName' => 'Photo']);

    $image = items(controller(objects($record))->ImageEditForm(new HTTPRequest('POST', '', [], ['ObjectID' => 7]))->fields)[1];

    assertSame('object-images', $image->folderName);
}

function test_ImportantEditForm_is_null_without_important_items() {
    assertNull(controller(objects(editable()))->ImportantEditForm(7), 'no items');
    assertNull(controller(objects(editable(['importantItems' => []])))->ImportantEditForm(7), 'empty items');
    assertNull(controller(objects(new FakeObject(7, null, ['importantItems' => [TextField::create('A')]])))->ImportantEditForm(7), 'not editable');
}

function test_ImportantEditForm() {
    $a = TextField::create('A');
    $b = TextField::create('B');

    $form = controller(objects(editable(['importantItems' => [$a, $b]])))->ImportantEditForm(new HTTPRequest('POST', '', [], ['ObjectID' => 7]));

    assertSame('ImportantEditForm', $form->name);
    assertSame(['A', 'B', 'ObjectID'], fieldNames($form->fields));
    assertSame($a, items($form->fields)[0]);
    assertSame(7, items($form->fields)[2]->value);
    // Current behaviour: the action is doImageEdit, not doImportantEdit
    assertSame(['doImageEdit'], fieldNames($form->actions));
}

/// Meta tags

function test_RichSnippets_is_the_object_markup_as_json() {
    $record = new FakeObject(1, null, ['markup' => ['@type' => 'Person', 'name' => 'علي <b>&</b>', 'url' => 'https://x.test/a']]);

    $snippet = controller(objects($record), ['ID' => 1])->RichSnippets();

    assertInstanceOf(DBField::class, $snippet);
    assertSame('HTMLText', $snippet->type);
    assertSame('{"@type":"Person","name":"علي \u003Cb\u003E\u0026\u003C/b\u003E","url":"https://x.test/a","@context":"http://schema.org"}', $snippet->value);
}

function test_RichSnippets_collapses_runs_of_whitespace() {
    $record = new FakeObject(1, null, ['markup' => ['name' => "A   B", 'tab' => "C\tD", 'line' => "E\nF"]]);

    $snippet = controller(objects($record), ['ID' => 1])->RichSnippets();

    assertSame('{"name":"A B","tab":"C\tD","line":"E\nF","@context":"http://schema.org"}', $snippet->value);
}

function test_RichSnippets_whitespace_of_every_kind() {
    $nbsp = "\u{00A0}";
    $nel = "\u{0085}";
    $markup = [
        'nbsp' => "a{$nbsp}b{$nbsp}{$nbsp}c",
        'mixed' => "a {$nbsp}\u{2003}b",
        'nel' => "a{$nel}{$nel}b",
        'separators' => "a\u{2028}\u{2029}b",
        'controls' => "a\r\n\v\f b",
        'edges' => "  x  ",
        'nested' => ['deep' => ['  y  ', "z\n\n"]],
    ];

    $snippet = controller(objects(new FakeObject(1, null, ['markup' => $markup])), ['ID' => 1])->RichSnippets();

    assertSame('{"nbsp":"a' . $nbsp . 'b c","mixed":"a b","nel":"a b","separators":"a\\u2028\\u2029b","controls":"a\\r\\n\\u000b\\f b","edges":" x ","nested":{"deep":[" y ","z\\n\\n"]},"@context":"http://schema.org"}', $snippet->value);
}

function test_RichSnippets_of_empty_markup() {
    $snippet = controller(objects(new FakeObject(1)), ['ID' => 1])->RichSnippets();

    assertSame('{"@context":"http://schema.org"}', $snippet->value);
}

function test_RichSnippets_of_markup_json_cant_encode_is_empty() {
    $record = new FakeObject(1, null, ['markup' => ['name' => "bad \xB1 utf8"]]);

    assertSame('', controller(objects($record), ['ID' => 1])->RichSnippets()->value);
}

function test_RichSnippets_is_null_without_a_discoverable_object() {
    assertNull(controller(objects(new FakeObject(1)))->RichSnippets(), 'no object');
    assertNull(controller(objects(new PlainObject(1, true)), ['ID' => 1])->RichSnippets(), 'not discoverable');
}

function test_OpenGraph() {
    $object = controller(numbered(2), ['ID' => 2])->OpenGraph();
    $page = controller(numbered(2))->OpenGraph();

    assertSame('Includes\Single_OpenGraph', $object->template);
    assertSame(2, $object->data['Single']->ID);
    assertSame('Includes\Page_OpenGraph', $page->template);
}

function test_SocialDescription_cleans_up_the_object_description() {
    $record = new FakeObject(1, null, ['description' => "  <p>Born in\n\n Amman ( Jordan )in 1990</p>  <b>x</b> "]);

    assertSame('Born in Amman (Jordan) in 1990 x', controller(objects($record), ['ID' => 1])->SocialDescription());
}

function test_SocialDescription_falls_back_to_the_page_meta_description() {
    assertSame('Page meta description', controller(numbered(1))->SocialDescription(), 'no object');
    assertSame('Page meta description', controller(objects(new PlainObject(1, true)), ['ID' => 1])->SocialDescription(), 'no description');
}

function test_Canonical() {
    assertSame('https://example.test/objects/show/2', controller(numbered(2), ['ID' => 2])->Canonical());
    assertSame('https://example.test/objects', controller(numbered(2))->Canonical());
}

function test_FullURL() {
    assertSame('https://example.test/a/b', controller()->FullURL('/a/b'));
}

function test_ExtraMetaTags() {
    assertSame('Includes\Page_ExtraTags', controller()->ExtraMetaTags()->template);
}

/// Direction

function test_isRTL_and_Align() {
    $controller = controller();

    assertSame(false, $controller->isRTL());
    assertSame('left', $controller->Align());

    SilverStripe\i18n\i18n::$locale = 'ar_JO';
    assertSame(true, $controller->isRTL());
    assertSame('right', $controller->Align());
}

function test_init_runs() {
    controller()->init();
    SilverStripe\i18n\i18n::$locale = 'ar_JO';
    controller()->init();
    assertTrue(true);
}

/// Locales

function localised($segment) {
    $page = new Page();
    $page->URLSegment = $segment;
    return $page;
}

function test_Locales_is_null_without_a_current_locale() {
    Locale::$cached = [new Locale('en_US', 'English', 'en')];

    assertNull(controller()->Locales());
}

function test_Locales_links_each_locale_to_the_same_action() {
    Locale::$cached = [new Locale('en_US', 'English', 'en'), new Locale('ar_JO', 'العربية', 'ar')];
    Page::$byLocale = ['en_US' => localised('people'), 'ar_JO' => localised('ar/people')];
    FluentState::singleton()->setLocale('ar_JO');

    $locales = controller(null, ['Action' => 'show', 'ID' => 12])->Locales();

    assertInstanceOf(ArrayList::class, $locales);
    assertSame([
        ['Locale' => 'en_US', 'Title' => 'English', 'Link' => '/people/show/12', 'AbsoluteLink' => 'https://example.test/people/show/12', 'LinkingMode' => 'link', 'HrefLang' => 'en'],
        ['Locale' => 'ar_JO', 'Title' => 'العربية', 'Link' => '/ar/people/show/12', 'AbsoluteLink' => 'https://example.test/ar/people/show/12', 'LinkingMode' => 'current', 'HrefLang' => 'ar'],
    ], array_map(function (ArrayData $locale) {
        return $locale->toMap();
    }, items($locales)));
    assertSame('ar_JO', FluentState::singleton()->getLocale(), 'the current locale is restored');
}

function test_Locales_on_the_list_page() {
    Locale::$cached = [new Locale('en_US', 'English', 'en')];
    Page::$byLocale = ['en_US' => localised('people')];
    FluentState::singleton()->setLocale('en_US');

    $locale = controller(null, ['OtherID' => 'x'])->Locales()->first()->toMap();

    assertSame('/people/x', $locale['Link']);
}

function test_Locales_with_no_locales_is_an_empty_list() {
    FluentState::singleton()->setLocale('en_US');

    assertSame(0, controller()->Locales()->count());
}

function test_Locales_fails_when_the_page_doesnt_exist_in_a_locale() {
    // Current behaviour: no skip, Link() is called on null
    Locale::$cached = [new Locale('en_US', 'English', 'en'), new Locale('fr_FR', 'Français', 'fr')];
    Page::$byLocale = ['en_US' => localised('people')];
    FluentState::singleton()->setLocale('en_US');

    assertThrows(Error::class, function () {
        controller()->Locales();
    });
}

/// Strip

function test_Strip() {
    assertSame('Hello world', controller()->Strip('<p>Hello <b>world</b></p>'));
}
