<?php

require_once "tokenvalidation.inc.php";
require_once "../Classes/User.php";

$data = json_decode(file_get_contents("php://input"), true);

if($data['type'] == "load"){
    $user = new User($userId);
    $user->getProfileDetails();
}

if($data['type'] == "updateProfile"){
    $user = new User($userId);
    $user->updateProfileDetails($data['fullname'], $data['username'], $data['company'], $data['telnumber']);
}

