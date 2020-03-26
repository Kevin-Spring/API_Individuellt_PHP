<?php

include("../../objects/Products.php");
include("../../objects/Users.php");
include("../../objects/Carts.php");

$products_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);
$cart_handler = new Cart($databaseHandler);

$productID = ( isset($_POST['product_id'] ) ? $_POST['product_id'] : '' );
$token_IN = ( isset($_POST['token']) ? $_POST['token'] : '' );

if(!empty($token_IN)){

    $retObject = new stdClass();

    if(!empty($productID)){

        $token = $token_IN;

        if($user_handler->validateToken($token) === false){
            
            $retObject->error = "Token is invalid";

        }

        echo $cart_handler->addToCart($productID, $token_IN);
        die();


    } else {

        $retObject->error = "No prodcut id found!";
        
    } 

    echo json_encode($retObject);
    
} else {

        $retObject = new stdClass();
        $retObject->error = "No token found!";
        echo json_encode($retObject);
}



?>