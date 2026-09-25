<?php

require_once __DIR__.'/bootstrap.php';
require __DIR__.'/classes/Session.class.php';
require __DIR__.'/classes/Ranking.class.php';
require __DIR__.'/classes/RememberMe.class.php';

// Logging out changes state, so it must be a same-site request (see SESSION_SAMESITE).
$session  = new Session();

if($session->issetLogin()){
    $ranking = new Ranking();
    $ranking->updateTimePlayed();
}

RememberMe::forget();
$session->logout();

header("Location:index.php");
exit();
