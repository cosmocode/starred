<?php
/**
 * DokuWiki Plugin starred (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Class helper_plugin_starred
 */
class helper_plugin_starred extends DokuWiki_Plugin {

    /**
     * load the sqlite helper
     *
     * @return helper_plugin_sqlite|false
     */
    public function getDB() {
        /** @var helper_plugin_sqlite $db */
        $db = plugin_load('helper', 'sqlite');
        if(!is_null($db) && $db->init('starred', dirname(__FILE__) . '/db/')) {
            return $db;
        } else {
            msg($this->getLang('e_nosqlite'), -1);
            return false;
        }
    }

    /**
     * toggle the star for the user and page
     * @param string|null $user defaults to current user
     * @param string|null $pageid defaults to current page
     */
    public function toggleStar($user = null, $pageid = null) {
        global $ID;

        // DB access
        $db = $this->getDB();
        if(!$db) return;

        // param defaults
        if($pageid === null) $pageid = $ID;
        if($user === null) $user = $_SERVER['REMOTE_USER'];
        if(blank($user)) return;

        $on = $this->checkStar($user, $pageid); // currently on?

        if($on) {
            //delete
            $sql = "DELETE FROM stars WHERE pid = ? AND login = ?";
            $db->query($sql, $pageid, $user);
        } else {
            //add
            $sql = "INSERT OR IGNORE INTO stars (pid,login,stardate) VALUES (?,?,?)";
            $db->query($sql, $pageid, $user, time());
        }
    }

    /**
     * check the star for the current user and page
     *
     * @param string|null $user defaults to current user
     * @param string|null $pageid defaults to current page
     * @return bool|int the time the star was added or false if not
     */
    public function checkStar($user = null, $pageid = null) {
        global $ID;

        // DB access
        $db = $this->getDB();
        if(!$db) return false;

        // param defaults
        if($pageid === null) $pageid = $ID;
        if($user === null) $user = $_SERVER['REMOTE_USER'];
        if(blank($user)) return false;

        $sql = "SELECT stardate FROM stars WHERE pid = ? AND login = ?";
        $res = $db->query($sql, $pageid, $user);
        $row = $db->res2row($res);
        return (int) $row['stardate'];
    }

    /**
     * Load the starred pages of a given user
     *
     * @param string|null $user defaults to current user
     * @param int $limit defaults to all
     * @return array|bool
     */
    public function loadStars($user = null, $limit = 0) {
        $result = array();

        $db = $this->getDB();
        if(!$db) return $result;

        if($user === null) $user = $_SERVER['REMOTE_USER'];
        if(blank($user)) return $result;

        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;

        $sql = "SELECT pid, stardate FROM stars WHERE ";

        if($auth && !$auth->isCaseSensitive()) {
            $sql .= 'lower(login) = lower(?)';
        } else {
            $sql .= 'login = ?';
        }
        $sql .= " ORDER BY stardate DESC";
        if($limit) {
            $sql .= ' LIMIT ' . $limit;
        }
        $res = $db->query($sql, $user);
        $arr = $db->res2arr($res);

        foreach($arr as $row) {
            $result[$row['pid']] = $row['stardate'];
        }

        return $result;
    }

    /**
     * Create the html for a star
     *
     * @param string $ID        The page where the star is supposed to appear.
     * @param string $pageid The page which the star is supposed to toggle.
     * @param bool   $inneronly
     * @param bool   $setid        Must not be true more than once per page
     * @return string The html for the star
     */
    public function starHtml($ID, $pageid, $inneronly=false, $setid=false) {
        $result = '';
        $dt = $this->checkStar(null, $pageid);
        if($inneronly === false) {
            $result .= '<a href="' . wl($ID, array('do' => 'startoggle_' . $pageid)) . '" data-pageid="'.$pageid.'" class="plugin__starred"';
            if($setid === true) {
                $result .= ' id="plugin__starred">';
            } else {
                $result .= '>';
            }
        }

        if($dt){
            $result .= '<span title="'.$this->getLang('star_on').'" class="starred on">';
            $result .= inlineSVG(__DIR__ . '/pix/star.svg');
            $result .= '</span>';
            //$result .= '<img src="'.DOKU_BASE.'lib/plugins/starred/pix/star.png" width="16" height="16" title="'.$this->getLang('star_on').'" alt="★" />';
        }else{
            $result .= '<span title="'.$this->getLang('star_off').'" class="starred off">';
            $result .= inlineSVG(__DIR__ . '/pix/star-outline.svg');
            $result .= '</span>';
            //$result .= '<img src="'.DOKU_BASE.'lib/plugins/starred/pix/star_grey.png" width="16" height="16" title="'.$this->getLang('star_off').'" alt="☆" />';
        }
        if(!$inneronly) {
            $result .=  '</a>';
        }
        return $result;
    }
}
