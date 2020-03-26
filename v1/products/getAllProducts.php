<?php
include('../../objects/Products.php');
include('../../objects/Users.php');

$product_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);

$tokenId = ( !empty($_POST['token'] ) ? $_POST['token'] : -1 );


if($user_handler->validateToken($tokenId) === false) {
    echo "Invalid token!";
    die;
} else {
    
    echo $product_handler->fetchAllProdcuts();
}





?>