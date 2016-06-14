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

    function register(Doku_Event_Handler $controller) {

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
        if(substr(act_clean($event->data),0,10) != 'startoggle') return;
        $id = substr($event->data,11);
        $this->_startoggle($id);
        $event->data = 'show';
    }

    /**
     * toggle the star for the current user and page
     */
    function _startoggle($custom_ID = false){
        global $ID;
        if(!isset($_SERVER['REMOTE_USER'])) return;

        $db = $this->_getDB();
        if(!$db) return;

        if ($custom_ID === false) {
            $custom_ID = $ID;
        }

        $on = $this->_starmode($custom_ID); // currently on?

        if($on){
            //delete
            $sql = "DELETE FROM stars WHERE pid = ? AND login = ?";
            $db->query($sql,$custom_ID,$_SERVER['REMOTE_USER']);
        }else{
            //add
            $sql = "INSERT OR IGNORE INTO stars (pid,login,stardate) VALUES (?,?,?)";
            $db->query($sql,$custom_ID,$_SERVER['REMOTE_USER'],time());
        }

    }

    /**
     * check the star for the current user and page
     */
    function _starmode($custom_ID){
        $db = $this->_getDB();
        if(!$db) return;

        $sql = "SELECT stardate FROM stars WHERE pid = ? AND login = ?";
        $res = $db->query($sql,$custom_ID,$_SERVER['REMOTE_USER']);
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
     * Print the current star state for the current page
     * @param bool $inneronly TBD
     * @param bool $print Should the HTML be printed or returned?
     * @return bool|string
     */
    function tpl_starred($inneronly=false, $print=true){
        global $ID;
        if(!isset($_SERVER['REMOTE_USER'])) return false;
        $star_html =  $this->create_star_html($ID, $ID, $inneronly, true);
        if ($print) {
            echo $star_html;
        }
        return $star_html;
    }

    /**
     * Create the html for a star
     *
     * @param string $ID        The page where the star is supposed to appear.
     * @param string $custom_ID The page which the star is supposed to toggle.
     * @param bool   $inneronly
     * @param bool   $id        Must not be true more than once per page
     * @return string The html for the star
     */
    function create_star_html($ID, $custom_ID, $inneronly=false, $id=false) {
        $result = '';
        $dt = $this->_starmode($custom_ID);
        if($inneronly === false) {
            $result .= '<a href="' . wl($ID, array('do' => 'startoggle_' . $custom_ID)) . '" class="plugin__starred"';
            if($id === true) {
                $result .= ' id="plugin__starred">';
            } else {
                $result .= '>';
            }
        }

        if($dt){
            $result .= '<img src="'.DOKU_BASE.'lib/plugins/starred/pix/star.png" width="16" height="16" title="'.$this->getLang('star_on').'" alt="★" />';
        }else{
            $result .= '<img src="'.DOKU_BASE.'lib/plugins/starred/pix/star_grey.png" width="16" height="16" title="'.$this->getLang('star_off').'" alt="☆" />';
        }
        if(!$inneronly) {
            $result .=  '</a>';
        }
        return $result;
    }

}

// vim:ts=4:sw=4:et:enc=utf-8:
