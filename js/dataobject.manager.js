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
        var url = $pictureHolder.data('url');
        var align = $pictureHolder.data('align');
        $pictureHolder.attr('data-url', '');

        $pictureHolder.load(url, function (data) {
            $pictureHolder.removeClass('place-holder');
            $('.imgBox').imgZoom({
                boxWidth: 500,
                boxHeight: 400,
                marginLeft: 5,
                align: align,
                origin: 'data-origin'
            });
        });
    }
};

var updateSummary = function () {
    var $summaryHolder = $('.dataobject-brief.place-holder');

    if ($summaryHolder.length) {
        var url = $summaryHolder.data('url');
        $summaryHolder.attr('data-url', '');

        $summaryHolder.load(url, function (data) {
            $summaryHolder.removeClass('place-holder');
        });
    }
};

var updateTabs = function () {
    var $tabsHolder = $('.dataobject-tabs.place-holder');

    if ($tabsHolder.length) {
        var url = $tabsHolder.data('url');
        $tabsHolder.attr('data-url', '');

        $tabsHolder.load(url, function () {
            $tabsHolder.removeClass('place-holder');
            try {
                onTabsLoaded();
            } catch (e) {}
        });
    }
};

var updateRelated = function () {
    var $relatedHolder = $('.dataobject-related.place-holder');

    if ($relatedHolder.length) {
        var url = $relatedHolder.data('url');
        $relatedHolder.attr('data-url', '');

        $relatedHolder.load(url, function (data) {
            $relatedHolder.removeClass('place-holder');
});
    }
};
