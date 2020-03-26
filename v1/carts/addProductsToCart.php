<?php

include("../../objects/Products.php");
include("../../objects/Users.php");
include("../../objects/Carts.php");

$products_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);
$cart_handler = new Cart($databaseHandler);

if(!empty($_POST['token'])){

    if(!empty($_POST['product_id'])){

        $token = $_POST['token'];

        if($user_handler->validateToken($token) === false){
            $retObject = new stdClass();
            $retObject->error = "Token is invalid";
            echo json_encode($retObject);
            die();
        }

        echo $cart_handler->addToCart($_POST['product_id'], $_POST['token']);


    } else {

        $retObject = new stdClass();
        $retObject->error = "No prodcut id found!";
        echo json_encode($retObject);
    
    } 
    
} else {

        $retObject = new stdClass();
        $retObject->error = "No token found!";
        echo json_encode($retObject);
}



?>