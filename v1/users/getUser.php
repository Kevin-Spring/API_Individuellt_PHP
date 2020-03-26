<?php

include("../../objects/Users.php");

$username = $_POST['username'];
$password = $_POST['password'];

$user_handler = new User($databaseHandler);

print_r($user_handler->loginUser($username, $password));

?>