<?php

include("../../objects/Users.php");

$user_handler = new User($databaseHandler);

$create_username = (isset($_POST['username']) ? $_POST['username'] : ''); 
$create_password = (isset($_POST['password']) ? $_POST['password'] : '');
$create_email = (isset($_POST['email']) ? $_POST['email'] : '');

if(!empty($create_username)){

    $retObject = new stdClass;

    if(!empty($create_password)){

        if(!empty($create_email)){

            echo $user_handler->addUser($create_username, $create_password, $create_email);
            die();
        } else {

            $retObject->error = "Please enter an email!";
        }

    } else {
        $retObject->error = "Please enter a password";
    }

    echo json_encode($retObject);

} else {
    $retObject = new stdClass;
    $retObject->error = "Please enter a username!";
    echo json_encode($retObject);
}





?>