/**
 * Attach AJAX action to the star
 */
jQuery(function(){
    'use strict';
    var obj = jQuery('#plugin__starred');

    obj.click(function(e) {
        jQuery.post(DOKU_BASE + 'lib/exe/ajax.php', {
                call: 'startoggle',
                id: JSINFO['id']
            }).done(function (data) {
                    obj.html(data);
                });

        e.preventDefault();
        e.stopPropagation();
        return false;
    });

    var starred_list = jQuery('div.plugin_starred');
    starred_list.find('a.plugin__starred').click(function(e) {
        jQuery.post(DOKU_BASE + 'lib/exe/ajax.php', {
            call: 'startoggle',
            id: jQuery(this).data('pageid')
        }).done(function (data) {
        });

        e.preventDefault();
        e.stopPropagation();

        jQuery(this).closest('li').remove();

    });

});
