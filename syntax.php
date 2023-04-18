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

    /** @inheritdoc */
    function getType() { return 'substition'; }

    /** @inheritdoc */
    function getPType() { return 'block'; }

    /** @inheritdoc */
    function getSort() { return 155; }

    /** @inheritdoc */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{starred(?:>min)?(?:\|\d+)?}}',$mode,'plugin_starred');
    }

    /** @inheritdoc */
    function handle($match, $state, $pos, Doku_Handler $handler){
        preg_match('{{starred((?:>min)?)\|?(\d*)}}', $match, $matches);
        return array('min' => $matches[1] !== '',
                     'limit' => $matches[2]);
    }

    /** @inheritdoc */
    function render($mode, Doku_Renderer $R, $data) {
        if($mode != 'xhtml') return false;
        global $INPUT;

        /** @var Doku_Renderer_xhtml $R */
        $R->info['cache'] = false;

        if(!$INPUT->server->has('REMOTE_USER')){
            $R->cdata($this->getLang('login'));
            return true;
        }

        /** @var helper_plugin_starred $hlp */
        $hlp = plugin_load('helper', 'starred');
        $starred = $hlp->loadStars(null, $data['limit']);

        $R->doc .= '<div class="plugin_starred">';
        if(!count($starred)){
            if (!$data['min']) {
                $R->doc .= '<p>';
                $R->cdata($this->getLang('none'));
                $R->p_close();
            }
            $R->doc .= '</div>';
            return true;
        }

        $R->doc .= '<ul>';
        foreach($starred as $pid => $time){
            $R->listitem_open(1);
            $R->listcontent_open();
            $R->internallink(':'.$pid,null,null,false,'navigation');
            if (!$data['min']) {
                $R->cdata(' '.dformat($time,'%f'));
            }
            global $ID;
            $R->doc .= $hlp->starHtml($ID, $pid, false);
            $R->listcontent_close();
            $R->listitem_close();
        }
        $R->listu_close();
        $R->doc .= '</div>';
        return true;
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
