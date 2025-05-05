/*
 * 11-27-2017 (updated 05-03-2025 for tab lazy loading + IntersectionObserver)
 */

jQuery(document).ready(function () {
    updatePicture();
    updateSummary();
    updateTabs();
    updateRelated();
});

/* Picture */
var pictureCallbacks = [];
var onPictureLoaded = function (callback) {
    pictureCallbacks.push(function () {
        callback();
    });
};

var updatePicture = function () {
    var $pictureHolder = $('.dataobject-picture.place-holder');
    observeAndLoad($pictureHolder, function () {
        var url = getURL($pictureHolder);
        if (!url) {
            return;
        }

        $pictureHolder.load(url, function (data) {
            $pictureHolder.removeClass('place-holder');
            // Open modal in AJAX callback
            $('.btn-show-upload').click(function (event) {
                event.preventDefault();
                loadModal(this.href);
            });

            pictureCallbacks.forEach(function (callback) {
                callback();
            });
        });
    });
};

/* Summary */
var summaryCallbacks = [];
var onSummaryLoaded = function (callback) {
    summaryCallbacks.push(function () {
        callback();
    });
};

var updateSummary = function () {
    var $summaryHolder = $('.dataobject-brief.place-holder');
    observeAndLoad($summaryHolder, function () {
        var url = getURL($summaryHolder);
        if (!url) {
            return;
        }

        $summaryHolder.load(url, function (data) {
            $summaryHolder.removeClass('place-holder');

            summaryCallbacks.forEach(function (callback) {
                callback();
            });
        });
    });
};

/* Tabs */
var tabsCallbacks = [];
var onTabsLoaded = function (callback) {
    tabsCallbacks.push(function () {
        callback();
    });
};

var updateTabs = function () {
    var $tabsHolder = $('.dataobject-tabs.place-holder');
    observeAndLoad($tabsHolder, function () {
        var url = getURL($tabsHolder);
        if (!url) {
            return;
        }

        $tabsHolder.load(url, function () {
            // Remove url before #
            $('a.nav-link.tab-link').each(function () {
                var hashLink = this.href.split('#')[1];
                $(this).attr('href', '#' + hashLink);
            });

            $tabsHolder.removeClass('place-holder');

            tabsCallbacks.forEach(function (callback) {
                callback();
            });

            initTabLoading();
        });
    });
};

/* Related */
var relatedCallbacks = [];
var onRelatedLoaded = function (callback) {
    relatedCallbacks.push(function () {
        callback();
    });
};

var updateRelated = function () {
    var $relatedHolder = $('.dataobject-related.place-holder');
    observeAndLoad($relatedHolder, function () {
        var url = getURL($relatedHolder);
        if (!url) {
            return;
        }

        $relatedHolder.load(url, function (data) {
            $relatedHolder.removeClass('place-holder');

            relatedCallbacks.forEach(function (callback) {
                callback();
            });
        });
    });
};

var getURL = function ($element) {
    var url = $element.data('url');
    $element.attr('data-url', '');

    return url;
};

var loadModal = function (url) {
    $.get(url, function (html) {
        var $content = $(html);
        $content.appendTo('body').modal({
            closeText: '<span aria-hidden="true">×</span>',
            closeClass: 'close',
            fadeDuration: 400
        });

        initFileinput();
    });
};

var initTabLoading = function () {
    $('.tab-link').on('click', function (e) {
        e.preventDefault();

        var $tab = $(this);
        var tabKey = $tab.data('tab');
        var objectId = $tab.data('object-id');
        var objectClass = $tab.data('object-class');
        var $contentContainer = $('#' + tabKey + '-content');

        if ($contentContainer.data('loaded')) {
            return;
        }

        $contentContainer.html('<span class="loader"></span>');

        $contentContainer.load($tab.data('url'), function () {
            $contentContainer.data('loaded', true);

            tabsCallbacks.forEach(function (callback) {
                callback();
            });
        });
    });

    $('.tab-link.active').trigger('click');
};

var observeAndLoad = function ($element, loadCallback) {
    if (!$element.length) {
        return;
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadCallback();
                    obs.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '200px',
            threshold: 0.1
        });

        $element.each(function () {
            observer.observe(this);
        });
    } else {
        loadCallback(); // fallback for older browsers
    }
};
