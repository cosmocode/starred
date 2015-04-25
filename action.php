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
     * @param bool $inneronly TBD
     * @param bool $print Should the HTML be printed or returned?
     * @return null|string
     */
    function tpl_starred($inneronly=false, $print=true){
        global $ID;
        if(!isset($_SERVER['REMOTE_USER'])) return;

        $dt = $this->_starmode();
        
        $ret = '';

        if(!$inneronly) $ret .= '<a href="'.wl($ID,array('do'=>'startoggle')).'" id="plugin__starred">';
        if($dt){
            $ret .= '<img src="'.DOKU_BASE.'lib/plugins/starred/pix/star.png" width="16" height="16" title="'.$this->getLang('star_on').'" alt="★" />';
        }else{
            $ret .= '<img src="'.DOKU_BASE.'lib/plugins/starred/pix/star_grey.png" width="16" height="16" title="'.$this->getLang('star_off').'" alt="☆" />';
        }
        if(!$inneronly) $ret .= '</a>';
        if($print) echo $ret;
        return $ret;
    }

    /**
     * Print the current's user starred pages
     * @param bool $min 
     * @param int|string $limit limit of listed pages
     * @param bool $print Should the HTML be printed or returned?
     * @return null|string
     */
    function tpl_starred_pages($min=false, $limit='',$print=true) {
        if(!isset($_SERVER['REMOTE_USER'])) return;

        $db = $this->_getDB();
        if(!$db) return true;

        $sql = "SELECT pid, stardate FROM stars WHERE ";

        global $auth;
        if ($auth && !$auth->isCaseSensitive()) {
            $sql .= 'lower(login) = lower(?)';
        } else {
            $sql .= 'login = ?';
        }
        $sql .= " ORDER BY stardate DESC";
        if (is_int($limit) || ctype_digit($limit)) {
            $sql .= ' LIMIT ' . $limit;
        }
        $res = $db->query($sql,$_SERVER['REMOTE_USER']);
        $arr = $db->res2arr($res);
        
        $ret = '';

        $ret .= '<div class="plugin_starred">';
        if (!count($arr)) {
            if (!$min) {
            	$ret .= '<p>';
            	$ret .= hsc($this->getLang('none'));
            	$ret .= '</p>';
            }
        } else {
        	$ret .= '<ul>'.DOKU_LF;
            foreach($arr as $row){
                $ret .= '<li class="level1">';
                $ret .= '<div class="li">';
                $ret .= html_wikilink($row['pid']);
                if (!$min) {
            	    $ret .= hsc(' '.dformat($row['stardate'],'%f'));
                }
                $ret .= '</div>';
                $ret .= '</li>'.DOKU_LF;
            }
            $ret .= '</ul>';
        }
        $ret .= '</div>'.DOKU_LF;
        
        if($print) echo $ret;
        return $ret;
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
