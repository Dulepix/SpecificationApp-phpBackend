<?php

require_once "tokenvalidation.inc.php";
require_once "../Classes/User.php";

$user = new User($userId);
echo json_encode($user->getUsername());
