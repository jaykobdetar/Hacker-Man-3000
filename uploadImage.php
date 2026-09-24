<?php

/*
 * Profile (t=1) and clan (t=2) picture upload.
 *
 * The original implementation handed the uploaded file to ImageMagick as-is and was disabled
 * because of a remote-code-execution bug. This version only accepts small JPEG/PNG/GIF files,
 * decodes them with GD and writes a freshly encoded JPEG, so nothing from the original file
 * (EXIF data, embedded scripts, polyglot payloads) is ever stored or served.
 *
 * Posted to directly for profile pictures; included by Clan::handlePost() for clan pictures.
 */

require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/classes/Session.class.php';

if(!isset($_SESSION['id'])){
    header("Location:index.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] != 'POST'){
    exit('Not a post req');
}

const UPLOAD_MAX_BYTES = 2 * 1024 * 1024;
const UPLOAD_MAX_PIXELS = 4000;

function upload_fail($message){
    http_response_code(400);
    exit(esc($message));
}

/** Resizes/crops $src to a $w x $h JPEG at $dest (crop = fill the box, otherwise fit inside). */
function upload_save_jpeg($src, $dest, $w = null, $h = null, $crop = false){
    $sw = imagesx($src);
    $sh = imagesy($src);
    if ($w === null) {
        $scale = min(1, 400 / max($sw, $sh));
        $w = max(1, (int) round($sw * $scale));
        $h = max(1, (int) round($sh * $scale));
    }
    $sx = $sy = 0;
    $cw = $sw;
    $ch = $sh;
    if ($crop) {
        $ratio = min($sw / $w, $sh / $h);
        $cw = (int) round($w * $ratio);
        $ch = (int) round($h * $ratio);
        $sx = (int) (($sw - $cw) / 2);
        $sy = (int) (($sh - $ch) / 2);
    }
    $dst = imagecreatetruecolor($w, $h);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $w, $h, $cw, $ch);
    if (!is_dir(dirname($dest))) {
        mkdir(dirname($dest), 0755, true);
    }
    $ok = imagejpeg($dst, $dest, 85);
    imagedestroy($dst);
    return $ok;
}

$file = $_FILES['image_upload'] ?? null;
if(!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])){
    upload_fail('Please choose an image to upload.');
}
if($file['size'] > UPLOAD_MAX_BYTES){
    upload_fail('The image is too big (maximum 2 MB).');
}

$info = @getimagesize($file['tmp_name']);
if($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF], true)){
    upload_fail('Only JPEG, PNG and GIF images are allowed.');
}
if($info[0] < 1 || $info[1] < 1 || $info[0] > UPLOAD_MAX_PIXELS || $info[1] > UPLOAD_MAX_PIXELS){
    upload_fail('The image dimensions are not allowed.');
}

switch($info[2]){
    case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($file['tmp_name']); break;
    case IMAGETYPE_PNG: $src = @imagecreatefrompng($file['tmp_name']); break;
    default: $src = @imagecreatefromgif($file['tmp_name']);
}
if(!$src){
    upload_fail('Cant upload this image');
}

$pdo = PDO_DB::factory();
$t = $_POST['t'] ?? null;

if($t == 1){

    $sql = SqlQuery::make('SELECT login FROM users WHERE id = ? LIMIT 1', [$_SESSION['id']]);
    $data = $pdo->query($sql)->fetch(PDO::FETCH_OBJ);
    if(!$data){
        upload_fail('Invalid user');
    }

    $filename = md5($data->login.$_SESSION['id']).'.jpg';
    upload_save_jpeg($src, BASE_PATH.'/images/profile/'.$filename);
    upload_save_jpeg($src, BASE_PATH.'/images/profile/thumbnail/'.$filename, 60, 60, true);
    upload_save_jpeg($src, BASE_PATH.'/images/profile/x60/'.$filename, 60, 60, true);
    $redirect = 'profile.php';

} elseif($t == 2){

    $sql = SqlQuery::make('SELECT clan.clanID, clan.name FROM clan_users INNER JOIN clan ON clan.clanID = clan_users.clanID WHERE clan_users.userID = ? LIMIT 1', [$_SESSION['id']]);
    $data = $pdo->query($sql)->fetch(PDO::FETCH_OBJ);
    if(!$data){
        upload_fail('You are not a clan member');
    }

    upload_save_jpeg($src, BASE_PATH.'/images/clan/'.md5($data->name.$data->clanid).'.jpg');
    $redirect = 'clan.php';

} else {
    upload_fail('Invalid upload type');
}

imagedestroy($src);

// When included from Clan::handlePost() the caller sets the message and redirects.
if(realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__){
    header("Location:$redirect");
    exit();
}
