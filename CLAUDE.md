# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A SilverStripe **vendor module** (`hudhaifas/silverstripe-dataobject-manager`) that provides a frontend wrapper to list, search, show and edit instances of an arbitrary DataObject through a single CMS page. It is not a standalone app: there is no build or linter in the repo. To exercise changes in a real site, install it into a SilverStripe project (`composer require hudhaifas/silverstripe-dataobject-manager`), then run `dev/build?flush=1`. Flush again after changing templates or YAML config.

- `composer.json` targets **SilverStripe 5** (`silverstripe/framework ^5`, `silverstripe/cms ^5`). The README still says 4.x and is out of date.
- Hard dependencies: `hudhaifas/silverstripe-frontend-fields` (supplies `FrontendFileField`, `FrontendImageField` and `FrontendRichTextField` for the edit forms) and `hudhaifas/silverstripe-data-progress`.
- The frontend assumes the host theme provides jQuery 3 and Bootstrap 4. The `jquery-modal` assets load from a CDN in the controller's `init()`.
- Optional integration: TractorCow Fluent. The controller guards it with `class_exists` checks to build the locale switcher.

## Tests

`tests/` holds plain-PHP unit tests for `DataObjectPageController`. They need no composer install: `tests/bootstrap.php` stubs the few SilverStripe, frontend-fields and Fluent classes the controller touches. The stubs follow the framework's semantics, for example `PaginatedList::count()` counts the whole list, not the page.

```
php tests/run.php                                # input/output suite; must pass on every version
php tests/run.php tests/OptimizationTest.php     # also counts lookups and repeated calls
DOM_CONTROLLER=/path/to/Controller.php php tests/run.php   # run against another copy, e.g. the original
```

`DataObjectPageControllerTest.php` pins current behaviour, quirks included. Tests commented "Current behaviour" pin known bugs, so fixing one of those bugs means changing its test on purpose. A PHP warning fails the test that caused it.

## Architecture

### The integration contract
A consuming project wires up **two** classes per managed type:
1. **A DataObject that implements `HudhaifaS\DOM\Model\ManageableDataObject`** (`src/Model/`). The controller and templates never touch the object's fields directly. They call `getObjectTitle/Link/EditLink/Image/DefaultImage/Summary/Nav/Related/Tabs/TabContent`, `getObjectEditableImageName` and `canPublicView`. It can also implement `DiscoverableDataObject` (`getObjectMarkup`, `getObjectDescription`) to supply rich-snippet and OpenGraph data.
2. **A subclass of `DataObjectPage` / `DataObjectPageController`** (`src/View/`). The subclass overrides `getObjectsList()`, which is required (the base class returns `DataObject::get('Page')` as a placeholder). It can also override `searchObjects($list, $keywords)` (default: `Title:PartialMatch`), `getSingle()`, `canViewSingle()`, `preRenderList()`, `preRenderSingle()` and `getFiltersList()`. The controller's `restrictFields()` hook is never called. The edit form excludes the fields listed in the **record's** `restrict_fields` config instead.

`DataObjectPage::canCreate()` returns `false`, so only subclasses can be created in the CMS.

### Request flow and progressive (AJAX) loading
- `index` loads `getObjectsList()` and optionally narrows it with `searchObjects` from `?q=`. It then filters by permission and paginates with `?start=` using the page's `PageLength`:
  - If `filterViewable($list)` returns a list, pagination and count happen in SQL and nothing is checked in PHP. The base method returns `null`. A derived controller overrides it for the DataObject it lists, returning the list narrowed to exactly what `canViewSingle()` would keep. It runs after `searchObjects()`, so it covers search results too. The consumers' overrides:
    - `EtymologyPageController`, `UrbanismPageController` and `GenderCategoryPageController` return the list as is, because those objects are always viewable.
    - `PeoplePageController` translates `Gender::canView()`, `PermissibleDataObjectExtension` and the subclass `canPublicView()` overrides into a `whereAny`. A change to those permission rules must change it too: `am-genealogy`'s `php tests/visibility/run.php` compares the two on random SQLite data.
    - The models themselves have no list methods.
  - Otherwise, a `DataList` with a `PageLength` is paged and counted in SQL, and `canViewSingle()` runs only on that page. The count includes hidden objects, and a page with hidden objects on it is short (intended, pinned by tests). `EventsPageController` takes this path: `LifeEvent` and `CollectiveEvent` decide `canView()` per event, and the events page never shows the list anyway, only its anniversary sections.
  - Before a `DataList` is paged, `paginate()` adds the base table's `ID` as the last sort column, unless the sort already includes `ID`. MySQL can order rows that tie on every sort column differently for each `LIMIT`, so they would repeat or go missing across pages (for example `IndexedName` on the clans list).
  - An in-memory list such as an `ArrayList`, or a page length of 0, still runs `canViewSingle()` on every row (O(N)).
  - A `PaginatedList` returned by `searchObjects()` is not used as-is: its whole inner list is filtered and paginated again.
  - `paginate()` returns a `CachedPaginatedList` (`src/ORM/`). SS 5.4's template engine loops a `PaginatedList` twice (the items, then `iterator_count`), and `PaginatedList` reruns the page query on each loop. The cached list keeps the page of a `DataList` until the start, length, limiting or inner list changes. In-memory lists aren't cached.
  - Once the page is ready, `index` passes it to `prefetchResults($results)`, which is empty in the base class. A derived controller overrides it to load in bulk what the template asks each object for. Looping `$results` there costs no extra query. `PeoplePageController` uses it to load seven generations of fathers (`Person::prefetch_fathers`) and every ancestor in `IndexedAncestors` (`Gender::prefetch_by_ids`, which fills the cache `DataObject::get_by_id()` reads).
  - `EventsPageController::index()` returns no list for a page request, because `EventsPage.ss` never shows it. An AJAX request still gets the list.
- Inside the controller, read the current object through `getCachedSingle()`, not `getSingle()`. It memoises per ID for the request, so the action, the fragment actions, the edit forms, `RichSnippets`, `OpenGraph`, `SocialDescription` and `Canonical` share one lookup. `getSingle()` remains the loader that subclasses override; it returns `null` without loading the list when there is no ID.
- `show/$ID` and `edit/$ID` render the `DataObjectPage_Show` and `DataObjectPage_Edit` layouts. By default `getSingle()` looks the ID up inside `getObjectsList()`. Most consumers override it to fetch by ID directly, so don't rely on the list as an access boundary.
- The show page renders only a skeleton. Placeholder divs carry `data-url` attributes built by `PictureLink`, `SummaryLink`, `TabsLink`, `TabContentLink` and `RelatedLink`. `res/js/dataobject.manager.js` fetches each one with `$.load()` and blanks the `data-url` afterwards so it isn't fetched twice. These URLs map to the controller actions `picture`, `summary`, `tabs`, `tabcontent/$ID/$Key`, `related` and `upload`, which return `Includes/Single_*` fragments. A new lazily loaded section needs all of these: an `$allowed_actions` entry, a `$url_handlers` entry, a `*Link()` helper, an `Includes/` template and a JS loader.
- The edit forms are `ObjectEditForm` (scaffolded from `$db` only, minus `restrict_fields` and `Version`; `has_one` and `many_many` are not editable), `ImageEditForm` and `ImportantEditForm`. Each carries the object ID in an `ObjectID` hidden field, and `getSingle()` reads that field when given an `HTTPRequest`.

### Optional DataExtensions (`src/Extension/`, namespace `HudhaifaS\DOM\Extension`)
None of these are applied by default (`_config/config.yml` is empty). Consuming projects attach them in their own YAML. The known consumers (`am-genealogy`, `am-urban`, `am-etymology`, `am-genealogy-jo`, `am-genealogy-grandstracker`, all under `~/Personal/`) use only `DataObject_Views`. For privacy and ownership they use `PermissibleDataObjectExtension` and `MemberOwnershipExtension` from the `amlineage.com` app.
- `DataObject_PrivicyExtension` (the class name has this spelling; keep it): adds `CanViewType` and `CanEditType` along with group/member pickers, and overrides `canView/Edit/Delete/Create`, using a static permission cache.
- `DataObject_MemberExtension`: a `has_one` for the creating member, set in `onBeforeWrite`.
- `DataObject_Views`: a `NumView` counter. `showSingle()` calls `addView()` when the object has that method.

### Namespacing quirk
The models and extensions are namespaced (`HudhaifaS\DOM\...`), but `DataObjectPage` and `DataObjectPageController` are in the **global namespace** (they extend the project's `Page` and `PageController`). Keep this when you edit them: renaming them would break subclasses and stored `ClassName` values.

### Templates, styles and i18n
- `templates/Layout/` holds the page layouts. `templates/Includes/` holds the overridable fragments: `List_*` for the list display variants, `Single_*` for show-page sections (with `*_PlaceHolder` skeletons), and the OpenGraph/meta includes. Themes override these by name.
- The CSS in `res/css/` is **compiled from `res/less/`**. Edit the `.less` sources and recompile; don't hand-edit the `.css` or `.map` files. There is no build script in the repo, so use any LESS compiler. RTL variants (`*-rtl.less`) load when `isRTL()` is true.
- Assets are exposed through `extra.expose` and referenced as `hudhaifas/silverstripe-dataobject-manager: res/...`.
- Translations live in `lang/en.yml` and `lang/ar.yml`. Add new `_t()` keys to both.
