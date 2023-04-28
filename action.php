<?php
/**
 * DokuWiki Plugin starred (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

/**
 * Class action_plugin_starred
 */
class action_plugin_starred extends DokuWiki_Action_Plugin
{
    /** @var helper_plugin_starred */
    protected $helper;

    /**
     * action_plugin_starred constructor.
     */
    public function __construct()
    {
        $this->helper = plugin_load('helper', 'starred');
    }

    /** @inheritdoc */
    public function register(Doku_Event_Handler $controller)
    {

        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax_call_unknown');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_action_act_preprocess');

    }

    /**
     * Handle the ajax call
     *
     * @param Doku_Event $event AJAX_CALL_UNKNOWN
     * @param $param
     */
    public function handle_ajax_call_unknown(Doku_Event $event, $param)
    {
        if ($event->data != 'startoggle') return;
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
     * @param Doku_Event $event ACTION_ACT_PREPROCESS
     * @param $param
     */
    public function handle_action_act_preprocess(Doku_Event $event, $param)
    {
        if (substr(act_clean($event->data), 0, 10) != 'startoggle') return;
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
    public function tpl_starred($inneronly = false, $print = true)
    {
        global $ID;
        global $INPUT;

        if (!$INPUT->server->has('REMOTE_USER')) return false;
        $star_html = $this->helper->starHtml($ID, $ID, $inneronly, true);
        if ($print) {
            echo $star_html;
        }
        return $star_html;
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
                $ret .= html_wikilink(':'.$row['pid']);
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
