<?php

include("../../objects/Products.php");
$product_handler = new Product($databaseHandler);

$title_IN = ( isset($_POST['title']) ? $_POST['title'] : '' );
$content_IN = ( isset($_POST['content']) ? $_POST['content'] : '' );
$category_IN = ( isset($_POST['category']) ? $_POST['category'] : '' );
$price_IN = ( isset($_POST['price']) ? $_POST['price'] : '' );


if(!empty($title_IN)) {
    if(!empty($content_IN)) {

        echo $product_handler->addProduct($title_IN, $content_IN, $category_IN, $price_IN);

    } else {
        echo "Error: content cannot be empty!";
    }
} else {
    echo "Error: titel cannot be empty!";
}





?>