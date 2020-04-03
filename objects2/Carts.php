<?php
include("../../config/database_handler.php");

class Cart{

    private $database_handler;
    private $cart_validity_time = 15; //minutes

    public function __construct($databaseHandler){

        $this->database_handler = $databaseHandler;

    }


    //Funktion för att kunna ta bort enskilda produkter ur varukorgen
    public function removeFromCart($product_id, $token_id){

        $return_object = new stdClass();

        //Först för att kunna ta bort nått ur vår varukorg behöver vi id:et från vår token.
        $query_string = "SELECT id FROM tokens WHERE token=:token";
        $statementHandler = $this->database_handler->prepare($query_string);
    
        if($statementHandler !== false ){
    
            $statementHandler->bindParam(":token", $token_id);
            $statementHandler->execute();
    
            $token_data = $statementHandler->fetch();
    
                if($token_data['id'] > 1){

                    $query_string = "SELECT id FROM carts2 WHERE tokens_id=:token_id";
                    $statementHandler = $this->database_handler->prepare($query_string);

                    $statementHandler->bindParam(":token_id", $token_data['id']);
                    $statementHandler->execute();

                    $carts_data = $statementHandler->fetch();

                    if($carts_data['id'] > 1){

                        //Om vårt fetchade id från token tabellen existerar skickar vi med det tillsammans med produktens id med vår removeFromCartDb funktion.
                        $return = $this->removeFromCartDb($product_id);

                            if($return !== false){
                                $return_object->state = "SUCCESS";
                                $return_object->message = "Product " . $product_id . " was removed from your shoppingcart";
                                $return_object->products_in_cart = $this->getCartItems($carts_data['id']);   
                            } else {
                                $return_object->state = "ERROR";
                                $return_object->message = "Could not find that specific product!";
                                $return_object->products_in_cart = $this->getCartItems($carts_data['id']);  
                            }

                        } else {
                            $return_object->state = "ERROR";
                            $return_object->message = "Please create a shoppingcart!";
                        }

                    } else {
                        //Eftersom vår token raderas i tabellen "tokens" efter en kvart,
                        //Så kommer den även raderas i vår tabell "carts2" i och med våra foreign key-relationer.
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

    //Funktion för att skapa en möjlig checkout
    //Nu försvinner tyvärr allt som läggs in i checkout efter 15 min pga tokens expire tid, för vår cart raderas.
    //I v2 behövs en lösning för att kunna spara checkouten.

    /* ------ POTENTIELL LÖSNING -------- */
    
    //Om en token inte raderas ur token tabellen vid inaktivitet, utan bara uppdaterar token-värdet varje gång man loggar in.
    //Så kommer inte cartsen försvinna, med andra ord kommer inte checkouten försvinna.
    //Då får en inloggad user alltid samma token id, men det är nytt värde i den varje gång något slår mot databasen.
    //För att då radera en cart vid inaktivitet och spara carts som har blivit utcheckade kan vi lägga in ytterligare en kolumn i tabellen carts. 
    //Nya tabellen med namnet t.ex. ”status” kommer vara beroende av värdet i den kolumnen och våra sql-frågor kommer då radera de som är inaktiva och- 
    //spara de som har ”status = utcheckad” exempelvis.
    //Då får alla nyskapade carts ha ett DEFAULT-VÄRDE i status-kolumnen som indikerar att de inte checkats ut.
    //Och när vi checkar ut får cartsens ”status-värden” då bli ändrade.
    //Då kommer vi kunna se vilka våra utcheckade carts är i checkouten.

    public function checkout($token_id, $firstname, $lastname, $address, $email){

        $return_object = new stdClass();

        $query_string = "SELECT id FROM tokens WHERE token=:token";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false){

            $statementHandler->bindParam(":token", $token_id);
            $statementHandler->execute();

            $token_data = $statementHandler->fetch();

            if($token_data['id'] > 1){

                $query_string = "SELECT id FROM carts2 WHERE tokens_id=:token_id";
                $statementHandler = $this->database_handler->prepare($query_string);

                $statementHandler->bindParam(":token_id", $token_data['id']);
                $statementHandler->execute();

                $carts_data = $statementHandler->fetch();

                    if($carts_data['id'] > 1){

                        $return = $this->validateCheckout($carts_data['id'], $firstname, $lastname, $address, $email);

                            if($return !== false){
                                $return_object->state = "SUCCESS";
                                $return_object->message = "Your have successfully checked out!";

                                $return_object->products_in_cart = $this->getCheckoutItems($carts_data['id']);


                            } else {
                                $return_object->state = "ERROR";
                                $return_object->message = "Could not check out order!";
                            }

                    } else {
                        $return_object->state = "ERROR";
                        $return_object->message = "Please create a shoppingcart!";
                    }

            } else {
                $return_object->state = "ERROR";
                $return_object->message = "Could not find requested cart and insert into checkout";
            }


        } else {
            $return_object->state = "ERROR";
            $return_object->message = "Something went wrong when trying to CHECKOUT your products from CART";
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
    
            //Först för att kunna lägga nått i vår varukorg behöver vi id:et från vår token.
            //DVS för att hålla våra foreign keys löften.
            $query_string = "SELECT id FROM tokens WHERE token=:token";
            $statementHandler = $this->database_handler->prepare($query_string);
    
            if($statementHandler !== false ){
    
                $statementHandler->bindParam(":token", $token_id);
                $statementHandler->execute();
    
                $token_data = $statementHandler->fetch();
    
                    if($token_data['id'] > 1){

                        $this->validateCart($token_data['id']);
                        

                        $query_string = "SELECT id FROM carts2 WHERE tokens_id=:token_id";
                        $statementHandler = $this->database_handler->prepare($query_string);

                        $statementHandler->bindParam(":token_id", $token_data['id']);
                        $statementHandler->execute();

                        $carts_data = $statementHandler->fetch();

                        if($carts_data['id'] > 1){

                            //Om vårt fetchade id från token tabellen existerar skickar vi med det tillsammans med produktens id i vår insertCartToDatabase funktion.
                            //Även priset på varorna så totalsumman kan räknas ut i checkouten.
                            $query_string = "SELECT price FROM products WHERE products.id=:product_id";
                            $statementHandler = $this->database_handler->prepare($query_string);

                            $statementHandler->bindParam(":product_id", $product_id);
                            $statementHandler->execute();

                            $price_data = $statementHandler->fetch();

                            $return = $this->insertNewCartToDatabase($product_id, $carts_data['id'], $price_data['price']);

                            if($return !== false){
                                $return_object->state = "SUCCESS";
                                $return_object->message = "Product " . $product_id . " was added to your shoppingcart";
                                $return_object->products_in_cart = $this->getCartItems($carts_data['id']);   
                            } else {
                                $return_object->state = "ERROR";
                                $return_object->message = "Could not find that specific product!";
                            }

                        } else {
                            $return_object->state = "ERROR";
                            $return_object->message = "Please create a shoppingcart!";
                        }

                    } else {
                        //Eftersom vår token raderas i tabellen "tokens" efter en kvart,
                        //Så kommer den även raderas i vår tabell "carts" i och med våra foreign key-relationer.
                        //Man hatar inte mysql:s "CASCADE" setting.
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
    private function insertNewCartToDatabase($product_ID_IN, $carts_ID_IN, $price_data){

    $query_string = "INSERT INTO prodcutsInCarts(products_id, carts_id, price_data) VALUES(:productID, :cartID, :price_data)";
    $statementHandler = $this->database_handler->prepare($query_string);

    if($statementHandler !== false){

        $statementHandler->bindParam(":productID", $product_ID_IN);
        $statementHandler->bindParam(":cartID", $carts_ID_IN);
        $statementHandler->bindParam(":price_data", $price_data);

        $statementHandler->execute();

        } else {
            return false;
        }

    }

    public function deleteCart($token_id_check){
        $return_object = new stdClass;

        $query_string = "SELECT id FROM tokens WHERE token=:token";
        $statementHandler = $this->database_handler->prepare($query_string);
    
            if($statementHandler !== false ){
    
                $statementHandler->bindParam(":token", $token_id_check);
                $statementHandler->execute();
    
                $token_data = $statementHandler->fetch();
    
                    if($token_data['id'] > 1){

                        $query_string = "SELECT id FROM carts2 WHERE tokens_id=:token_id";
                        $statementHandler = $this->database_handler->prepare($query_string);

                        $statementHandler->bindParam(":token_id", $token_data['id']);
                        $statementHandler->execute();

                        $carts_data = $statementHandler->fetch();

                        if($carts_data['id'] > 1){

                            if($carts_data['status'] !==  1){
                                $query_string = "DELETE FROM carts2 WHERE id=:carts_id";
                                $statementHandler = $this->database_handler->prepare($query_string);
        
                                $statementHandler->bindParam(':carts_id', $carts_data['id']);
                                $statementHandler->execute();
                            } else {
                                $return_object->state = "ERROR";
                                $return_object->message = "Cannot delete already checked out cart!";
                            }

                            $return_object->state = "ERROR";
                            $return_object->message = "Cannot find cart!";

                        }
                        $return_object->state = "ERROR";
                        $return_object->message = "Cannot find token!";
                    }
                    $return_object->state = "ERROR";
                    $return_object->message = "Satetement handler messed up!";
                }

                return json_encode($return_object);

            }
    

}

    




?>