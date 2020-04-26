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
     * @var Currency holds the currency
     */
    private $currency;

    public function __toString()
    {
        return $this->json_encode();
    }

    public function json_encode(){
        return
            '{"ID"='            . $this->ID .
            ',"productNumber"=' . $this->productNumber .
            ',"title"='         . $this->title .
            ',"description"='   . $this->description .
            ',"valueBeforeTax"='. $this->valueBeforeTax .
            ',"taxPercentage"=' . $this->taxPercentage .
            ',"valueAfterTax"=' . $this->valueAfterTax .
            ',"currency"='      . $this->currency->json_encode() .
            '}';
    }

    public function __construct(){
        $this->ID               = null;
        $this->productNumber    = null;
        $this->title            = null;
        $this->description      = null;
        $this->valueAfterTax    = null;
        $this->valueBeforeTax   = null;
        $this->taxPercentage    = null;
        $this->currency         = null;
    }

    /**
     * @throws DocumentItemInvalid
     */
    public function addIntoDatabase(){
        if($this->currency == null)
            $this->currency = Currency::getCurrencyByTitle(DEFAULT_ITEM_VALUE_CURRENCY);
        if(
            $this->currency         == null ||
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

            $currencyID = $this->currency->getID();

            $statement = DatabaseManager::PrepareStatement(self::$insertIntoDatabase);
            $statement->bindParam(":title",             $this->title);
            $statement->bindParam(":description",       $this->description);
            $statement->bindParam(":currenciesID",      $currencyID);
            $statement->bindParam(":valueBeforeTax",    $this->valueBeforeTax);
            $statement->bindParam(":valueAfterTax",     $this->valueAfterTax);
            $statement->bindParam(":taxPercentage",     $this->taxPercentage);
            $statement->bindParam(":productNumber",     $this->productNumber);

            $statement->execute();

            //$this->setID(self::fetchFromDatabaseByTitleAndProductNumber($this->title, $this->productNumber)->ID);

            if($statement->rowCount() > 0){
                $this->ID = (int)(DatabaseManager::getConnectionInstance()->lastInsertId());
            }

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
            $this->currency         == null ||
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

            $currencyID = $this->currency->getID();

            $statement = DatabaseManager::PrepareStatement(self::$updateIntoDatabase);
            $statement->bindParam(":ID",                $this->ID);
            $statement->bindParam(":title",             $this->title);
            $statement->bindParam(":description",       $this->description);
            $statement->bindParam(":currencyID",        $currencyID);
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
                ->setCurrency($item->currency);

            return $this;
        }

        if(
            $this->productNumber    != null ||
            $this->title            != null ||
            $this->description      != null ||
            $this->taxPercentage    != null ||
            $this->valueAfterTax    != null ||
            $this->valueBeforeTax   != null ||
            $this->currency         != null
        ){
            $item = self::fetchFromDatabaseByItem($this);

            $this->setID($item->ID);

            return $this;
        }

        if($this->productNumber != null && $this->title != null){
            $item = self::fetchFromDatabaseByTitleAndProductNumber($this->title, $this->productNumber);

            $this
                ->setID($item->ID)
                ->setDescription($item->description)
                ->setValueBeforeTax($item->valueBeforeTax)
                ->setTaxPercentage($item->taxPercentage)
                ->setCurrency($item->currency);

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
                    ->setCurrency($items[0]->currency);
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
                    ->setCurrency($items[0]->currency);
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

            $result = null;

            if($row != null) {
                $result = new DocumentItem();

                $result
                    ->setID($row['ID'])
                    ->setTitle($row['Title'])
                    ->setProductNumber($row['Product_Number'])
                    ->setDescription($row['Description'])
                    ->setValueBeforeTax($row['Value_Before_Tax'])
                    ->setTaxPercentage($row['Tax_Percentage'])
                    ->setCurrency(Currency::getCurrencyByID($row['Currencies_ID'], true));
            }

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
                    ->setCurrency( Currency::getCurrencyByID($row['Currencies_ID'], true) )
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
                    ->setCurrency( Currency::getCurrencyByID($row['Currencies_ID'], true) )
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

    /**
     * @param DocumentItem $item
     * @return DocumentItem
     */
    public static function fetchFromDatabaseByItem($item){
        try{
            DatabaseManager::Connect();

            $currencyID = $item->currency->getID();

            $statement = DatabaseManager::PrepareStatement(self::$getIDFromDatabaseByAllOther);
            $statement->bindParam(":title", $item->title);
            $statement->bindParam(":description", $item->description);
            $statement->bindParam(":productNumber", $item->productNumber);
            $statement->bindParam(":valueBeforeTax", $item->valueBeforeTax);
            $statement->bindParam(":valueAfterTax", $item->valueAfterTax);
            $statement->bindParam(":taxPercentage", $item->taxPercentage);
            $statement->bindParam(":currenciesID", $currencyID);

            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();

            return $item->setID($row->ID);
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

            $item = null;

            if($row != null) {
                $item = new DocumentItem();
                $item
                    ->setID($row['ID'])
                    ->setTitle($row['Title'])
                    ->setProductNumber($row['Product_Number'])
                    ->setDescription($row['Description'])
                    ->setValueBeforeTax($row['Value_Before_Tax'])
                    ->setTaxPercentage($row['Tax_Percentage'])
                    ->setCurrency(Currency::getCurrencyByID($row['Currencies_ID'], true));
            }

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
            $this->valueAfterTax = $this->valueBeforeTax + $this->valueBeforeTax * $this->taxPercentage;
        return $this;
    }

    /**
     * @param $taxPercentage
     * @return $this
     */
    public function setTaxPercentage($taxPercentage){
        $this->taxPercentage = $taxPercentage;

        if($this->valueBeforeTax != null)
            $this->valueAfterTax = $this->valueBeforeTax + $this->valueBeforeTax * $this->taxPercentage;

        return $this;
    }

    /**
     * @param Currency $currency
     * @return DocumentItem
     */
    public function setCurrency($currency){
        $this->currency = $currency;
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
     * @return Currency
     */
    public function getCurrency(){
        return $this->currency;
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

    private static $getIDFromDatabaseByAllOther = "
        SELECT ID FROM items WHERE 
            Title               = :title AND
            Product_Number      = :productNumber AND
            Description         = :description AND
            Value_Before_Tax    = :valueBeforeTax AND
            Value_After_Tax     = :valueAfterTax AND
            Tax_Percentage      = :taxPercentage AND
            Currencies_ID       = :currenciesID 
    ";
}