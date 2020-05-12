<?php

if( !defined('ROOT') ){
    define('ROOT', dirname(__FILE__) . "/../..");
}

if( !defined('DEFAULT_ITEM_VALUE_CURRENCY') ){
    define('DEFAULT_ITEM_VALUE_CURRENCY', 'RON');
}

require_once ( ROOT . '/Utility/StatusCodes.php' );
require_once ( ROOT . '/Utility/CommonEndPointLogic.php' );
require_once ( ROOT . '/Utility/ResponseHandler.php' );
require_once ( ROOT . '/Utility/DatabaseManager.php' );

class PaymentMethod{
    private $ID;
    private $title;

    /**
     * PaymentMethod constructor.
     * @param string $title
     */
    public function __construct($title = null){
        $this->title = $title;

        if($this->title != null)
            $this->ID = self::getPaymentMethodID($title);
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
        $this->ID = self::getPaymentMethodID($title);
    }

    /**
     * @return int
     */
    public function getID()
    {
        return $this->ID;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return int
     */
    public static function getPaymentMethodID($title){
        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$insertPaymentMethodIntoDatabase);
            $statement->bindParam(":title", $title);
            $statement->execute();

            $statement = DatabaseManager::PrepareStatement(self::$getPaymentMethodByTitle);
            $statement->bindParam(":title", $title);
            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();

            return $row->ID;
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            die();
        }
    }

    /**
     * @param integer $ID
     * @return PaymentMethod|null
     */
    public static function getPaymentMethodByID($ID){
        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$getPaymentMethodByID);
            $statement->bindParam(":ID", $ID);
            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();

            $paymentMethod = null;

            if($row != null){
                $paymentMethod = new PaymentMethod();
                $paymentMethod->title = $row->Title;
                $paymentMethod->ID = $row->ID;
            }
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            die();
        }

        return $paymentMethod;
    }

    private static $getPaymentMethodByTitle = "
        SELECT * FROM payment_methods WHERE Title = :title
    ";

    private static $insertPaymentMethodIntoDatabase = "
        INSERT INTO payment_methods (Title) VALUES (:title)
    ";

    private static $getPaymentMethodByID = "
        SELECT * FROM payment_methods WHERE ID = :ID
    ";
}
?>