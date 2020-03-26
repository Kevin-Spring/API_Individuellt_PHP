<?php

include("../../config/database_handler.php");

class Product{

    private $database_handler;
    private $product_id;

    public function __construct($database_handler_IN) {
        $this->database_handler = $database_handler_IN;
    }

    public function setProductId($product_id_IN) {
        $this->product_id = $product_id_IN;
    }

    public function fetchSingleProduct() {

    $return_object = new stdClass();
    
    $query_string = "SELECT id, title, content, category, price ,date_posted FROM products WHERE id=:product_id";
    $statementHandler = $this->database_handler->prepare($query_string);
    
    if($statementHandler !== false) {

        $statementHandler->bindParam(":product_id", $this->product_id);
        $statementHandler->execute();
    
        $return = $statementHandler->fetch();

        $return_object->state = "SUCCESS";
        $return_object->product = $return;
        
    
    } else {
            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong when trying to FETCH product";
        die();
    }

    return json_encode($return_object);

    }
    
    public function fetchAllProdcuts() {

    $return_object = new stdClass();
    
    $query_string = "SELECT id, title, content, category, price, date_posted FROM products";
    $statementHandler = $this->database_handler->prepare($query_string);
    
        if($statementHandler !== false) {
    
        $statementHandler->execute();
        $return = $statementHandler->fetchAll();

        $return_object->state = "SUCCESS";
        $return_object->product = $return;
    
        } else {
            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong when trying to FETCH ALL products";
            die();
        }

        return json_encode($return_object);
            
        }

    
    public function addProduct($title_param, $content_param, $category_param, $price_param) {
        $return_object = new stdClass();
    
        $return = $this->insertProductToDatabase($title_param, $content_param, $category_param, $price_param);

            //return returnerar false om det inte kommer något från fetch() men om det är true så att säga
            //kommer den returnera informationen. Med andra ord kan vi inte kolla om return === true.
            //För det är den aldrig tekniskt sätt
            if($return !== false) {
                $return_object->state = "SUCCESS";
                $return_object->product = $return;
            
            } else {
                $return_object->state = "ERROR";
                $return_object->message = "Something went wrong when trying to INSERT product";
            }

        return json_encode($return_object);
    }

    private function insertProductToDatabase($title_param_IN, $content_param_IN, $category_param_IN, $price_param_IN){
        $query_string = "INSERT INTO products (title, content, category, price) VALUES(:title_IN, :content_IN, :category_IN, :price_IN)";
        $statementHandler = $this->database_handler->prepare($query_string);
    
        if($statementHandler !== false) {
    
            $statementHandler->bindParam(":title_IN", $title_param_IN);
            $statementHandler->bindParam(":content_IN", $content_param_IN);
            $statementHandler->bindParam(":category_IN", $category_param_IN);
            $statementHandler->bindParam(":price_IN", $price_param_IN);


            $statementHandler->execute();
            
            //För att kunna hämta json-objektet behöver vi hämta informationen vi precis la in i databasen.
            $last_inserted_product_id = $this->database_handler->lastInsertId();

            $query_string = "SELECT title, content, category, price, date_posted FROM products WHERE id=:last_product_id";
            $statementHandler = $this->database_handler->prepare($query_string);

            $statementHandler->bindParam(':last_product_id', $last_inserted_product_id);

            $statementHandler->execute();

            return $statementHandler->fetch();

        } else {
            return false;
        }
    }


    public function updateProduct($data){

        $return_object = new stdClass();

        if(!empty($data['title'])){

            $query_string = "UPDATE products SET title=:title WHERE  id=:product_id";
            $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false) {

            $statementHandler->bindParam(":product_id", $data['id']);
            $statementHandler->bindParam(":title", $data['title']);

            $statementHandler->execute();

            } else {

                $return_object->state = "ERROR";
                $return_object->message = "Something went wrong when trying to UPDATE products TITLE";

            }


        }

        if(!empty($data['content'])){

            $query_string = "UPDATE products SET content=:content WHERE  id=:product_id";
            $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false) {

            $statementHandler->bindParam(":product_id", $data['id']);
            $statementHandler->bindParam(":content", $data['content']);

            $statementHandler->execute();

            } else {

                $return_object->state = "ERROR";
                $return_object->message = "Something went wrong when trying to UPDATE products CONTENT";

            }


        }

        if(!empty($data['category'])){

            $query_string = "UPDATE products SET category=:category WHERE  id=:product_id";
            $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false) {

            $statementHandler->bindParam(":product_id", $data['id']);
            $statementHandler->bindParam(":category", $data['category']);

            $statementHandler->execute();

            } else {

                $return_object->state = "ERROR";
                $return_object->message = "Something went wrong when trying to UPDATE products CATEGORY";

            }

            


        }

        if(!empty($data['price'])){

            $query_string = "UPDATE products SET price=:price WHERE  id=:product_id";
            $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false) {

                $statementHandler->bindParam(":product_id", $data['id']);
                $statementHandler->bindParam(":price", $data['price']);
    
                $statementHandler->execute();

            } else {

                $return_object->state = "ERROR";
                $return_object->message = "Something went wrong when trying to UPDATE products PRICE";

            }

            


        }

        $query_string = "SELECT id, title, content, category, price ,date_posted FROM products WHERE id=:product_id";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false) {

            $statementHandler->bindParam(":product_id", $data['id']);

            $statementHandler->execute();

            $return = $statementHandler->fetch();

            $return_object->state = "SUCCESS";
            $return_object->product = $return;

        } else {

            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong when trying to FETCH UPDATED product";

        }

        
        return json_encode($return_object);
        

    }

    public function deleteProduct($data){

        $return_object = new stdClass();

        $query_string = "DELETE FROM products WHERE id=:product_id";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false){

            $statementHandler->bindParam(":product_id", $data['id']);

            $statementHandler->execute();

            $return_object->state = "SUCCESS";
            $return_object->message = "Product " . $data['id'] . " was deleted from database.";

        } else {

            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong when trying to DELETE product";

        }

        return json_encode($return_object);

    }


}

?>