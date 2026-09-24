<?php

/**
 * Forum integration.
 *
 * The original game embedded a modified phpBB 3.0.12 (in forum/) and kept its users, sessions and
 * per-clan boards in sync with the game. That phpBB version is end-of-life, has known security
 * vulnerabilities and does not run on PHP 8, so it was removed.
 *
 * This class keeps the old interface so game code does not need to know whether a forum exists.
 * Set FORUM_URL in .env to link players to an external forum (for example a current phpBB,
 * Discourse or Flarum install); account/clan synchronisation is not performed.
 */
class Forum {

    private $url;

    public function __construct(){
        $this->url = Config::get('FORUM_URL', '');
    }

    public function isEnabled(){
        return $this->url !== '';
    }

    public function showPosts($type){
        ?>
            <ul class="recent-posts">
                <li>
        <?php
        if ($this->isEnabled()) {
            echo sprintf(_('Visit the %sforum%s to talk with other players.'), '<a href="'.htmlspecialchars($this->url, ENT_QUOTES).'">', '</a>');
        } else {
            echo _('The forum is not available on this server.');
        }
        ?>
                </li>
            </ul>
        <?php
    }

    public function externalRegister($username, $pass, $email, $gameID){
    }

    public function login($user, $pass, $special){
    }

    public function logout(){
    }

    public function createForum($forum_name, $forum_clan){
    }

    public function getForumIDByGameID($gameID){
        return 0;
    }

    public function getForumClanID($clanID){
        return ['forum_id' => 0, 'parent_id' => 0];
    }

    public function setPermission($userID, $permissionType, $forumID){
    }

}
