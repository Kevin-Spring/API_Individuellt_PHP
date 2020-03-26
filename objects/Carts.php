<?php
include("../../config/database_handler.php");

class Cart{

    private $database_handler;

    public function __construct($databaseHandler){

        $this->database_handler = $databaseHandler;

    }

    /* Funktion för att lägga till saker i användarens varukorg */
    public function addToCart($product_id, $token_id){
        $return_object = new stdClass;

        //Först för att kunna lägga nått i vår varukorg behöver vi id:et från vår token.
        //DVS för att hålla våra foreign keys löften.
        $query_string = "SELECT id FROM tokens WHERE token=:token";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false ){

            $statementHandler->bindParam(":token", $token_id);
            $statementHandler->execute();

            $token_data = $statementHandler->fetch();

                if($token_data['id'] > 1){

                    //Om vårt fetchade id från token tabellen existerar skickar vi med det tillsammans med produktens id i vår insertCartToDatabase funktion.
                    $return = $this->insertCartToDatabase($product_id, $token_data['id']);

                    if($return !== false){
                        $return_object->state = "SUCCESS";
                        $return_object->message = "Product " . $product_id . " was added to your shoppingcart";
                        $return_object->products_in_cart = $this->getCartItems();   
                    } else {
                        $return_object->state = "ERROR";
                        $return_object->message = "Could not find that specific product!";
                    }

                } else {
                    //Eftersom vår token raderas i tabellen "tokens" efter en kvart,
                    //Så kommer den även raderas i vår tabell "carts" i och med våra foreign key-relationer.
                    //Lord praise mysql "CASCADE".
                    $return_object->state = "ERROR";
                    $return_object->message = "Your token has expired!!";
                }

        } else {
            $return_object->state = "ERROR";
            $return_object->message = "Satetement handler messed up!";
        }
    
    
    return json_encode($return_object);

}

    
//Vår funktion för att faktiskt lägga in produkten och användarens token i databasen.
private function insertCartToDatabase($product_ID_IN, $token_ID_IN){

        $query_string = "INSERT INTO carts(products_id, tokens_id) VALUES(:productID, :tokenID)";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false){

            $statementHandler->bindParam(":productID", $product_ID_IN);
            $statementHandler->bindParam(":tokenID", $token_ID_IN);

            $statementHandler->execute();

            //För att kunna hämta json-objektet behöver vi hämta informationen vi precis la in i databasen.
            $last_inserted_product_id = $this->database_handler->lastInsertId();

            $query_string = "SELECT id, products_id, tokens_id FROM carts WHERE id=:last_user_id";
            $statementHandler = $this->database_handler->prepare($query_string);

            $statementHandler->bindParam(':last_user_id', $last_inserted_product_id);

            $statementHandler->execute();

            return $statementHandler->fetch();

        } else {
            return false;
        }

    }

    //Funktion för att kunna ta bort enskilda produkter ur varukorgen
    public function removeFromCart($product_id){

        $return_object = new stdClass();

        //Med hjälp av LIMIT 1 tas bara en produkt bort.
        //Vilket är användbart om vi har fler av samma produkt.
        //Då raderas endast den med lägst id.
        $query_string = "DELETE FROM carts WHERE products_id = :product_id LIMIT 1";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false){

            $statementHandler->bindParam(":product_id", $product_id);

            $return = $this->getSingleCartItem($product_id);

            if($return !== false){

                $statementHandler->execute();

                $return_object->state = "SUCCESS";
                $return_object->message = "Product " . $product_id . " was deleted from your cart.";
                $return_object->products_in_cart = $this->getCartItems();

            } else {

                $return_object->state = "ERROR";
                $return_object->message = "product was not found";
                $return_object->products_in_cart = $this->getCartItems();

            }

        } else {

            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong when trying to DELETE product";

        }

        return json_encode($return_object);

    }

    //Tänka sig, ytterligare en funktion...
    //Funktion som hämtar produkterna som ligger i vår varukorg.
    private function getCartItems(){

        $return_object = new stdClass;

        $query_string = "SELECT products_id FROM carts";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false) {
    
            $statementHandler->execute();
            $return = $statementHandler->fetchAll();

            if(!empty($return)){
                $return_object->state = "SUCCESS";
                $return_object->product = $return;
            } else {
                $return_object->state = "ERROR";
                $return_object->message = "products was not found";
            }  
        
        } else {
            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong when trying to FETCH ALL products from CART";
            die();
        }
    
        return json_encode($return_object);

    }

    //Funktion som hämtar specifik produkt från vår varukorg.
    //Främst för att kunna använda den i funktionen removeFromCart
    private function getSingleCartItem($product_id_remove){
        $return_object = new stdClass;

        $query_string = "SELECT products_id FROM carts WHERE products_id = :product_id_remove";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false){

            $statementHandler->bindParam(":product_id_remove", $product_id_remove);
            $statementHandler->execute();

            $return = $statementHandler->fetch();

            if(!empty($return)){

                $return_object->state = "SUCCESS";
                $return_object->product = $return;

            } else {
                return false;
            }


        } else {
            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong when trying to FETCH your product from CART";
            die();
        }

        return json_encode($return_object);
    }

}




?>