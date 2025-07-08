<?php
require_once "../Classes/WebLink/Link.php";
$link = new Link();

header("Access-Control-Allow-Origin: " . $link->getLink());
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: text/plain");

$token = file_get_contents("php://input");

require_once "../Classes/Connection.php";
include_once("../Classes/EmailToken.php");

$tokenObject = new EmailToken($token);

$tokenObject->Verify();
