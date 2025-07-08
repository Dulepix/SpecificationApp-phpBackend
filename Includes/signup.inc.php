<?php

require_once "../Classes/WebLink/Link.php";
$link = new Link();

header("Access-Control-Allow-Origin: " . $link->getLink());
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!isset($data['user'], $data['username'], $data['email'], $data['password'], $data['repeatpassword'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit();
}

$user = trim($data['user']);
$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];
$repeatpassword = $data['repeatpassword'];

require_once "../Classes/Connection.php";
require_once "../Classes/Signup.php";

$signup = new Signup($user, $username, $email, $password, $repeatpassword);
$signup->insertUser();


?>
