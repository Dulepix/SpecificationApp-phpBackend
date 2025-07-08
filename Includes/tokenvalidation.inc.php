<?php
require_once "../Classes/WebLink/Link.php";
$link = new Link();

header("Access-Control-Allow-Origin: " . $link->getLink());
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../Classes/Connection.php";
require_once "../vendor/autoload.php";
require_once "../Classes/Functions.php";

$userId;
if(isset($_COOKIE["refreshToken"])){
    $userId = (new Functions)->validateToken($_COOKIE["refreshToken"]);
    if(!$userId){
        echo json_encode(["status" => "error", "message" => "Authentication failed, please log in again!"]);
        exit;
    }
}else{
    echo json_encode(["status" => "error", "message" => "Authentication failed, please log in again!"]);
    exit;
}
?>
