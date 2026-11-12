<?php

/**
 * How much work the controller does, not what it returns: lookups, list loads and repeated calls.
 *
 * DataObjectPageControllerTest.php pins the results and must pass on every version. This file only passes once the
 * optimizations are in.
 *
 *   php tests/run.php tests/OptimizationTest.php
 */

use SilverStripe\Forms\TextField;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;

require_once __DIR__ . '/DataObjectPageControllerTest.php';

/**
 * Everything a show page renders that needs its object
 */
function renderShowPage($controller) {
    $controller->show();
    $controller->RichSnippets();
    $controller->OpenGraph();
    $controller->SocialDescription();
    $controller->Canonical();
}

function test_a_show_page_looks_its_object_up_once() {
    $controller = controller(numbered(3), ['ID' => 2]);

    renderShowPage($controller);

    assertSame(1, $controller->lookups);
    assertSame(1, $controller->listLoads);
}

function test_an_edit_page_looks_its_object_up_once() {
    $controller = controller(objects(editable(['editableImageName' => 'Photo', 'importantItems' => [TextField::create('A')]])), ['ID' => 7]);

    $controller->edit();
    $controller->ObjectEditForm(7);
    $controller->ImageEditForm(7);
    $controller->ImportantEditForm(7);
    $controller->Canonical();

    assertSame(1, $controller->lookups);
}

function test_the_list_page_meta_tags_load_no_list() {
    $controller = controller(numbered(3));

    $controller->RichSnippets();
    $controller->OpenGraph();
    $controller->SocialDescription();
    $controller->Canonical();

    assertSame(0, $controller->listLoads);
}

function test_tabcontent_without_a_key_looks_nothing_up() {
    $controller = controller(numbered(2), ['ID' => 2]);

    try {
        $controller->tabcontent();
    } catch (SilverStripe\Control\HTTPResponse_Exception $error) {
    }

    assertSame(0, $controller->listLoads);
}

function test_the_edit_forms_ask_the_record_once() {
    $record = editable(['editableImageName' => 'Photo', 'importantItems' => [TextField::create('A')]]);
    $controller = controller(objects($record));

    $controller->ImageEditForm(7);
    $controller->ImportantEditForm(7);

    assertSame(1, $record->calls['getObjectEditableImageName']);
    assertSame(1, $record->calls['getImportantItems']);
}

function test_Locales_loads_each_localised_page_once_per_request() {
    Locale::$cached = [new Locale('en_US', 'English', 'en'), new Locale('ar_JO', 'العربية', 'ar')];
    Page::$byLocale = ['en_US' => localised('people'), 'ar_JO' => localised('ar/people')];
    FluentState::singleton()->setLocale('en_US');
    $controller = controller();

    $first = $controller->Locales();
    $second = $controller->Locales();

    assertSame(2, Page::$loads);
    assertSame($first, $second);
}

function test_filterViewable_replaces_the_per_object_permission_check() {
    $controller = new class extends TestController {

        protected function filterViewable($list) {
            return $list->filter(['publicView' => true]);
        }

    };
    $controller->objects = numbered(5, [2, 4]);

    $data = $controller->index(request());

    assertSame([1, 3, 5], ids($data['Results']));
    assertSame(3, $data['Count']);
    foreach ($controller->objects as $object) {
        assertSame(0, $object->calls['canPublicView'] ?? 0, "object {$object->ID} was checked in PHP");
    }
}

function test_index_on_a_data_list_loads_only_the_page_and_counts_once() {
    $list = numberedData(1000, [3]);

    $results = pagedController($list, 10)->index(request(['start' => 20]))['Results'];
    items($results);
    $results->getTotalItems();

    assertSame(10, $list->log->rowsLoaded);
    assertSame(1, $list->log->selects);
    assertSame(1, $list->log->counts);
}

function test_index_with_the_controllers_sql_filter_loads_only_the_page_and_counts_once() {
    $list = numberedData(1000, [3]);

    $results = pagedController($list, 10, SqlFilteredController::class)->index(request(['start' => 20]))['Results'];
    items($results); // as the template loops them
    $results->getTotalItems(); // as List_Paginate counts the pages

    assertSame(10, $list->log->rowsLoaded);
    assertSame(1, $list->log->counts);
}

function test_index_results_run_the_page_query_once_however_often_the_template_loops_them() {
    $list = numberedData(1000, [3]);

    $results = pagedController($list, 10, SqlFilteredController::class)->index(request(['start' => 20]))['Results'];
    items($results); // the template's loop
    items($results); // SS 5.4 loops again to count the items

    assertSame(1, $list->log->selects);
    assertSame(10, $list->log->rowsLoaded);
}

function test_index_results_query_the_new_page_after_the_page_start_changes() {
    $list = numberedData(30);

    $results = pagedController($list, 10, SqlFilteredController::class)->index(request())['Results'];
    $first = ids($results);
    $results->setPageStart(10);

    assertSame(range(1, 10), $first);
    assertSame(range(11, 20), ids($results));
    assertSame(2, $list->log->selects);
}

class PrefetchingController extends SqlFilteredController {

    public $prefetched = [];

    protected function prefetchResults($results) {
        $this->prefetched[] = ids($results);
    }

}

function test_index_hands_the_page_to_prefetchResults_without_querying_it_again() {
    $list = numberedData(100, [3]);
    $controller = pagedController($list, 10, PrefetchingController::class);

    $results = $controller->index(request(['start' => 10]))['Results'];
    items($results); // the template's loops
    items($results);

    assertSame([range(12, 21)], $controller->prefetched);
    assertSame(1, $list->log->selects);
}

function test_index_hands_the_page_to_prefetchResults_for_ajax_too() {
    $controller = pagedController(numberedData(5), 2, PrefetchingController::class);

    $controller->index(request([], true));

    assertSame([[1, 2]], $controller->prefetched);
}

function test_getRecordFields_reads_the_config_once() {
    $record = editable(['db' => ['Name' => 'Varchar'], 'restrictFields' => ['Secret']]);

    controller(objects($record))->ObjectEditForm(7);

    assertSame(1, $record->calls['config']);
}
