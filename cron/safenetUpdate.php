<?php

require_once __DIR__.'/../bootstrap.php';
// Cron/maintenance script: never reachable as a web page.
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require __DIR__.'/../classes/PDO.class.php';

$pdo = PDO_DB::factory();

$sql = "DELETE FROM safeNet WHERE TIMESTAMPDIFF(SECOND, NOW(), endTime) < 0";
$pdo->query($sql);

$sql = "SELECT IP, reason FROM safeNet";
$data = $pdo->query($sql)->fetchAll();

if(sizeof($data) > 0){
    
    $txt = 'Dolan, send this brief to the FBI please: '."<br/><br/>";
    
    for($i=0;$i<sizeof($data);$i++){

        switch($data[$i]['reason']){

            case 1:
                $reason = 'DDoS';
                break;
            default:
                $reason = 'Unknown';
                break;

        }

        $txt .= 'IP ['.long2ip($data[$i]['ip']).'] caught for '.$reason."<br/>";

    }

    $sql = "SELECT id FROM npc WHERE npcType = 50 LIMIT 1";
    $safeNetID = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->id;
    
    //delete old txt if exists
    $sql = SqlQuery::make('SELECT software_texts.id 
            FROM software_texts
            INNER JOIN software
            ON software.id = software_texts.id
            WHERE software.userID = ? AND software.isNPC = \'1\' AND softType = \'30\'', [$safeNetID]);

    $data = $pdo->query($sql)->fetchAll();    
    
    if(sizeof($data) > 0){

        for($i=0;$i<sizeof($data);$i++){
            
            $sql = SqlQuery::make('DELETE FROM software WHERE id = ? LIMIT 1', [$data[$i]['id']]);
            $pdo->query($sql);
            
            $sql = SqlQuery::make('DELETE FROM software_texts WHERE id = ? LIMIT 1', [$data[$i]['id']]);
            $pdo->query($sql);
            
        }
        
    }

    $sql = SqlQuery::make('INSERT INTO software (id, userID, softName, softVersion, softSize, softRam, softType, softLastEdit, softHidden, softHiddenWith, isNPC, licensedTo)
            VALUES (\'\', ?, \'Fwd to FBI\', \'0\', \'1\', \'0\', \'30\', NOW(), \'0\', \'0\', \'1\', \'0\')', [$safeNetID]);
    $pdo->query($sql);

    $txtID = $pdo->lastInsertID();

    $sql = SqlQuery::make('INSERT INTO software_texts (id, userID, isNPC, text, lastEdit) VALUES (?, ?, \'1\', ?, NOW())', [$txtID, $safeNetID, $txt]);
    $pdo->query($sql);    
    
}

?>
