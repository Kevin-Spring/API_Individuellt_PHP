<?php

include("../../objects/Products.php");
$product_handler = new Product($databaseHandler);

$title_IN = ( isset($_POST['title']) ? $_POST['title'] : '' );
$content_IN = ( isset($_POST['content']) ? $_POST['content'] : '' );
$category_IN = ( isset($_POST['category']) ? $_POST['category'] : '' );
$price_IN = ( isset($_POST['price']) ? $_POST['price'] : '' );
$token_IN = ( isset($_POST['token']) ? $_POST['token'] : '' );

if(!empty($token_IN)){

    $retObject = new stdClass();

    if(!empty($title_IN)) {

        if(!empty($content_IN)) {

            if(!empty($category_IN)){
        
                if(!empty($price_IN)){
            
                    echo $product_handler->addProduct($title_IN, $content_IN, $category_IN, $price_IN);
                    die();
                    
                } else {

                    $retObject->error = "Error: Product must have a price!";
                }   
            
            }  else {
                
                $retObject->error = "Error: Product must have a category!";
            } 
                
        } else {
            $retObject->error = "Error: content cannot be empty!";
        }  
        
    } else {
        $retObject->error = "Error: titel cannot be empty!";
    }

    echo json_encode($retObject);

} else {
    $retObject = new stdClass();
    $retObject->error = "Token not found, please log in first!";
    echo json_encode($retObject);
}
 







?>