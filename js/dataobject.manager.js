/*
 * 11-27-2017
 */

jQuery(document).ready(function () {
    var $tabsHolder = $('.dataobject-tabs.place-holder');

    if ($tabsHolder.length) {
        var url = $tabsHolder.data('url');
        $tabsHolder.attr('data-url', '');

        $tabsHolder.load(url, function () {
            $tabsHolder.removeClass('place-holder');
        });
    }

});
