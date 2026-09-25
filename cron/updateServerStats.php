<?php

require_once __DIR__.'/../bootstrap.php';
// Cron/maintenance script: never reachable as a web page.
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require __DIR__.'/../classes/PDO.class.php';

$pdo = PDO_DB::factory();

//UPDATE SERVER STATS DO ROUND ATUAL

$sql = "SELECT COUNT(*) AS totalUsers FROM cache_profile";
$totalUsers = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->totalusers;

$sql = 'SELECT COUNT(*) AS total FROM users WHERE TIMESTAMPDIFF(DAY, lastLogin, NOW()) <= 14';
$activeUsers = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT COUNT(*) AS total FROM users_online';
$onlineUsers = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT 
            SUM(warezSent) AS tWarez, SUM(spamSent) AS tSpam, SUM(ddosCount) AS tDdos, SUM(hackCount) AS tHack, 
            SUM(timePlaying) AS tTime, SUM(moneyTransfered) AS mTransfered, SUM(moneyHardware) AS mHardware, 
            SUM(moneyEarned) AS mEarned, SUM(moneyResearch) AS mResearch, SUM(profileViews) AS tClicks,
            SUM(bitcoinSent) AS tBitcoin
        FROM users_stats';
$totalInfo = $pdo->query($sql)->fetch(PDO::FETCH_OBJ);

$sql = 'SELECT COUNT(*) AS totalClan FROM clan';
$totalClan = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->totalclan;

$sql = 'SELECT COUNT(*) AS total FROM clan_users';
$totalClanMembers = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT SUM(pageClicks) AS total FROM clan_stats';
$totalClanClicks = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT COUNT(*) AS total FROM clan_war_history';
$totalClanWar = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT COUNT(*) AS tLists FROM lists';
$totalListed = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->tlists;

$sql = 'SELECT COUNT(*) AS tVirus FROM virus';
$totalVirus = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->tvirus;

$sql = 'SELECT SUM(cash) AS tCash FROM bankAccounts';
$totalMoney = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->tcash;

$sql = 'SELECT COUNT(*) AS total FROM missions_history WHERE completed = 1';
$totalMissions = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT COUNT(*) AS total FROM mails WHERE mails.from > 0';
$totalMails = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT COUNT(*) AS total FROM internet_connections';
$totalConnections = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT COUNT(*) AS total FROM software_research';
$totalResearched = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT COUNT(*) AS total FROM processes';
$totalTasks = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT COUNT(*) AS total FROM software';
$totalSoftware = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT COUNT(*) AS total FROM software_running';
$totalSoftwareRunning = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

$sql = 'SELECT COUNT(*) AS total FROM hardware';
$totalServers = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;



$sql = SqlQuery::make('UPDATE round_stats 
        SET
            totalUsers = ?, 
            activeUsers = ?,
            onlineUsers = ?,
            usersClicks = ?,
            warezSent = ?, 
            spamSent = ?,
            bitcoinSent = ?,
            mailSent = ?, 
            ddosCount = ?, 
            hackCount = ?, 
            clans = ?,
            clansWar = ?,
            clansMembers = ?,
            clansClicks = ?,
            timePlaying = ?, 
            totalListed = ?, 
            totalVirus = ?, 
            totalMoney = ?,
            moneyHardware = ?,
            moneyEarned = ?,
            moneyTransfered = ?,
            moneyResearch = ?,
            missionCount = ?,
            totalConnections = ?,
            researchCount = ?,
            totalTasks = ?,
            totalSoftware = ?,
            totalRunning = ?,
            totalServers = ?
        ORDER BY id DESC LIMIT 1', [$totalUsers, $activeUsers, $onlineUsers, $totalInfo->tclicks, $totalInfo->twarez, $totalInfo->tspam, $totalInfo->tbitcoin, $totalMails, $totalInfo->tddos, $totalInfo->thack, $totalClan, $totalClanWar, $totalClanMembers, $totalClanClicks, $totalInfo->ttime, $totalListed, $totalVirus, $totalMoney, $totalInfo->mhardware, $totalInfo->mearned, $totalInfo->mtransfered, $totalInfo->mresearch, $totalMissions, $totalConnections, $totalResearched, $totalTasks, $totalSoftware, $totalSoftwareRunning, $totalServers]);
$pdo->query($sql);

?>
