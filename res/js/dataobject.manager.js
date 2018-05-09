/*
 * 11-27-2017
 */

jQuery(document).ready(function () {
    updatePicture();
    updateSummary();
    updateTabs();
    updateRelated();
});

var updatePicture = function () {
    var $pictureHolder = $('.dataobject-picture.place-holder');

    if ($pictureHolder.length) {
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

        });
    }
};

var updateSummary = function () {
    var $summaryHolder = $('.dataobject-brief.place-holder');

    if ($summaryHolder.length) {
        var url = getURL($summaryHolder);
        if (!url) {
            return;
        }

        $summaryHolder.load(url, function (data) {
            $summaryHolder.removeClass('place-holder');
        });
    }
};

var updateTabs = function () {
    var $tabsHolder = $('.dataobject-tabs.place-holder');

    if ($tabsHolder.length) {
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
            try {
                onCoreTabsLoaded();
            } catch (e) {
            }

            try {
                onTabsLoaded();
            } catch (e) {
            }
        });
    }
};

var updateRelated = function () {
    var $relatedHolder = $('.dataobject-related.place-holder');

    if ($relatedHolder.length) {
        var url = getURL($relatedHolder);
        if (!url) {
            return;
        }

        $relatedHolder.load(url, function (data) {
            $relatedHolder.removeClass('place-holder');
        });
    }
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