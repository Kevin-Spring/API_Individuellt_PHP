<?php 

include("../../objects/Products.php");
include("../../objects/Users.php");
include("../../objects/Carts.php");

$products_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);
$cart_handler = new Cart($databaseHandler);

$token_IN = ( isset($_POST['token']) ? $_POST['token'] : '' );

if(!empty($token_IN)){

    $retObject = new stdClass();

    if($user_handler->validateToken($token_IN) === false){
            
        $retObject->error = "Token is invalid";

    } else {

        echo $cart_handler->createCart($token_IN);
        die();
    }

    echo json_encode($retObject);

} else {
    $retObject = new stdClass;
    $retObject->error = "Invalid token!";
    echo json_encode($retObject);
}



?>