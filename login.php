<?php

require_once __DIR__.'/bootstrap.php';
require __DIR__.'/classes/Session.class.php';
$session = new Session();

if ($_SERVER['REQUEST_METHOD'] != 'POST' || $session->issetLogin()) {
    header("Location:index.php");
    exit();
}

require __DIR__.'/classes/Database.class.php';

$user = is_string($_POST['username'] ?? null) ? $_POST['username'] : '';
$pass = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

$db = new LRSys();

if(isset($_POST['keepalive'])){
    $db->set_keepalive(TRUE);
}

if(!$db->login($user, $pass)){
    $_SESSION['TYP'] = 'LOG';
}

header("Location:login.php");

?>