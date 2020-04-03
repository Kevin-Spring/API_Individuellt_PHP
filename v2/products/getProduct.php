<?php
include("../../objects2/Products.php");
include("../../objects2/Users.php");

$product_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);

$productID = ( !empty($_POST['product_id'] ) ? $_POST['product_id'] : -1 );
$token_IN = ( isset($_POST['token']) ? $_POST['token'] : '' );


if(!empty($token_IN)){

    $retObject = new stdClass;

    if($user_handler->validateToken($token_IN) === false) {
       
        $retObject->error =  "Invalid token!";
     
    } else {
    
        if($productID > -1) {
    
            echo $product_handler->fetchSingleProduct($productID);
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
