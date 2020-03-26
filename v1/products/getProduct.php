<?php
include("../../objects/Products.php");
include("../../objects/Users.php");

$product_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);

$productID = ( !empty($_POST['product_id'] ) ? $_POST['product_id'] : -1 );
$token_IN = ( isset($_POST['token']) ? $_POST['token'] : '' );


if(!empty($token_IN)){

    $retObject = new stdClass;

    if($user_handler->validateToken($tokenId) === false) {
       
        $retObject->error =  "Invalid token!";
     
    } else {
    
        if($productID > -1) {
    
            $product_handler->setProductId($productID);
            echo $product_handler->fetchSingleProduct();
            die();
    
        } else {
            $retObject->error =  "Error: Missing parameter id!";
        }
    }

    echo json_encode($retObject);

} else {
    $retObject = new stdClass;
    $retObject->error = "Invalid token!";
    echo json_encode($retObject);
}









?>
