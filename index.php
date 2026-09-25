<?php

require_once __DIR__.'/bootstrap.php';
require 'config.php';
require __DIR__.'/classes/Session.class.php';
require __DIR__.'/classes/System.class.php';

$session = new Session();

if(!isset($_SESSION['id'])){

    if(isset($_GET['nologin'])){
        $_SESSION = [];
        session_destroy();
        header("Location:index");
        exit();
    }

    // Not logged in: try the "keep me logged in" cookie.
    require_once __DIR__.'/classes/RememberMe.class.php';
    $remember = new RememberMe();
    $remember->rememberlogin();

}

if (isset($_SESSION['id'])) {

    require_once __DIR__.'/classes/Player.class.php';
    $player = new Player($_SESSION['id']);

    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST)){

        $player->handlePost();

    }

    require 'template/contentStart.php';

    if($session->issetMsg()){
        $session->returnMsg();
    }

    if($_SESSION['ROUND_STATUS'] == 1){

        $player->showIndex();

    } else {

        $player->showGameOver();

    }

    require 'template/contentEnd.php';

} else {

    unset($_SESSION['GOING_ON']);
    require 'template/default.php';

}
