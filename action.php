<?php
/**
 * DokuWiki Plugin starred (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Class action_plugin_starred
 */
class action_plugin_starred extends DokuWiki_Action_Plugin {
    /** @var helper_plugin_starred */
    protected $helper;

    /**
     * action_plugin_starred constructor.
     */
    public function __construct() {
        $this->helper = plugin_load('helper', 'starred');
    }

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller) {

        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax_call_unknown');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_action_act_preprocess');

    }

    /**
     * Handle the ajax call
     *
     * @param Doku_Event $event
     * @param $param
     */
    function handle_ajax_call_unknown(Doku_Event $event, $param) {
        if($event->data != 'startoggle') return;
        global $ID;
        global $INPUT;
        $ID = cleanID($INPUT->str('id'));

        $this->helper->toggleStar();
        $this->tpl_starred(true);
        $event->preventDefault();
        $event->stopPropagation();
    }

    /**
     * Handle the non-ajax call
     *
     * @param Doku_Event $event
     * @param $param
     */
    function handle_action_act_preprocess(Doku_Event $event, $param) {
        if(substr(act_clean($event->data), 0, 10) != 'startoggle') return;
        $id = substr($event->data, 11);
        $this->helper->toggleStar(null, $id);
        $event->data = 'show';
    }

    /**
     * Print the current star state for the current page
     * @param bool $inneronly TBD
     * @param bool $print Should the HTML be printed or returned?
     * @return bool|string
     */
    function tpl_starred($inneronly = false, $print = true) {
        global $ID;
        global $INPUT;

        if(!$INPUT->server->has('REMOTE_USER')) return false;
        $star_html = $this->helper->starHtml($ID, $ID, $inneronly, true);
        if($print) {
            echo $star_html;
        }
        return $star_html;
    }

}

// vim:ts=4:sw=4:et:enc=utf-8:
