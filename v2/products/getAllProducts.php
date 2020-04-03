<?php
include('../../objects2/Products.php');
include('../../objects2/Users.php');

$product_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);

$token_IN = ( isset($_POST['token']) ? $_POST['token'] : '' );
$selected_option = ( isset($_POST['category']) ? $_POST['category'] : '' );
$order = ( isset($_POST['order']) ? $_POST['order'] : '' );

if(!empty($token_IN)){
    $retObject = new stdClass;

    if($user_handler->validateToken($token_IN) === false) {

        $retObject->error = "Invalid token!";

    } else {
        echo $product_handler->fetchAllProdcuts($selected_option, $order);
        die();
    }

    echo json_encode($retObject);

} else {
    $retObject = new stdClass;
    $retObject->error = "Invalid token!";
    echo json_encode($retObject);
}







?>