<?php
/**
 * DokuWiki Plugin starred (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_starred extends DokuWiki_Syntax_Plugin {

    function getType() { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort() { return 155; }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{starred(?:>min)?(?:\|\d+)?}}',$mode,'plugin_starred');
    }

    function handle($match, $state, $pos, &$handler){
        preg_match('{{starred((?:>min)?)\|?(\d*)}}', $match, $matches);
        return array('min' => $matches[1] !== '',
                     'limit' => $matches[2]);
    }

    function render($mode, &$R, $data) {
        if($mode != 'xhtml') return false;
        $R->info['cache'] = false;

        if(!isset($_SERVER['REMOTE_USER'])){
            $R->cdata($this->getLang('login'));
            return true;
        }

        $action =& plugin_load('action','starred');
        $db = $action->_getDB();
        if(!$db) return true;

        $sql = "SELECT pid, stardate FROM stars WHERE ";

        global $auth;
        if ($auth && !$auth->isCaseSensitive()) {
            $sql .= 'lower(login) = lower(?)';
        } else {
            $sql .= 'login = ?';
        }
        $sql .= " ORDER BY stardate DESC";
        if ($data['limit'] !== '') {
            $sql .= ' LIMIT ' . $data['limit'];
        }
        $res = $db->query($sql,$_SERVER['REMOTE_USER']);
        $arr = $db->res2arr($res);

        $R->doc .= '<div class="plugin_starred">';
        if(!count($arr)){
            if (!$data['min']) {
                $R->doc .= '<p>';
                $R->cdata($this->getLang('none'));
                $R->p_close();
            }
            $R->doc .= '</div>';
            return true;
        }

        $R->doc .= '<ul>';
        foreach($arr as $row){
            $R->listitem_open(1);
            $R->listcontent_open();
            $R->internallink(':'.$row['pid'],null,null,false,'navigation');
            if (!$data['min']) {
                $R->cdata(' '.dformat($row['stardate'],'%f'));
            }
            $R->listcontent_close();
            $R->listitem_close();
        }
        $R->listu_close();
        $R->doc .= '</div>';
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
