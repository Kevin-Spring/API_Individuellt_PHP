<?php
include("../../config/database_handler.php");

class Cart{

    private $database_handler;

    public function __construct($databaseHandler){

        $this->database_handler = $databaseHandler;

    }


    //Funktion för att kunna ta bort enskilda produkter ur varukorgen
    public function removeFromCart($product_id, $token_id){

        $return_object = new stdClass();

        $check_token = $this->getTokenId($token_id);
        $check_cart = $this->getCartId($check_token);

                $carts_data = $check_cart['id'];

                    if(!empty($carts_data)){

                        //Om vårt fetchade id från token tabellen existerar skickar vi med det tillsammans med produktens id med vår removeFromCartDb funktion.
                        $return = $this->removeFromCartDb($product_id);

                            if($return !== false){
                                $return_object->state = "SUCCESS";
                                $return_object->message = "Product " . $product_id . " was removed from your shoppingcart";
                                $return_object->products_in_cart = $this->getCartItems($carts_data);   
                            } else {
                                $return_object->state = "ERROR";
                                $return_object->message = "Could not find that specific product!";
                                $return_object->products_in_cart = $this->getCartItems($carts_data);  
                            }

                    } else {
                            $return_object->state = "ERROR";
                            $return_object->message = "Please create a shoppingcart!";
                        }


        return json_encode($return_object);

    } 

    //Funktion för att radera produkter från databas som ligger användares i varukorg
    private function removeFromCartDb($product_id){

        $return_object = new stdClass();

        //Med hjälp av LIMIT 1 tas bara en produkt bort.
        //Vilket är användbart om vi har fler av samma produkt.
        //Då raderas endast den med lägst id.
        $query_string = "DELETE FROM prodcutsInCarts WHERE products_id = :product_id LIMIT 1";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false){

            $statementHandler->bindParam(":product_id", $product_id);

            $return = $this->getSingleCartItem($product_id);

                if($return !== false){

                    $statementHandler->execute();

                    return true;

                } else {

                    return false;

                }

        } else {

            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong when trying to DELETE product";

        }

        return json_encode($return_object);
    }



    //Tänka sig, ytterligare en funktion...
    //Funktion som hämtar produkterna som ligger i vår varukorg.
    private function getCartItems($cart_id){

        $return_object = new stdClass;

        $query_string = "SELECT id, products_id, price_data FROM prodcutsInCarts WHERE carts_id = :cart_id";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false) {
    
            $statementHandler->bindParam(":cart_id", $cart_id);
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

        $query_string = "SELECT products_id FROM prodcutsInCarts WHERE products_id = :product_id_remove";
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
        }

        return json_encode($return_object);
    }


    /* ------ POTENTIELL LÖSNING -------- */
    
    //Om en token inte raderas ur token tabellen vid inaktivitet, utan bara uppdaterar tokens timestamp-värde varje gång man loggar in.
    //Så kommer inte cartsen försvinna, med andra ord kommer inte checkouten försvinna.
    //Då får en inloggad user alltid samma token id och token, men det är nytt värde i den varje gång något slår mot databasen.
    //För att då radera en cart vid inaktivitet och spara carts som har blivit utcheckade kan vi lägga in ytterligare en kolumn i tabellen carts. 
    //Nya tabellen med namnet t.ex. ”status” kommer vara beroende av värdet i den kolumnen och våra sql-frågor kommer då radera de som är inaktiva och spara de som har ”status = utcheckad” exempelvis.
    //Då får alla nyskapade carts ha ett DEFAULT-VÄRDE i status-kolumnen som indikerar att de inte checkats ut.
    //Och när vi checkar ut får cartsens ”status-värden” då bli ändrade.
    //Då kommer vi kunna se vilka våra utcheckade carts är i checkouten.

    //Funktion för att kika om en användare kan göra en checkout eller inte.
    public function checkout($token_id, $firstname, $lastname, $address, $email){

        $return_object = new stdClass();

        $check_token = $this->getTokenId($token_id);
        $check_cart = $this->getCartId($check_token);

                $carts_data = $check_cart['id'];

                    if(!empty($carts_data)){

                        $return = $this->validateCheckout($carts_data, $firstname, $lastname, $address, $email);

                            if($return !== false){
                                $return_object->state = "SUCCESS";
                                $return_object->message = "Your have successfully checked out!";
                                $return_object->products_in_cart = $this->getCheckoutItems($carts_data);

                            } else {
                                $return_object->state = "ERROR";
                                $return_object->message = "Could not check out order!";
                            }

                    } else {
                        $return_object->state = "ERROR";
                        $return_object->message = "Please create a shoppingcart!";
                    }

        return json_encode($return_object);

    }

    //Funktion för att lägga in samtlig användarinfo i checkout db.
    private function insertIntoCheckout($carts_ID_IN, $firstname_IN, $lastname_IN, $address_IN, $email_IN){

        $this->updateCartStatus($carts_ID_IN);

        $query_string = "SELECT SUM(price_data) FROM prodcutsInCarts WHERE carts_id = :cart_id";

        $statementHandler = $this->database_handler->prepare($query_string);

        $statementHandler->bindParam(":cart_id", $carts_ID_IN);
        $statementHandler->execute();

        $total_price = $statementHandler->fetch();

        if($total_price["SUM(price_data)"] > 0){


            $query_string = "INSERT INTO checkout(carts_id, firstname, lastname, address, email, total_price) VALUES(:cart_id_in, :firstname_IN, :lastname_IN, :address_IN, :email_IN, :total_price_IN)"; 
            $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false){

                $statementHandler->bindParam(":cart_id_in", $carts_ID_IN);
                $statementHandler->bindParam(":firstname_IN", $firstname_IN);
                $statementHandler->bindParam(":lastname_IN", $lastname_IN);
                $statementHandler->bindParam(":address_IN", $address_IN);
                $statementHandler->bindParam(":email_IN", $email_IN);
                $statementHandler->bindParam(":total_price_IN", $total_price["SUM(price_data)"]);
                $statementHandler->execute();

            } else {
                echo "statementhandler got it wrong in inserIntoCheckout!";
            }

        }


    }

    //Funktion för att ändra statusen på en cart från "icke checked out" till "checked out". Där 1:or är att man har checkat ut totalt.
    private function updateCartStatus($cart_id){

        $query_string = "UPDATE carts2 SET status = 1 WHERE id = :cart_id";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false){
            $statementHandler->bindParam(":cart_id", $cart_id);
            $statementHandler->execute();
        } else {
            echo "statementhandler in updateCartStatus could not be created";
        }
    }

    //Funktion som stoppar användaren ifrån att skapa flera checkouts med samma id.
    //Överflödig nu i v2 när en användare bara kan handla en gång och dessutom kommer knytas an till flera varukorgar om användaren kan handla fler gånger.
    //Just nu fungerar den som en portal till insertIntoCheckout().
    private function validateCheckout($cart_id,$firstname_IN, $lastname_IN, $address_IN, $email_IN){
        
        $return_object = new stdClass();

        $query_string = "SELECT COUNT(id) FROM checkout WHERE carts_id= :cart_id";
        $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false ){

                $statementHandler->bindParam(":cart_id", $cart_id);
                $statementHandler->execute();

                $numberOfCarts = $statementHandler->fetch()[0];

                if($numberOfCarts < 1) {

                    $this->insertIntoCheckout($cart_id, $firstname_IN, $lastname_IN, $address_IN, $email_IN);
                    $return_object->state = "SUCCESS!";
                    $return_object->message = "Created cart for user";

            } else {

                $return_object->message = "User already have an active cart";

            }

        }

        return json_encode($return_object);
    }

    //Funktion för att ge kunden en bekräftelse på beställda varor
    private function getCheckoutItems($cart_id){
        $return_object = new stdClass;

        $query_string = "SELECT carts_id, firstname, lastname, address, email, total_price FROM checkout WHERE carts_id = :cart_id";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false){

            $statementHandler->bindParam(":cart_id", $cart_id);
            $statementHandler->execute();

            $return = $statementHandler->fetch();

            if(!empty($return)){

                $return_object->state = "SUCCESS";
                $return_object->product = $return;

            } else {
                return false;
            }


        } else {
            return false;
        }

        return json_encode($return_object);
    }

    //Funktion för att kolla om användaren med tokenen redan har en varukorg och om den är utcheckad eller inte.
    //Om inte skapas en ny
    private function validateCart($token_id){

        $return_object = new stdClass();

        $query_string = "SELECT COUNT(id), status FROM carts2 WHERE tokens_id= :token_id";
        $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false ){

                $statementHandler->bindParam(":token_id", $token_id);
                $statementHandler->execute();

                $cart_data = $statementHandler->fetch();

                if($cart_data['COUNT(id)'] < 1) {

                    $this->createCart($token_id);
                    $return_object->state = "SUCCESS!";
                    $return_object->message = "Created cart for user";

                } else {

                    if($cart_data['status'] == 1){

                    /* --------- ANVÄNDARE KAN BARA HANDLA EN GÅNG FÖRTILLFÄLLET --------- */

                        //$this->createCart($token_id);
                        echo "User has already made his purchase";
                        die();
    
                    } 

                    $return_object->message = "User already have an active cart";

                }
        }

        return json_encode($return_object);

    }

    //Funktion för att skapa ny varukorg
    private function createCart($token_id){

        $return_object = new stdClass();
                    
        $query_string = "INSERT INTO carts2(tokens_id) VALUES(:token_id_in)"; 
        $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false){
                $currentTime = time();

                $statementHandler->bindParam(":token_id_in", $token_id);
                $statementHandler->execute();
        
                $return_object->state = "SUCCESS";
                $return_object->message = "Created a cart for user.";
        
            } else {
                $return_object->state = "ERROR";
                $return_object->message = "Could not create statementhadnler";
                }        

        return json_encode($return_object);

    }


        /* Funktion för att lägga till saker i användarens varukorg */
        public function addToNewCart($product_id, $token_id){
            $return_object = new stdClass;
    
            $check_token = $this->getTokenId($token_id);

            if(!empty($check_token)){
                
                $this->validateCart($check_token['id']);

                $check_cart = $this->getCartId($check_token);

                if(!empty($check_cart)){

                            $product_data = $this->getProductData($product_id);

                            if(!empty($product_data['id'])){

                                $return = $this->insertNewCartToDatabase($product_id, $check_cart['id'], $product_data['price']);

                                if($return !==false){ 
                                    $return_object->state = "SUCCESS";
                                    $return_object->message = "Product " . $product_id . " was added to your shoppingcart";
                                    $return_object->products_in_cart = $this->getCartItems($check_cart['id']);
                                }

                            } else {
                                $return_object->state = "ERROR";
                                $return_object->message = "Could not find that specific product!";
                            }
                } else {
                    $return_object->state = "ERROR";
                    $return_object->message = "Please create a shoppingcart!";
                }

            } else {
                $return_object->state = "ERROR";
                $return_object->message = "Could not find token id!";
            }
                      
            return json_encode($return_object);
    
            } 

    //Vår funktion för att faktiskt lägga in produkten och användarens token i databasen.
    private function insertNewCartToDatabase($product_ID_IN, $carts_ID_IN, $price_data){

    $query_string = "INSERT INTO prodcutsInCarts(products_id, carts_id, price_data) VALUES(:productID, :cartID, :price_data)";
    $statementHandler = $this->database_handler->prepare($query_string);

    if($statementHandler !== false){

        $statementHandler->bindParam(":productID", $product_ID_IN);
        $statementHandler->bindParam(":cartID", $carts_ID_IN);
        $statementHandler->bindParam(":price_data", $price_data);

        $statementHandler->execute();

        return true;

        } else {
            return false;
        }

    }

    //Funktion för att ta bort varukorgar som inte har checkats ut.
    //Detta baseras på om tokenen har gått ut och kallas på i våra cart-endpoints.
    public function deleteCart($token_id_check){
        $return_object = new stdClass;

        $check_token = $this->getTokenId($token_id_check);
        $check_cart = $this->getCartId($check_token);

        if(!empty($check_cart)){

            if($check_cart['status'] !==  1){
                $query_string = "DELETE FROM carts2 WHERE id=:carts_id";
                $statementHandler = $this->database_handler->prepare($query_string);

                $statementHandler->bindParam(':carts_id', $check_cart['id']);
                $statementHandler->execute();
            } else {
                $return_object->state = "ERROR";
                $return_object->message = "Cannot delete already checked out cart!";
            }

            $return_object->state = "ERROR";
            $return_object->message = "Cannot find cart!";
        }

        return json_encode($return_object);
    }

        
    //Funktion för att hämta vårt tokenid kopplat till våra tokens
    private function getTokenId($token_id_check){
        $query_string = "SELECT id FROM tokens WHERE token=:token";
        $statementHandler = $this->database_handler->prepare($query_string);
    
            if($statementHandler !== false ){
    
                $statementHandler->bindParam(":token", $token_id_check);
                $statementHandler->execute();
    
                $token_data = $statementHandler->fetch();
    
                    if(!empty($token_data)){
                        return $token_data;
                    } else {
                        return false;
                    }
            }
    }
    
    //Funktion för att hämta info om våra varukorgar
    private function getCartId($token_id_check){

        $query_string = "SELECT id, status FROM carts2 WHERE tokens_id=:token_id";
        $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false){

                $statementHandler->bindParam(":token_id", $token_id_check['id']);
                $statementHandler->execute();

                $carts_data = $statementHandler->fetch();

                    if(!empty($carts_data)){

                        return $carts_data;

                    } else {
                       
                        return false;
                    }
            }

    }

    //Funktion för att hämta info angående produkter
    private function getProductData($product_id){
        $query_string = "SELECT id, price FROM products WHERE products.id=:product_id";
        $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false){

                $statementHandler->bindParam(":product_id", $product_id);
                $statementHandler->execute();

                $product_data = $statementHandler->fetch();

                    if(!empty($product_data)){

                        return $product_data;

                    } else {
                       
                        return false;
                    }

            }
       
    }

    
}
?>