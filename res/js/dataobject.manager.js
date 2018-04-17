/*
 * 11-27-2017
 */

jQuery(document).ready(function () {
    updatePicture();
    updateSummary();
    updateTabs();
    updateRelated();
});

$(function () {
    var hash = window.location.hash;
    hash && $('ul.nav a[href="' + hash + '"]').tab('show');

    $('.nav-tabs a').click(function (e) {
        $(this).tab('show');
        var scrollmem = $('body').scrollTop();
        window.location.hash = this.hash;
        $('html,body').scrollTop(scrollmem);
    });
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
            $('a.nav-link.tab-link').each(function () {
                console.log(this.href);
                var hashLink = this.href.split('#')[1];
                console.log(hashLink);
                $(this).attr('href', '#' + hashLink);
            });

            $tabsHolder.removeClass('place-holder');
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
