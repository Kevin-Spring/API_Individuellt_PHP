<?php
include('../../objects/Products.php');
include('../../objects/Users.php');

$product_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);

$token_IN = ( isset($_POST['token']) ? $_POST['token'] : '' );

if(!empty($token_IN)){
    $retObject = new stdClass;

    if($user_handler->validateToken($token_IN) === false) {

        $retObject->error = "Invalid token!";

    } else {
        echo $product_handler->fetchAllProdcuts();
        die();
    }

    echo json_encode($retObject);

} else {
    $retObject = new stdClass;
    $retObject->error = "Invalid token!";
    echo json_encode($retObject);
}







?>