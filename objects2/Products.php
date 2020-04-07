<?php

include("../../config/database_handler.php");

class Product{

    private $database_handler;

    public function __construct($database_handler_IN) {
        $this->database_handler = $database_handler_IN;
    }

    //Funktion som hämtar enskilda produkter
    public function fetchSingleProduct($product_id) {

    $return_object = new stdClass();
    
    $query_string = "SELECT id, title, content, category, price ,date_posted FROM products WHERE id=:product_id";
    $statementHandler = $this->database_handler->prepare($query_string);
    
    if($statementHandler !== false) {

        $statementHandler->bindParam(":product_id", $product_id);
        $statementHandler->execute();
    
        $return = $statementHandler->fetch();

        if(!empty($return)){

            $return_object->state = "SUCCESS";
            $return_object->product = $return;

        } else {
            $return_object->state = "ERROR";
            $return_object->message = "product: ". $product_id." was not found";
            //Här måste jag echo:a ut mitt errormeddelande eftersom deleteProduct behöver "return false" för att kunna fungera korrekt.
            //Om jag bara skulle returnera false visas inget felmeddelande när den här funktionen körs ensam.
            //Finns 100% en bättre lösning.
            echo json_encode($return_object);
            return false;
            
        }
    
    } else {
            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong with STATEMENTHANDLER";
    }

    return json_encode($return_object);

    }
    
    //Funktion som hämtar alla våra produkter för page 1.
    public function fetchAllProdcuts($category, $order) {

        $return_object = new stdClass();
        
        $query_string = "SELECT id, title, content, price FROM products WHERE category LIKE :category ORDER BY date_posted $order LIMIT 5 OFFSET 0";
        $statementHandler = $this->database_handler->prepare($query_string);
    
        if($statementHandler !== false) {
    
        $statementHandler->bindParam(":category", $category);
        $statementHandler->execute();
        $return = $statementHandler->fetchAll(PDO::FETCH_ASSOC);

        $return_object->state = "SUCCESS";
        $return_object->products = $return;
    
        } else {
            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong when trying to FETCH ALL products";
            die();
        }

        return json_encode($return_object);
            
    }

    //Funktion som hämtar alla våra produkter för page 2.
    public function fetchAllProdcutsOffset($category, $order) {

        $return_object = new stdClass();
            
        $query_string = "SELECT id, title, content, price FROM products WHERE category LIKE :category ORDER BY date_posted $order LIMIT 5 OFFSET 5";
        $statementHandler = $this->database_handler->prepare($query_string);
            
            if($statementHandler !== false) {
            
                $statementHandler->bindParam(":category", $category);
                $statementHandler->execute();
                $return = $statementHandler->fetchAll(PDO::FETCH_ASSOC);
        
                $return_object->state = "SUCCESS";
                $return_object->products = $return;
            
            } else {
                $return_object->state = "ERROR";
                $return_object->message = "Something went wrong when trying to FETCH ALL products";
                die();
            }
        
        return json_encode($return_object);
                    
    }
        

    //Funktion som är överflödig, vad gör den här?
    //Fin är den iaf.
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

    //Funktion som ägnar sig åt att lägga in prdukter i vår databas
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

    //Funktion för att kunna redigera produkter i databasen
    //Speciellt specificerad för att kunna uppdatera enskilda värden dessutom
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

    //Funktion för att kunna radera produkter ur databas
    public function deleteProduct($data){

        $return_object = new stdClass();

        $query_string = "DELETE FROM products WHERE id=:product_id";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false){

            $statementHandler->bindParam(":product_id", $data['id']);

            $return = $this->fetchSingleProduct($data['id']);

            //Här kan jag inte göra checken om !empty($return) eftersom funktionen fetchSingleProduct(), kommer med json-meddelanden
            if($return !== false){

                $statementHandler->execute();
                $return_object->state = "SUCCESS";
                $return_object->message = "Product " . $data['id'] . " was deleted.";
            } else {
            //Här behövs inget else statement som säger att produkten inte hittades 
            //eftersom det ligger ett sånt i fetchSingleProduct.
                die();
            }
           

        } else {

            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong when trying to DELETE product";

        }

        return json_encode($return_object);

    }


}

?>