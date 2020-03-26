<?php

include("../../objects/Products.php");
include("../../objects/Users.php");

$products_handler = new Product($databaseHandler);
$user_handler = new User($databaseHandler);

if(!empty($_POST['token'])){

    if(!empty($_POST['id'])){

        $token = $_POST['token'];

        if($user_handler->validateToken($token) === false){
            $retObject = new stdClass();
            $retObject->error = "Token is invalid";
            $retObject->errorcode = 80085;
            echo json_encode($retObject);
            die();
        }

        echo $products_handler->updateProduct($_POST);


    } else {

        $retObject = new stdClass();
        $retObject->error = "No id found!";
        $retObject->errorcode = 1339;
        echo json_encode($retObject);
    
    } 
    
} else {

        $retObject = new stdClass();
        $retObject->error = "No token found!";
        $retObject->errorcode = 1337;
        echo json_encode($retObject);
}

?>