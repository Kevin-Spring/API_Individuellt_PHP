<?php
include("../../objects/Products.php");
include("../../objects/Users.php");

$product_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);

$productID = ( !empty($_POST['product_id'] ) ? $_POST['product_id'] : -1 );
$tokenId = ( !empty($_POST['token'] ) ? $_POST['token'] : -1 );

if($user_handler->validateToken($tokenId) === false) {
    echo "Invalid token!";
    die;
} else {

    if($productID > -1) {

        $product_handler->setProductId($productID);
        echo $product_handler->fetchSingleProduct();

    } else {
    echo "Error: Missing parameter id!";
    }
}







?>
