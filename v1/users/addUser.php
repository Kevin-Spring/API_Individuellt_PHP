<?php

include("../../objects/Users.php");

$user_handler = new User($databaseHandler);
$create_username = $_POST['username']; 
$create_password = $_POST['password'];
$create_email = $_POST['email'];

echo $user_handler->addUser($_POST['username'], $_POST['password'], $_POST['email']);



?>