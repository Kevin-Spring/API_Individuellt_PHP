<?php

include("../../objects/Products.php");
include("../../objects/Users.php");


$products_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);

$id_IN = ( isset($_POST['id']) ? $_POST['id'] : '' );
$token_IN = ( isset($_POST['token']) ? $_POST['token'] : '' );

if(!empty($token_IN)){

    $retObject = new stdClass();

    if(!empty($id_IN)){

        $token = $token_IN;

        $is_admin = $user_handler->isAdmin($token);
 
        if($is_admin === false) {
            echo "You are not admin!";
            die();
        }

        if($user_handler->validateToken($token) === false){

            $retObject->error = "Token is invalid";
            die();
        }
        
        echo $products_handler->deleteProduct($_POST);
        die();

    } else {

        $retObject->error = "No id found!";
    } 

    echo json_encode($retObject);

} else {
        $retObject = new stdClass();
        $retObject->error = "No token found!";
        echo json_encode($retObject);
}



?>