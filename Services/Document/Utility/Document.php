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

require_once ( ROOT . '/Institution/Utility/InstitutionValidator.php' );
require_once ( ROOT . '/Institution/Utility/InstitutionAutomation.php' );

require_once ( ROOT . '/Document/Utility/Currency.php' );
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

    /**
     * @var string
     */
    protected $dateCreated;

    /**
     * @var string
     */
    protected $dateSent;

    /**
     * @var boolean
     */
    protected $isSent;

    /**
     * @return string
     */
    public function getDateCreated(){
        return $this->dateCreated;
    }

    /**
     * @return string
     */
    public function getDateSent(){
        return $this->dateSent;
    }

    /**
     * @return bool
     */
    public function isSent(){
        return $this->isSent;
    }

    /**
     * @param bool $isSent
     * @return Document
     */
    public function setIsSent($isSent){
        $this->isSent = $isSent;
        return $this;
    }

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
        $this->isSent                   = false;
        $this->dateCreated              = null;
        $this->dateSent                 = null;

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
     * @throws DocumentSendNoReceiverInstitution
     * @throws DocumentSendInvalidReceiverInstitution
     * @throws DocumentSendInvalidReceiverUser
     * @throws DocumentSendUpdateStatementFailed
     * @throws DocumentSendAlreadySent
     */
    public function send(){

        if(defined('DEBUG_ENABLED'))
            DebugHandler::getInstance()
                ->setSource('Document.php')
                ->setLineNumber(__LINE__)
                ->setDebugMessage('DOC VAR CHECK')
                ->addDebugVars($this->getDAO())
                ->debugEcho();

        if($this->isSent != null)
            if($this->isSent == true)
                throw new DocumentSendAlreadySent();

        if($this->receiverInstitutionID == null)
            throw new DocumentSendNoReceiverInstitution();

        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement("SELECT ID FROM institutions WHERE ID = :ID");
            $statement->bindParam(":ID", $this->receiverInstitutionID);
            $statement->execute();

            if($statement->rowCount() == 0)
                throw new DocumentSendInvalidReceiverInstitution();

            $institutionID = $this->receiverInstitutionID;

            DatabaseManager::Disconnect();
        } catch (PDOException $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }

        if($this->receiverID != null) {
            try {
                DatabaseManager::Connect();

                $statement = DatabaseManager::PrepareStatement("SELECT * FROM users WHERE ID = :ID");
                $statement->bindParam(":ID", $this->receiverID);
                $statement->execute();

                if($statement->rowCount() == 0)
                    throw new DocumentSendInvalidReceiverUser();

                DatabaseManager::Disconnect();
            } catch (PDOException $exception) {
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                    ->send();
            }
        }

        if($this->receiverAddressID != null){
            try{
                DatabaseManager::Connect();

                $statement = DatabaseManager::PrepareStatement("SELECT * FROM institution_addresses_list WHERE Institution_ID = :institutionID AND Address_ID = :addressID");
                $statement->bindParam(":institutionID", $institutionID);
                $statement->bindParam(":addressID", $this->receiverAddressID);

                $statement->execute();

                if($statement->rowCount() == 0)
                    $this->receiverAddressID = null;

                DatabaseManager::Disconnect();
            } catch (PDOException $exception){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                    ->send();
            }
        }

        if($this->receiverAddressID == null){
            try{
                DatabaseManager::Connect();

                $statement = DatabaseManager::PrepareStatement("SELECT Address_ID FROM institution_addresses_list WHERE Institution_ID = :institutionID AND Is_Main_Address = true");
                $statement->bindParam(":institutionID", $institutionID);
                $statement->execute();

                $row = $statement->fetch(PDO::FETCH_ASSOC);

                $this->receiverAddressID = $row['Address_ID'];

                DatabaseManager::Disconnect();
            } catch (PDOException $exception){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                    ->send();
            }
        }

        $isTrusted = InstitutionAutomation::isInstitutionTrusted($this->receiverInstitutionID, $this->senderInstitutionID);

        /**
         * Daca receiver trusts sender => isapproved = 1
         */
        $statementString = "UPDATE documents SET Is_Sent = :isSent, Date_Sent = CURRENT_TIMESTAMP, Receiver_Institution_ID = :institutionID, Receiver_Address_ID = :addressID, Sender_User_ID = :senderUserID, Is_Approved = :isApproved";

        if($this->receiverID != null){
            $statementString = $statementString . ', Receiver_User_ID=:receiverUserID';
        }

        $statementString = $statementString . ' WHERE ID = :ID';

        try{
            DatabaseManager::Connect();

            $sendStatus = true;

            $statement = DatabaseManager::PrepareStatement($statementString);
            $statement->bindParam(":isSent", $sendStatus, PDO::PARAM_BOOL);
            $statement->bindParam(":ID", $this->ID);
            $statement->bindParam(":institutionID", $institutionID);
            $statement->bindParam(":addressID", $this->receiverAddressID);
            $statement->bindParam(":senderUserID", $this->senderID);
            $statement->bindParam(":isApproved", $isTrusted, PDO::PARAM_BOOL);

            if($this->receiverID != null)
                $statement->bindParam(":receiverUserID", $this->receiverID);

            $statement->execute();

            if($statement->rowCount() == 0)
                throw new DocumentSendUpdateStatementFailed();

            DatabaseManager::Disconnect();
        }
        catch (PDOException $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }

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
                $this->dateCreated              = $row['Date_Created'];
                $this->dateSent                 = $row['Date_Sent'];
                $this->isSent                   = $row['Is_Sent'];
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

    public abstract function updateIntoDatabase($documentJSON);

    public abstract function fetchFromDatabaseByDocumentID($connected = false);

    public abstract function deleteFromDatabase($type);

    /**
     * @param int $documentID
     * @return Document
     * @throws DocumentNotFound
     * @throws DocumentTypeNotFound
     */
    public static function fetchDocument($documentID){
        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement("SELECT document_types.Title FROM documents JOIN document_types ON documents.Document_Types_ID = document_types.ID WHERE documents.ID = :ID");
            $statement->bindParam(":ID", $documentID);
            $statement->execute();

            if($statement->rowCount() == 0)
                throw new DocumentNotFound();

            $row = $statement->fetch(PDO::FETCH_OBJ);

            $document = null;

            if($row->Title == 'Invoice')
                $document = new Invoice();
            if($row->Title == 'Receipt')
                $document = new Receipt();

            if($document == null)
                throw new DocumentTypeNotFound();

            $document->setID($documentID)->fetchFromDatabaseByDocumentID(true);

            DatabaseManager::Disconnect();

            return $document;
        } catch (PDOException $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();

            die();
        }
    }

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