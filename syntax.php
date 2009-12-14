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

    function getInfo() {
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }

    function getType() { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort() { return 155; }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{starred}\}',$mode,'plugin_starred');

    }

    function handle($match, $state, $pos, &$handler){
        return array();
    }

    function render($mode, &$R, $data) {
        if($mode != 'xhtml') return false;
        $R->info['cache'] = false;

        if(!$_SERVER['REMOTE_USER']){
            $R->cdata($this->getLang['login']);
            return true;
        }

        $action =& plugin_load('action','starred');
        $db = $action->_getDB();
        if(!$db) return true;

        $sql = "SELECT pid, stardate FROM stars WHERE login = ? ORDER BY stardate DESC";
        $res = $db->query($sql,$_SERVER['REMOTE_USER']);
        $arr = $db->res2arr($res);

        if(!count($arr)){
            $R->cdata($this->getLang('none'));
            return true;
        }

        $R->listu_open();
        foreach($arr as $row){
            $R->listitem_open(1);
            $R->listcontent_open();
            $R->internallink(':'.$row['pid']);
            $R->cdata(' '.dformat($row['stardate'],'%f'));

            $R->listcontent_close();
            $R->listitem_close();
        }
        $R->listu_close();
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
