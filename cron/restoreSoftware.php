<?php

require_once __DIR__.'/../bootstrap.php';
// Cron/maintenance script: never reachable as a web page.
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
//function: restore npc software according to originalsoftware table on mysql

require __DIR__.'/../classes/PDO.class.php';

$pdo = PDO_DB::factory();

//talvez dá pra otimizar com um left / right join aqui..

$sql = "SELECT id, npcID, softName, softVersion, softRam, softSize, softType FROM software_original";
$query = $pdo->query($sql);

while($row = $query->fetch(PDO::FETCH_OBJ)){
 
    $newSql = SqlQuery::make('SELECT id FROM software WHERE userID = ? AND isNPC = 1 AND softName = ? AND softVersion = ?', [SqlQuery::num($row->npcid), $row->softname, SqlQuery::num($row->softversion)]);
    $newQuery = $pdo->query($newSql)->fetchAll();

    if(count($newQuery) == 0){

        $sqlQuery = "INSERT INTO software (id, softHidden, softHiddenWith, softLastEdit, softName, softSize,
            softType, softVersion, userID, isNPC, softRam) VALUES ('', '0', '0', NOW(), ?, ?, ?, ?, ?, '1', ?)";
        $sqlDown = $pdo->prepare($sqlQuery);
        $sqlDown->execute(array($row->softname, $row->softsize, $row->softtype, $row->softversion, $row->npcid, $row->softram));

    }

}

?>
