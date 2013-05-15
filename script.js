/**
 * Attach AJAX action to the star
 */
jQuery(function(){
    var obj = jQuery('#plugin__starred');
    if(!obj) return;

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

});
