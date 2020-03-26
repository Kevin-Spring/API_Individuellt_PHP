<?php

include("../../objects/Users.php");

$user_handler = new User($databaseHandler);

$username = (isset($_POST['username']) ? $_POST['username'] : ''); 
$password = (isset($_POST['password']) ? $_POST['password'] : '');


if(!empty($username)){

    $retObject = new stdClass;

    if(!empty($password)){

        echo $user_handler->loginUser($username, $password);
        die();

    } else {
        $retObject->error = "Please enter the correct password!";
    }

    echo json_encode($retObject);

} else {
    $retObject = new stdClass;
    $retObject->error = "Please enter a username!";
    echo json_encode($retObject);
}

?>