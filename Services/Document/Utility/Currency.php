<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . '/../..');
}

require_once ( ROOT . '/Utility/DatabaseManager.php' );
require_once ( ROOT . '/Utility/ResponseHandler.php' );
require_once ( ROOT . '/Utility/StatusCodes.php' );
require_once ( ROOT . '/Utility/CommonEndPointLogic.php' );

class Currency{
    private $ID;
    private $title;

    public function __toString()
    {
        return $this->json_encode();
    }

    public function json_encode(){
        return
            '{"ID"='    . $this->ID .
            ',"title"=' . $this->title .
            '}';
    }

    /**
     * @param $currency
     * @return bool
     */
    public function equals($currency){
        return $this->ID == $currency->ID && $this->title == $currency->title;
    }

    public function __construct($title, $fetchData = true, $ID = null){
        $this->title = $title;
        if($fetchData == true)
            $this->ID = self::getCurrencyIDByTitle($title);
        else
            $this->ID = $ID;
    }

    public static function getCurrencyIDByTitle($title){
        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$insertCurrencyIntoDatabase);
            $statement->bindParam(":title", $title);
            $statement->execute();

            $statement = DatabaseManager::PrepareStatement(self::$getCurrencyByTitle);
            $statement->bindParam(":title", $title);
            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_OBJ);

            $rowID = $row->ID;

            DatabaseManager::Disconnect();

            return $rowID;
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            die();
        }
    }

    public static function getCurrencyByID($ID, $alreadyConnected = false){
        try{
            if($alreadyConnected == false)
                DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$insertCurrencyIntoDatabase);
            $statement->bindParam(":title", $title);
            $statement->execute();

            $statement = DatabaseManager::PrepareStatement(self::$getCurrencyByID);
            $statement->bindParam(":ID", $ID);

            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_OBJ);

            $currency = new Currency($row->Title, false, $row->ID);

            if($alreadyConnected == false)
                DatabaseManager::Disconnect();

            return $currency;
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            die();
        }
    }

    public static function getCurrencyByTitle($title, $alreadyConnected = false){
        try{
            if($alreadyConnected == false)
                DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$insertCurrencyIntoDatabase);
            $statement->bindParam(":title", $title);
            $statement->execute();


            $statement = DatabaseManager::PrepareStatement(self::$getCurrencyByTitle);
            $statement->bindParam(":title", $title);

            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_OBJ);

            $currency = new Currency($row->Title, false, $row->ID);

            if($alreadyConnected == false)
                DatabaseManager::Disconnect();

            return $currency;
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            die();
        }
    }

    /**
     * @return string
     */
    public function getTitle(){
        return $this->title;
    }

    /**
     * @return int
     */
    public function getID(){
        return $this->ID;
    }

    public function setTitle($title){
        $this->title = $title;
        $this->ID = self::getCurrencyIDByTitle($title);
    }

    private static $insertCurrencyIntoDatabase = "
        INSERT INTO currencies (Title) VALUES (:title)
    ";

    private static $getCurrencyByTitle = "
        SELECT ID, Title FROM currencies WHERE Title = :title
    ";

    private static $getCurrencyByID = "
        SELECT ID, Title FROM currencies WHERE ID = :ID
    ";
}

?>