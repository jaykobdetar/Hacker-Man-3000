<?php

require_once __DIR__.'/../bootstrap.php';
// Cron/maintenance script: never reachable as a web page.
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/../classes/PDO.class.php';

$pdo = PDO_DB::factory();

$sql = "SELECT region, id FROM storyline_launches WHERE effect = 0 AND status = 3 ORDER BY endTime DESC";
$data = $pdo->query($sql)->fetchAll();

if(count($data) > 0){
    
    $sql = SqlQuery::make('UPDATE storyline_launches SET effect = \'1\' WHERE id = ?', [$data['0']['id']]);
    $pdo->query($sql);
    
    $region = $data['0']['region'];
            
    //disable all npcs
    $sql = SqlQuery::make('UPDATE npc SET downUntil = \'2020-01-01 12:00:00\' WHERE region = ?', [$region]);
    $pdo->query($sql);
    
    //disable all bank accounts
    $sql = SqlQuery::make('SELECT id FROM npc WHERE npcType = 1 AND region = ?', [$region]);
    $data = $pdo->query($sql);
    
    $bankArr = Array();
    
    $i = 0;
    while($bankInfo = $data->fetch(PDO::FETCH_OBJ)){
        
        $bankArr[$i]['id'] = $bankInfo->id;
        
        $i++;
    }
    
    for($a=0;$a<$i;$a++){
        
        $sql = SqlQuery::make('DELETE FROM bankAccounts WHERE bankID = ?', [$bankArr[$a]['id']]);
        $pdo->query($sql);
        
    }
    
}

?>
