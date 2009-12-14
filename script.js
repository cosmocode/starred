/**
 * Attach AJAX action to the star
 */
addInitEvent(function(){
    var obj = $('plugin__starred');
    if(!obj) return;

    addEvent(obj,'click',function(e){
        var ajax = new sack(DOKU_BASE + 'lib/exe/ajax.php');
        ajax.AjaxFailedAlert = '';
        ajax.encodeURIString = false;
        if(ajax.failed) return true;

        ajax.elementObj = obj;
        ajax.setVar('call','startoggle');
        ajax.setVar('id',JSINFO['id']);
        ajax.runAJAX();

        e.preventDefault();
        e.stopPropagation();
        return false;
    });

});
