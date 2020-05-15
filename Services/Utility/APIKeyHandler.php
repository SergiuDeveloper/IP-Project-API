<?php

if(!defined('ROOT'))
    define('ROOT', dirname(__FILE__) . '/..');

require_once (ROOT . '/Utility/Credentials.php');

require_once (ROOT . '/Utility/Exceptions/APIKeyHandlerCredentialsUnbound.php');
require_once (ROOT . '/Utility/Exceptions/APIKeyHandlerKeyUnbound.php');
require_once (ROOT . '/Utility/Exceptions/APIKeyHandlerAPIKeyInvalid.php');

require_once (ROOT . '/Utility/ResponseHandler.php');
require_once (ROOT . '/Utility/StatusCodes.php');
require_once (ROOT . '/Utility/CommonEndPointLogic.php');
require_once (ROOT . '/Utility/DatabaseManager.php');

class APIKeyHandler{

    /**
     * @var APIKeyHandler
     */
    private static $instance;

    /**
     * @var string
     */
    private $APIKey;

    /**
     * @var Credentials
     */
    private $credentials;

    private function __construct(){

    }

    public static function getInstance(){
        if(self::$instance == null)
            self::$instance = new APIKeyHandler();
        return self::$instance;
    }

    /**
     * @return string
     * @throws APIKeyHandlerCredentialsUnbound
     */
    public function getAPIKey(): string{
        if($this->APIKey == null)
            throw new APIKeyHandlerCredentialsUnbound();
        return $this->APIKey;
    }

    /**
     * @return Credentials
     * @throws APIKeyHandlerKeyUnbound
     */
    public function getCredentials(): Credentials {
        if($this->credentials == null)
            throw new APIKeyHandlerKeyUnbound();
        return $this->credentials;
    }

    /**
     * @param $APIKey
     * @return APIKeyHandler
     * @throws APIKeyHandlerAPIKeyInvalid
     */
    public function setAPIKey($APIKey): APIKeyHandler{

        $credentials = new Credentials();

        try {
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement("
                SELECT Email, Hashed_Password FROM users JOIN api_keys on users.ID = api_keys.User_ID WHERE API_Key = :apiKey
            ");
            $statement->bindParam(":apiKey", $APIKey);
            $statement->execute();

            if($statement->rowCount() == 0){
                DatabaseManager::Disconnect();
                throw new APIKeyHandlerAPIKeyInvalid();
            }

            $row = $statement->fetch(PDO::FETCH_OBJ);
            $credentials->setEmail($row->Email)->setHashedPassword($row->Hashed_Password);

            DatabaseManager::Disconnect();
        } catch (PDOException $exception) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }

        $this->credentials = $credentials;
        $this->APIKey = $APIKey;
        return $this;
    }

    /**
     * @param Credentials $credentials
     * @return APIKeyHandler
     */
    public function setCredentials($credentials): APIKeyHandler{
        $apiKey = null;

        try {
            DatabaseManager::Connect();

            $email = $credentials->getEmail();
            $password = $credentials->getHashedPassword();

            $statement = DatabaseManager::PrepareStatement("
                SELECT ID, Hashed_Password FROM users WHERE Email = :email
            ");

            $statement->bindParam(":email", $email);

            $statement->execute();

            if($statement->rowCount() == 0){
                DatabaseManager::Disconnect();
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("USER_INVALID"))
                    ->send(StatusCodes::INTERNAL_SERVER_ERROR);
            }

            $userRow = $statement->fetch(PDO::FETCH_OBJ);

            if(password_verify($password, $userRow->Hashed_Password) == false){
                DatabaseManager::Disconnect();
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("USER_INVALID"))
                    ->send(StatusCodes::INTERNAL_SERVER_ERROR);
            }

            $userID = $userRow->ID;

            $getKeyStatement = DatabaseManager::PrepareStatement("
                SELECT API_Key FROM api_keys WHERE User_ID = :ID
            ");
            $getKeyStatement->bindParam(":ID", $userID);
            $getKeyStatement->execute();

            if($getKeyStatement->rowCount() == 0){

                $apiKey = hash("sha256", $email . $password, false);

                $insertKeyStatement = DatabaseManager::PrepareStatement("
                    INSERT INTO api_keys (API_Key, User_ID) VALUES (:apiKey, :ID);
                ");
                $insertKeyStatement->bindParam(":apiKey", $apiKey);
                $insertKeyStatement->bindParam(":ID", $userID);
                $insertKeyStatement->execute();

                if($insertKeyStatement->rowCount() == 0){
                    DatabaseManager::Disconnect();
                    ResponseHandler::getInstance()
                        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("API_KEY_GEN_FAILED"))
                        ->send();
                }
            }
            else{
                $keyRow = $getKeyStatement->fetch(PDO::FETCH_OBJ);
                $apiKey = $keyRow->API_Key;
            }

            DatabaseManager::Disconnect();
        } catch (PDOException $exception) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }

        $this->credentials = $credentials;
        $this->APIKey = $apiKey;

        return $this;
    }

}
