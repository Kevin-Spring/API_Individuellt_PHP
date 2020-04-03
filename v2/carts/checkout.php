<?php 

include("../../objects2/Products.php");
include("../../objects2/Users.php");
include("../../objects2/Carts.php");

$products_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);
$cart_handler = new Cart($databaseHandler);

$token_IN = ( isset($_POST['token']) ? $_POST['token'] : '' );
$firstname_IN = ( isset($_POST['firstname']) ? $_POST['firstname'] : '' );
$lastname_IN = ( isset($_POST['lastname']) ? $_POST['lastname'] : '' );
$address_IN = ( isset($_POST['address']) ? $_POST['address'] : '' );
$email_IN = ( isset($_POST['email']) ? $_POST['email'] : '' );

if(!empty($token_IN)){

    $retObject = new stdClass();

    if($user_handler->validateToken($token_IN) === false){
            
        $retObject->error = "Token is invalid";

    } else {

        echo $cart_handler->checkout($token_IN, $firstname_IN, $lastname_IN, $address_IN, $email_IN);
        die();
    }

    echo json_encode($retObject);

} else {
    $retObject = new stdClass;
    $retObject->error = "Invalid token!";
    echo json_encode($retObject);
}



?>