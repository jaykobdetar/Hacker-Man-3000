<?php

require_once __DIR__.'/bootstrap.php';
require __DIR__.'/classes/Session.class.php';
$session = new Session();

if ($_SERVER['REQUEST_METHOD'] != 'POST' || $session->issetLogin()) {
    
    header("Location:index.php");
    exit();
    
}

require __DIR__.'/classes/Database.class.php';

$regLogin = is_string($_POST['username'] ?? null) ? $_POST['username'] : '';
$regPass = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$regEmail = is_string($_POST['email'] ?? null) ? $_POST['email'] : '';

$database = new LRSys();

if ($database->register($regLogin, $regPass, $regEmail)) {

    //Todo: header to email confirmation.

}

$_SESSION['TYP'] = 'REG';

header('Location:index.php');

?>
