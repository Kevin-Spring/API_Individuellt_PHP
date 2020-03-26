<?php

include("../../config/database_handler.php");


class User{

    private $database_handler;
    private $username;
    private $token_validity_time = 15; //minutes

    public function __construct($database_handler_IN)
       {
           $this->database_handler = $database_handler_IN;
       }

       public function addUser($username_IN, $password_IN, $email_IN) {
        //Startar en standardklass för json objektet
        $return_object = new stdClass();

        if($this->isUsernameTaken($username_IN) === false) {

            if($this->isEmailTaken($email_IN) === false) {

                //Skicka in i databasen
                $return = $this->insertUserToDatabase($username_IN, $password_IN, $email_IN);


                if($return !== false) {
                    
                    //Om insertUserToDatabase fungerade returnerar vi ett state som vi sätter till SUCCESS
                    //Och även all information som knuffades in i databasen.
                    $return_object->state = "SUCCESS";
                    $return_object->user = $return;

                }  else {
                    $return_object->state = "ERROR";
                    $return_object->message = "Something went wrong when trying to INSERT user";
                }

            } else {
                $return_object->state = "ERROR";
                $return_object->message = "Email is taken";
            }

        } else {
            $return_object->state = "ERROR";
            $return_object->message = "Username is taken";
        }
            
        //returnera ett json_encode:at svar med informationen.
        return json_encode($return_object);
       }
       
       //Funktion för att registrera användare i databasen.
       private function insertUserToDatabase($username_param, $password_param, $email_param) {

            $query_string = "INSERT INTO users (username, password, email) VALUES(:username, :password, :email)";
            $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false ){

                $encrypted_password = md5($password_param);

                $statementHandler->bindParam(':username', $username_param);
                $statementHandler->bindParam(':password', $encrypted_password);
                $statementHandler->bindParam(':email', $email_param);

                $statementHandler->execute();

                //För att kunna hämta json-objektet behöver vi hämta informationen vi precis la in i databasen.
                $last_inserted_user_id = $this->database_handler->lastInsertId();

                $query_string = "SELECT id, username, email FROM users WHERE id=:last_user_id";
                $statementHandler = $this->database_handler->prepare($query_string);

                $statementHandler->bindParam(':last_user_id', $last_inserted_user_id);

                $statementHandler->execute();

                return $statementHandler->fetch();
                

            } else {
                return false;
            }


       }

       //Funktion för att kika om användarnamn redan är upptaget
       private function isUsernameTaken( $username_param ) {

            $query_string = "SELECT COUNT(id) FROM users WHERE username=:username";
            $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false ){

                $statementHandler->bindParam(":username", $username_param);
                $statementHandler->execute();

                $numberOfUsernames = $statementHandler->fetch()[0];

                if($numberOfUsernames > 0) {
                    return true; 
                } else {
                    return false;
                }


            } else {
                echo "Statementhandler epic fail!";
                die;
            }
        }


        
        //Funktion för att bolla med databasen om mailadressen är taget.
        private function isEmailTaken( $email_param ) {
            $query_string = "SELECT COUNT(id) FROM users WHERE email=:email";
            $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false ){

                $statementHandler->bindParam(":email", $email_param);
                $statementHandler->execute();

                $numberOfUsers = $statementHandler->fetch()[0];

                if($numberOfUsers > 0) {
                    return true; 
                } else {
                    return false;
                }


            } else {
                echo "Statementhandler epic fail!";
                die;
            }
        }


        //Funktion för att logga in användare
        public function loginUser($username_parameter, $password_parameter) {
            $return_object = new stdClass();

            $query_string = "SELECT id, username, email FROM users WHERE username=:username_IN AND password=:password_IN";
            $statementHandler = $this->database_handler->prepare($query_string);
            
            if($statementHandler !== false) {

                $password = md5($password_parameter);

                $statementHandler->bindParam(':username_IN', $username_parameter);
                $statementHandler->bindParam(':password_IN', $password);

                $statementHandler->execute();
                $return = $statementHandler->fetch();

                if(!empty($return)) {

                    $this->username = $return['username'];

                    $return_object->state = "SUCCESS";
                    $return_object->token = $this->getToken($return['id'], $return['username']);

                } else {
                    $return_object->state = "ERROR";
                    $return_object->message = "Something went wrong when trying to LOG IN";
                }

            } else {
                $return_object->state = "ERROR";
                $return_object->message = "Something went wrong with STATEMENTHANDLER";
                die;
            }

            return json_encode($return_object);

        }

        
 /* -------------------------- TOKENS, aka spänn fast dig ---------------------- */
 /* ----- Användandet av json-meddelanden är lite extremt men enkelt att felsöka ----- */
        
        //Funktion som tillkallar en annan funktion, metanivån är galen.
        private function getToken($userID) {

            $token = $this->checkToken($userID);

            return $token;

        }

        //Funktion för att ta bort en inaktiv token, annars kalla på "skapa ny"-token funktionen
        private function checkToken($userID_IN) {

            $return_object = new stdClass;

            $query_string = "SELECT token, date_updated FROM tokens WHERE user_id=:userID";
            $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false) {

                    $statementHandler->bindParam(":userID", $userID_IN);
                    $statementHandler->execute();
                    $return = $statementHandler->fetch();
                    
                    if(!empty($return['token'])) {

                        $token_timestamp = $return['date_updated'];
                        $diff = time() - $token_timestamp;

                        if(($diff / 60) > $this->token_validity_time) {

                            $query_string = "DELETE FROM tokens WHERE user_id=:userID";
                            $statementHandler = $this->database_handler->prepare($query_string);

                            $statementHandler->bindParam(':userID', $userID_IN);
                            $statementHandler->execute();

                            $return_object->state = "SUCCESS";
                            $return_object->token = $this->createToken($userID_IN);

                        } else {
                            $return_object->state = "SUCCESS";
                            $return_object->token = $return['token'];
                        }

                    } else {

                        $return_object->state = "SUCCESS";
                        $return_object->token = $this->createToken($userID_IN);
                    }

            } else {
                $return_object->state = "ERROR";
                $return_object->message = "Something went wrong with STATEMENTHANDLER in checkToken";
                die;
            }

            return json_encode($return_object);

        }

        /* Fuktion för att skapa token */
        private function createToken($user_id_parameter) {
            $return_object = new stdClass;

            /* Ge token ett sjukt obskyrt & unikt namn */
            $uniqToken = md5($this->username.uniqid('', true).time());

            $query_string = "INSERT INTO tokens (user_id, token, date_updated) VALUES(:userid, :token, :current_time)";
            $statementHandler = $this->database_handler->prepare($query_string);

            if($statementHandler !== false) {

                $currentTime = time();
                $statementHandler->bindParam(":userid", $user_id_parameter);
                $statementHandler->bindParam(":token", $uniqToken);
                $statementHandler->bindParam(":current_time", $currentTime);

                $return = $statementHandler->execute();

                if(!empty($return)){

                    $return_object->state = "SUCCESS";
                    $return_object->token = $uniqToken;

                } else {

                    $return_object->state = "ERROR";
                    $return_object->message = "Could not execute query in createToken";
                }

            } else {
                $return_object->state = "ERROR";
                $return_object->message = "Could not create STATEMENTHANDLER in createToken";
            }

            return json_encode($return_object);

        }

    
    /* Funktion för att validera token, dvs om den har gått ut efter vår satta tid (längst upp) */
    public function validateToken($token) {

        $return_object = new stdClass;

        $query_string = "SELECT user_id, date_updated FROM tokens WHERE token=:token";
        $statementHandler = $this->database_handler->prepare($query_string);

        if($statementHandler !== false ){

            $statementHandler->bindParam(":token", $token);
            $statementHandler->execute();

            $token_data = $statementHandler->fetch();

            if(!empty($token_data['date_updated'])){

                /* Räkna ut hur lång tid det har gått sen du fick din token, även sen 70-talet */
                $diff = time() - $token_data['date_updated'];

                if(($diff / 60) < $this->token_validity_time){

                    // Uppdaterar token om användare är aktiv
                    $return_object->state = "SUCCESS";
                    $return_object->token = $this->updateStatus($token);

                } else {
                    $return_object->state = "ERROR";
                    $return_object->message = "Your session expired!";
                }

            } else {
                $return_object->state = "ERROR";
                $return_object->message = "Could not find token, please log in first";
            }

        } else {
            $return_object->state = "ERROR";
            $return_object->message = "Could not create STATEMENTHANDLER in validateToken!";
            die;
        }  

        return json_encode($return_object);

    }

    // Uppdaterar token om användare är aktiv
     private function updateStatus($token_ID){

        $query_string = "UPDATE tokens SET date_updated = :date_updated_new WHERE token = :token";
        $statementHandler = $this->database_handler->prepare($query_string);
        
        $new_current_time = time();
        $statementHandler->bindParam(":date_updated_new", $new_current_time);
        $statementHandler->bindParam(":token", $token_ID);

        $statementHandler->execute();

        }
    
    
}


?>