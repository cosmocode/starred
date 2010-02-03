<?php
/**
 * DokuWiki Plugin starred (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_starred extends DokuWiki_Action_Plugin {

    function register(&$controller) {

       $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax_call_unknown');
       $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_action_act_preprocess');

    }

    function handle_ajax_call_unknown(&$event, $param) {
        if($event->data != 'startoggle') return;
        global $ID;
        $ID = cleanID($_REQUEST['id']);

        $this->_startoggle();
        $this->tpl_starred(true);
        $event->preventDefault();
        $event->stopPropagation();
    }

    function handle_action_act_preprocess(&$event, $param) {
        if($event->data != 'startoggle') return;
        $this->_startoggle();
        $event->data = 'show';
    }

    /**
     * toggle the star for the current user and page
     */
    function _startoggle(){
        global $ID;
        if(!isset($_SERVER['REMOTE_USER'])) return;

        $db = $this->_getDB();
        if(!$db) return;

        $on = $this->_starmode(); // currently on?

        if($on){
            //delete
            $sql = "DELETE FROM stars WHERE pid = ? AND login = ?";
            $db->query($sql,$ID,$_SERVER['REMOTE_USER']);
        }else{
            //add
            $sql = "INSERT OR IGNORE INTO stars (pid,login,stardate) VALUES (?,?,?)";
            $db->query($sql,$ID,$_SERVER['REMOTE_USER'],time());
        }

    }

    /**
     * check the star for the current user and page
     */
    function _starmode(){
        global $ID;
        $db = $this->_getDB();
        if(!$db) return;

        $sql = "SELECT stardate FROM stars WHERE pid = ? AND login = ?";
        $res = $db->query($sql,$ID,$_SERVER['REMOTE_USER']);
        $row = $db->res2row($res);
        return (int) $row['stardate'];
    }

    /**
     * load the sqlite helper
     */
    function _getDB(){
        $db = plugin_load('helper', 'sqlite');
        if(!is_null($db) && $db->init('starred',dirname(__FILE__).'/db/')){
            return $db;
        }else{
            msg($this->getLang('e_nosqlite'), -1);
            return false;
        }
    }

    /**
     * Print the current star state
     */
    function tpl_starred($inneronly=false){
        global $ID;
        if(!isset($_SERVER['REMOTE_USER'])) return;

        $dt = $this->_starmode();

        if(!$inneronly) echo '<a href="'.wl($ID,array('do'=>'startoggle')).'" id="plugin__starred">';
        if($dt){
            echo '<img src="'.DOKU_BASE.'lib/plugins/starred/pix/star.png" width="16" height="16" title="'.$this->getLang('star_on').'" alt="★" />';
        }else{
            echo '<img src="'.DOKU_BASE.'lib/plugins/starred/pix/star_grey.png" width="16" height="16" title="'.$this->getLang('star_off').'" alt="☆" />';
        }
        if(!$inneronly) echo '</a>';

    }

}

// vim:ts=4:sw=4:et:enc=utf-8:
