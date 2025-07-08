<?php

require_once "../Classes/WebLink/Link.php";
$link = new Link();

header("Access-Control-Allow-Origin: " . $link->getLink());
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

$input = file_get_contents("php://input");
$data = json_decode($input, true);

$email = trim($data['email']);
$password = trim($data['password']);

require_once "../Classes/Connection.php";
require_once "../Classes/Functions.php"; 
require_once "../Classes/Login.php";

$login = new Login($email, $password);
$login->CheckLoginDetails();