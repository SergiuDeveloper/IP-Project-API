<?php

if( !defined('ROOT') ){
    define('ROOT', dirname(__FILE__) . "/../..");
}

require_once ( ROOT . '/Utility/StatusCodes.php' );
require_once ( ROOT . '/Utility/CommonEndPointLogic.php' );
require_once ( ROOT . '/Utility/ResponseHandler.php' );
require_once ( ROOT . '/Utility/DatabaseManager.php' );
require_once ( dirname(__FILE__) . '/Exception/DocumentNotEnoughFetchArguments.php' );

class DocumentItem
{
    /**
     * @var integer holds the item ID
     * TODO : Need another field for actual ID (institution item id)
     */
    private $ID;

    /**
     * @var string item title
     */
    private $title;
    /**
     * @var string item description
     */
    private $description;
    /**
     * @var double item value
     * TODO : currency type. Default and converted or multiple currencies indexed
     * TODO : ADD into db tax
     */
    private $valueBeforeTax;

    /**
     * @var double tax percentage
     */
    private $taxPercentage;

    /**
     * @var double item value after percentage. Range : 0...1 (20% = 0.2)
     */
    private $valueAfterTax;

    public function __construct(){
        $this->ID               = null;
        $this->title            = null;
        $this->description      = null;
        $this->valueAfterTax    = null;
        $this->valueBeforeTax   = null;
        $this->taxPercentage    = null;
    }

    /**
     * TODO : once value before tax/tax/value after tax
     */
    public function addIntoDatabase(){

    }

    /**
     * TODO : once value before tax/tax/value after tax
     */
    public function updateIntoDatabase(){

    }

    /**
     * @return $this
     * @throws DocumentNotEnoughFetchArguments
     */
    public function fetchFromDatabase(){
        if($this->ID != null){
            $item = self::fetchFromDatabaseByID($this->ID);

            $this
                ->setTitle($item->title)
                ->setDescription($item->description)
                ->setValueBeforeTax($this->valueBeforeTax)
                ->setTaxPercentage($this->taxPercentage);

            return $this;
        }

        throw new DocumentNotEnoughFetchArguments();
    }

    public static function fetchFromDatabaseByID($ID){
        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$getFromDatabaseByID);
            $statement->bindParam(":ID", $ID);

            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            $result = new DocumentItem();

            $result
                ->setID( $row['ID'] )
                ->setTitle( $row['Title'] )
                ->setDescription( $row['Description'] ); // TODO : add vbt / tax / vwt

            DatabaseManager::Disconnect();

            return $result;
        }
        catch(Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            die();
        }
    }

    public static function fetchAllFromDatabaseByTitle($title){
        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$getFromDatabaseByTitle);
            $statement->bindParam(":title", $title);

            $statement->execute();

            $result = array();

            while($row = $statement->fetch(PDO::FETCH_ASSOC)){
                $item = new DocumentItem();

                array_push($result, $item
                        ->setID( $row['ID'] )
                        ->setTitle( $row['Title'] )
                        ->setDescription( $row['Description'] )
                ); // TODO : add vbt / tax / vwt
            }

            DatabaseManager::Disconnect();

            return $result;
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            die();
        }
    }

    public function setID($ID){
        $this->ID = $ID;
        return $this;
    }

    public function setTitle($title){
        $this->title = $title;
        return $this;
    }

    public function setDescription($description){
        $this->description = $description;
        return $this;
    }

    public function setValueBeforeTax($valueBeforeTax){
        $this->valueBeforeTax = $valueBeforeTax;

        if($this->taxPercentage != null)
            $this->valueAfterTax = $this->valueBeforeTax * $this->taxPercentage;
        return $this;
    }

    public function setTaxPercentage($taxPercentage){
        $this->taxPercentage = $taxPercentage;

        if($this->valueBeforeTax != null)
            $this->valueAfterTax = $this->valueBeforeTax * $this->taxPercentage;

        return $this;
    }

    public function getID(){
        return $this->ID;
    }

    public function getTitle(){
        return $this->title;
    }

    public function getDescription(){
        return $this->description;
    }

    public function getValueBeforeTax(){
        return $this->valueBeforeTax;
    }

    public function getTaxPercentage(){
        return $this->taxPercentage;
    }

    public function getValueAfterTax(){
        return $this->valueAfterTax;
    }

    private static $getFromDatabaseByID = "
        SELECT * FROM items WHERE ID = :ID
    ";

    private static $getFromDatabaseByTitle = "
        SELECT * FROM items WHERE Title = :title
    ";
}