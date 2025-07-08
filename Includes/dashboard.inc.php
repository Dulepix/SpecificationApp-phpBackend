<?php

require_once "tokenvalidation.inc.php";
require_once '../Classes/Products.php'; 
require_once "../Classes/User.php";

$data = json_decode(file_get_contents("php://input"), true);

if($data['type'] == "pageload"){
    $user = new User($userId);
    $user->getSpecifications();
}

if($data['type'] == "searchProducts"){
    $products = new Products();
    $products->searchProducts($data['product'], $data['offset']);
}

if($data['type'] == "createSpecification"){
    $user = new User($userId);
    $user->insertSpecification($data['specName'], $data['visibility'], $data['price'], $data['products']);
}