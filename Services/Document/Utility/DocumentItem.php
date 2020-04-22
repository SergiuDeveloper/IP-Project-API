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
require_once ( dirname(__FILE__) . '/Exception/DocumentNotEnoughFetchArguments.php' );
require_once ( dirname(__FILE__) . '/Exception/DocumentItemDuplicate.php' );
require_once ( dirname(__FILE__) . '/Exception/DocumentItemMultipleResults.php' );
require_once ( dirname(__FILE__) . '/Exception/DocumentItemInvalid.php' );

class DocumentItem
{
    /**
     * @var integer holds the item ID
     * TODO : Need another field for actual ID (institution item id)
     */
    private $ID;

    /**
     * @var integer holds the item ID for the specific institution.
     *
     * Ex :
     *  Inst 1 sells item "chair", with id 5 in THEIR database (inst 1 items)
     *  Inst 2 sells item "table", with id 5 in THEIR database (inst 2 items)
     * On documents, their ids have got to appear, not our internal ids.
     */
    private $productNumber;

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

    /**
     * @var integer holds the currency id
     */
    private $currencyID;

    public function __construct(){
        $this->ID               = null;
        $this->productNumber    = null;
        $this->title            = null;
        $this->description      = null;
        $this->valueAfterTax    = null;
        $this->valueBeforeTax   = null;
        $this->taxPercentage    = null;
        $this->currencyID = self::getDatabaseCurrencyID(DEFAULT_ITEM_VALUE_CURRENCY);

        if( $this->currencyID == null ) {
            self::addCurrency(DEFAULT_ITEM_VALUE_CURRENCY);
            $this->currencyID = self::getDatabaseCurrencyID(DEFAULT_ITEM_VALUE_CURRENCY);
        }
    }

    /**
     * @throws DocumentItemInvalid
     */
    public function addIntoDatabase(){
        if(
            $this->currencyID       == null ||
            $this->title            == null ||
            $this->description      == null ||
            $this->valueBeforeTax   == null ||
            $this->valueAfterTax    == null ||
            $this->taxPercentage    == null ||
            $this->productNumber    == null
        ){
            throw new DocumentItemInvalid();
        }

        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$insertIntoDatabase);
            $statement->bindParam(":title",             $this->title);
            $statement->bindParam(":description",       $this->description);
            $statement->bindParam(":currencyID",        $this->currencyID);
            $statement->bindParam(":valueBeforeTax",    $this->valueBeforeTax);
            $statement->bindParam(":valueAfterTax",     $this->valueAfterTax);
            $statement->bindParam(":taxPercentage",     $this->taxPercentage);
            $statement->bindParam(":productNumber",     $this->productNumber);

            $statement->execute();

            $this->setID(self::fetchFromDatabaseByTitleAndProductNumber($this->title, $this->productNumber)->ID);

            DatabaseManager::Disconnect();
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }
    }

    /**
     * @throws DocumentItemInvalid
     */
    public function updateIntoDatabase(){
        if(
            $this->ID               == null ||
            $this->currencyID       == null ||
            $this->title            == null ||
            $this->description      == null ||
            $this->valueBeforeTax   == null ||
            $this->valueAfterTax    == null ||
            $this->taxPercentage    == null ||
            $this->productNumber    == null
        ){
            throw new DocumentItemInvalid();
        }

        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$updateIntoDatabase);
            $statement->bindParam(":ID",                $this->ID);
            $statement->bindParam(":title",             $this->title);
            $statement->bindParam(":description",       $this->description);
            $statement->bindParam(":currencyID",        $this->currencyID);
            $statement->bindParam(":valueBeforeTax",    $this->valueBeforeTax);
            $statement->bindParam(":valueAfterTax",     $this->valueAfterTax);
            $statement->bindParam(":taxPercentage",     $this->taxPercentage);
            $statement->bindParam(":productNumber",     $this->productNumber);

            $statement->execute();

            DatabaseManager::Disconnect();
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }
    }

    /**
     * @return $this
     * @throws DocumentNotEnoughFetchArguments
     * @throws DocumentItemMultipleResults
     * @throws DocumentItemInvalid
     */
    public function fetchFromDatabase(){
        if($this->ID != null){
            $item = self::fetchFromDatabaseByID($this->ID);

            $this
                ->setProductNumber($item->productNumber)
                ->setTitle($item->title)
                ->setDescription($item->description)
                ->setValueBeforeTax($item->valueBeforeTax)
                ->setTaxPercentage($item->taxPercentage)
                ->setCurrencyID($item->currencyID);

            return $this;
        }

        if($this->productNumber != null && $this->title != null){
            $item = self::fetchFromDatabaseByTitleAndProductNumber($this->title, $this->productNumber);

            $this
                ->setID($item->ID)
                ->setDescription($item->description)
                ->setValueBeforeTax($item->valueBeforeTax)
                ->setTaxPercentage($item->taxPercentage)
                ->setCurrencyID($item->currencyID);

            return $this;
        }

        if($this->productNumber != null){
            $items = self::fetchAllFromDatabaseByProductNumber($this->productNumber);

            if(count($items) == 0)
                throw new DocumentItemInvalid();

            if(count($items) == 1){
                $this
                    ->setID($items[0]->ID)
                    ->setTitle($items[0]->title)
                    ->setDescription($items[0]->description)
                    ->setValueBeforeTax($items[0]->valueBeforeTax)
                    ->setTaxPercentage($items[0]->taxPercentage)
                    ->setCurrencyID($items[0]->currencyID);
            }

            throw new DocumentItemMultipleResults();
        }

        if($this->title != null){
            $items = self::fetchAllFromDatabaseByTitle($this->title);

            if(count($items) == 0)
                throw new DocumentItemInvalid();

            if(count($items) == 1){
                $this
                    ->setProductNumber($items[0]->productNumber)
                    ->setTitle($items[0]->title)
                    ->setDescription($items[0]->description)
                    ->setValueBeforeTax($items[0]->valueBeforeTax)
                    ->setTaxPercentage($items[0]->taxPercentage)
                    ->setCurrencyID($items[0]->currencyID);
            }

            throw new DocumentItemMultipleResults();
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
                ->setProductNumber( $row['Product_Number'] )
                ->setDescription( $row['Description'] )
                ->setValueBeforeTax( $row['Value_Before_Tax'] )
                ->setTaxPercentage( $row['Tax_Percentage'] )
                ->setCurrencyID( $row['Currencies_ID'] );

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

    public static function fetchAllFromDatabaseByProductNumber($productNumber){
        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$getFromDatabaseByProductNumber);
            $statement->bindParam(":productNumber", $productNumber);

            $statement->execute();

            $result = array();

            while($row = $statement->fetch(PDO::FETCH_ASSOC)){
                $item = new DocumentItem();

                array_push($result, $item
                    ->setID( $row['ID'] )
                    ->setTitle( $row['Title'] )
                    ->setProductNumber( $row['Product_Number'] )
                    ->setDescription( $row['Description'] )
                    ->setValueBeforeTax( $row['Value_Before_Tax'] )
                    ->setTaxPercentage( $row['Tax_Percentage'] )
                    ->setCurrencyID( $row['Currencies_ID'] )
                );
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
                    ->setProductNumber( $row['Product_Number'] )
                    ->setDescription( $row['Description'] )
                    ->setValueBeforeTax( $row['Value_Before_Tax'] )
                    ->setTaxPercentage( $row['Tax_Percentage'] )
                    ->setCurrencyID( $row['Currencies_ID'] )
                );
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

    public static function fetchFromDatabaseByTitleAndProductNumber($title, $productNumber){
        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$getFromDatabaseByTitleAndProductNumber);
            $statement->bindParam(":title", $title);
            $statement->bindParam(":productNumber", $productNumber);

            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            $item = new DocumentItem();

            $item
                ->setID( $row['ID'] )
                ->setTitle( $row['Title'] )
                ->setProductNumber( $row['Product_Number'] )
                ->setDescription( $row['Description'] )
                ->setValueBeforeTax( $row['Value_Before_Tax'] )
                ->setTaxPercentage( $row['Tax_Percentage'] )
                ->setCurrencyID( $row['Currencies_ID'] );

            DatabaseManager::Disconnect();

            return $item;
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            die();
        }
    }

    /**
     * @param $ID
     * @return $this
     */
    public function setID($ID){
        $this->ID = $ID;
        return $this;
    }

    /**
     * @param int $productNumber
     * @return DocumentItem
     */
    public function setProductNumber($productNumber){
        $this->productNumber = $productNumber;
        return $this;
    }

    /**
     * @param $title
     * @return $this
     */
    public function setTitle($title){
        $this->title = $title;
        return $this;
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description){
        $this->description = $description;
        return $this;
    }

    /**
     * @param $valueBeforeTax
     * @return $this
     */
    public function setValueBeforeTax($valueBeforeTax){
        $this->valueBeforeTax = $valueBeforeTax;

        if($this->taxPercentage != null)
            $this->valueAfterTax = $this->valueBeforeTax * $this->taxPercentage;
        return $this;
    }

    /**
     * @param $taxPercentage
     * @return $this
     */
    public function setTaxPercentage($taxPercentage){
        $this->taxPercentage = $taxPercentage;

        if($this->valueBeforeTax != null)
            $this->valueAfterTax = $this->valueBeforeTax * $this->taxPercentage;

        return $this;
    }

    /**
     * @param int $currencyID
     * @return DocumentItem
     */
    public function setCurrencyID($currencyID){
        $this->currencyID = $currencyID;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getID(){
        return $this->ID;
    }

    /**
     * @return int
     */
    public function getProductNumber(){
        return $this->productNumber;
    }

    /**
     * @return string|null
     */
    public function getTitle(){
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getDescription(){
        return $this->description;
    }

    /**
     * @return float|null
     */
    public function getValueBeforeTax(){
        return $this->valueBeforeTax;
    }

    /**
     * @return float|null
     */
    public function getTaxPercentage(){
        return $this->taxPercentage;
    }

    /**
     * @return float|null
     */
    public function getValueAfterTax(){
        return $this->valueAfterTax;
    }

    /**
     * @return int
     */
    public function getCurrencyID(){
        return $this->currencyID;
    }

    /**
     * @param $title
     * @return integer
     */
    public static function getDatabaseCurrencyID($title){
        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$getCurrencyID);
            $statement->bindParam(":title", $title);

            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();
        }
        catch(Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();

            die();
        }

        if($row == null)
            return null;

        return $row->ID;
    }

    /**
     * @param $title
     */
    public static function addCurrency($title){
        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$insertCurrency);
            $statement->bindParam(":title", $title);

            $statement->execute();

            DatabaseManager::Disconnect();
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }
    }

    private static $insertIntoDatabase = "
        INSERT INTO items (
            Product_Number, 
            Title, 
            Description, 
            Value_Before_Tax, 
            Tax_Percentage, 
            Value_After_Tax, 
            Currencies_ID
        ) VALUES (
            :productNumber,
            :title,
            :description,
            :valueBeforeTax,
            :taxPercentage,
            :valueAfterTax,
            :currenciesID
        );
    ";

    private static $updateIntoDatabase = "
        UPDATE items SET 
            Product_Number = :productNumber,
            Title = :title,
            Description = :description,
            Value_Before_Tax = :valueBeforeTax,
            Value_After_Tax = :valueAfterTax,
            Tax_Percentage = :taxPercentage,
            Currencies_ID = :currencyID
        WHERE
            ID = :ID
    ";

    private static $getFromDatabaseByID = "
        SELECT * FROM items WHERE ID = :ID
    ";

    private static $getFromDatabaseByProductNumber = "
        SELECT * FROM items WHERE Product_Number = :productNumber
    ";

    private static $getFromDatabaseByTitle = "
        SELECT * FROM items WHERE Title = :title
    ";

    private static $getFromDatabaseByTitleAndProductNumber = "
        SELECT * FROM items WHERE Title = :title AND Product_Number = :productNumber
    ";

    private static $getCurrencyID = "
        SELECT ID FROM currencies WHERE Title = :title
    ";

    private static $insertCurrency = "
        INSERT INTO currencies (Title) VALUES (:title)
    ";
}