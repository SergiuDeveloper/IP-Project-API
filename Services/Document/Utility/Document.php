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

require_once ( ROOT . '/Document/Utility/DocumentItem.php' );
require_once ( ROOT . '/Document/Utility/Invoice.php' );
require_once ( ROOT . '/Document/Utility/Receipt.php' );
require_once ( ROOT . '/Document/Utility/DocumentItemContainer.php' );

require_once ( ROOT . '/DataAccessObject/DataObjects.php' );
require_once ( ROOT . '/Document/Utility/Exception/DocumentExceptions.php');

abstract class Document
{
    /**
     * @var integer holds the doc's id. NOT NULL
     */
    protected $ID;

    /**
     * @var integer holds the creator / sender employee id. NOT NULL.
     */
    protected $senderID;
    /**
     * @var integer|null holds the receiver employee id.
     */
    protected $receiverID;

    /**
     * @var integer holds the sender institution id. NOT NULL.
     */
    protected $senderInstitutionID;
    /**
     * @var integer holds the receiver institution id.
     */
    protected $receiverInstitutionID;

    /**
     * @var string holds the address of the sender institution
     */
    protected $senderAddressID;
    /**
     * @var string holds the address of the receiver institution
     */
    protected $receiverAddressID;

    /**
     * @var integer holds the creator ID;
     */
    protected $creatorID;

    public abstract function getDAO();

    protected function __construct(){
        $this->ID                       = null;
        $this->senderID                 = null;
        $this->senderInstitutionID      = null;
        $this->senderAddressID          = null;
        $this->receiverID               = null;
        $this->receiverInstitutionID    = null;
        $this->receiverAddressID        = null;
        $this->creatorID                = null;

        if(defined('CALLER_USER_ID')){
            $this->creatorID = CALLER_USER_ID;
        }
    }

    /**
     * TODO : in service or in here
     */
    protected function updateIntoDatabaseDocumentBase(){

    }

    /**
     * TODO : in service or in here
     * @param $documentTypeID
     * @param bool $connected
     * @throws DocumentInvalid
     */
    protected function insertIntoDatabaseDocumentBase($documentTypeID, $connected = false){
        if(
            $this->senderInstitutionID == null ||
            //$this->receiverInstitutionID == null || TODO :makes no sense
            $documentTypeID == null ||
            $this->senderAddressID == null
        ){
            throw new DocumentInvalid();
        }

        $statementString = self::$insertBaseIntoDatabase;

        if($this->senderID != null)
            $statementString = $statementString . ', Sender_ID';
        if($this->senderAddressID != null)
            $statementString = $statementString . ', Sender_Address_ID';
        if($this->receiverID != null)
            $statementString = $statementString . ', Receiver_ID';
        if($this->receiverAddressID != null)
            $statementString = $statementString . ', Receiver_Address_ID';
        if($this->creatorID != null || ($this->creatorID == null && defined('CALLER_USER_ID')))
            $statementString = $statementString . ', Creator_User_ID';

        $statementString = $statementString . ') VALUES (:senderInstitutionID, :receiverInstitutionID, :documentTypesID, CURRENT_TIMESTAMP, 0';

        if($this->senderID != null)
            $statementString = $statementString . ', :senderID';
        if($this->senderAddressID != null)
            $statementString = $statementString . ', :senderAddressID';
        if($this->receiverID != null)
            $statementString = $statementString . ', :receiverID';
        if($this->receiverAddressID != null)
            $statementString = $statementString . ', :receiverAddressID';
        if($this->creatorID != null || ($this->creatorID == null && defined('CALLER_USER_ID')))
            $statementString = $statementString . ', :creatorUserID';

        $statementString = $statementString . ')';

        try{
            if($connected == false)
                DatabaseManager::Connect();

            $defaultCreatorID = CALLER_USER_ID;

            $statement = DatabaseManager::PrepareStatement($statementString);
            $statement->bindParam(':senderInstitutionID', $this->senderInstitutionID);
            $statement->bindParam(':receiverInstitutionID', $this->receiverInstitutionID);
            $statement->bindParam(':documentTypesID', $documentTypeID);
            if($this->senderID != null)
                $statement->bindParam(":senderID", $this->senderID);
            if($this->senderAddressID != null)
                $statement->bindParam(":senderAddressID", $this->senderAddressID);
            if($this->receiverID != null)
                $statement->bindParam(":receiverID", $this->receiverID);
            if($this->receiverAddressID != null)
                $statement->bindParam(":receiverAddressID", $this->receiverAddressID);
            if($this->creatorID != null)
                $statement->bindParam(":creatorUserID", $this->creatorID);
            else if(defined('CALLER_USER_ID'))
                $statement->bindParam(":creatorUserID", $defaultCreatorID);

            $statement->execute();

            if($statement->rowCount() == 0)
                throw new DocumentItemDuplicate();

            $this->ID = (int)(DatabaseManager::getConnectionInstance()->lastInsertId());

            if($connected == false)
                DatabaseManager::Disconnect();
        }
        catch(DocumentItemDuplicate $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_DUPLICATE"))
                ->send();
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }

    }

    /**
     * TODO : in service or in here
     * @param bool $connected
     */
    protected function fetchFromDatabaseDocumentBaseByID($connected = false) {
        try{
            if(!$connected)
                DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$getFromDatabaseByID);
            $statement->bindParam(":ID", $this->ID);

            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if($row != null) {
                $this->ID                       = $row['ID'];
                $this->senderID                 = $row['Sender_User_ID'];
                $this->senderInstitutionID      = $row['Sender_Institution_ID'];
                $this->senderAddressID          = $row['Sender_Address_ID'];
                $this->receiverID               = $row['Receiver_User_ID'];
                $this->receiverInstitutionID    = $row['Receiver_Institution_ID'];
                $this->receiverAddressID        = $row['Receiver_Address_ID'];
                $this->creatorID                = $row['Creator_User_ID'];
            }

            if(!$connected)
                DatabaseManager::Disconnect();
        }
        catch (Exception $exception) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            die();
        }
    }

    public abstract function addIntoDatabase();

    public abstract function updateIntoDatabase();

    public abstract function fetchFromDatabaseByDocumentID();

    /**
     * @return integer
     */
    public function getID(){
        return $this->ID;
    }

    /**
     * @return string
     */
    public function getReceiverAddressID(){
        return $this->receiverAddressID;
    }

    /**
     * @return integer|null
     */
    public function getReceiverID(){
        return $this->receiverID;
    }

    /**
     * @return integer|null
     */
    public function getReceiverInstitutionID(){
        return $this->receiverInstitutionID;
    }

    /**
     * @return string
     */
    public function getSenderAddressID(){
        return $this->senderAddressID;
    }

    /**
     * @return integer
     */
    public function getSenderID(){
        return $this->senderID;
    }

    /**
     * @return integer
     */
    public function getSenderInstitutionID(){
        return $this->senderInstitutionID;
    }

    /**
     * @return int
     */
    public function getCreatorID(){
        return $this->creatorID;
    }

    /**
     * @param integer $ID
     * @return Document
     */
    public function setID($ID){
        $this->ID = $ID;
        return $this;
    }

    /**
     * @param string $receiverAddressID
     * @return Document
     */
    public function setReceiverAddressID($receiverAddressID){
        $this->receiverAddressID = $receiverAddressID;
        return $this;
    }

    /**
     * @param integer $receiverID
     * @return Document
     */
    public function setReceiverID($receiverID){
        $this->receiverID = $receiverID;
        return $this;
    }

    /**
     * @param integer $receiverInstitutionID
     * @return Document
     */
    public function setReceiverInstitutionID($receiverInstitutionID){
        $this->receiverInstitutionID = $receiverInstitutionID;
        return $this;
    }

    /**
     * @param string $senderAddressID
     * @return Document
     */
    public function setSenderAddressID($senderAddressID){
        $this->senderAddressID = $senderAddressID;
        return $this;
    }

    /**
     * @param integer $senderID
     * @return Document
     */
    public function setSenderID($senderID){
        $this->senderID = $senderID;
        return $this;
    }

    /**
     * @param integer $senderInstitutionID
     * @return Document
     */
    public function setSenderInstitutionID($senderInstitutionID){
        $this->senderInstitutionID = $senderInstitutionID;
        return $this;
    }

    /**
     * @param int $creatorID
     * @return Document
     */
    public function setCreatorID($creatorID){
        $this->creatorID = $creatorID;
        return $this;
    }

    private static $getFromDatabaseByID = "
        SELECT * FROM documents WHERE ID = :ID
    ";

    private static $insertBaseIntoDatabase = "
        INSERT INTO documents ( Sender_Institution_ID, Receiver_Institution_ID, Document_Types_ID, Date_Created, Is_Sent
    ";
}
?>