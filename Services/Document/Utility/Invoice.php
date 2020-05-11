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
require_once ( ROOT . '/Document/Utility/Document.php' );
require_once ( ROOT . '/Document/Utility/DocumentItemContainer.php' );
require_once ( ROOT . '/Document/Utility/Exception/DocumentExceptions.php');

require_once ( ROOT . '/DataAccessObject/DataObjects.php' );

class Invoice extends Document
{
    /**
     * @var integer Database entry of the invoice ID. Different from the ID of the document (DB Entries separated). Only used in DB
     */
    private $entryID;

    /**
     * @var integer|null document ID of the linked receipt of this invoice. SAME as in documents table, not receipts table.
     */
    private $receiptID;

    /**
     * @var integer receipt ID of the linked receipt of this invoice. SAME as as in receipts table, not documents table. Not to be shown in document.
     */
    private $receiptDocumentID;

    /**
     * @var DocumentItemContainer items mentioned in the invoice
     */
    private $itemsContainer;

    /**
     * @param DocumentItem $item
     * @param int $quantity
     */
    public function addItem($item, $quantity = 1){
        $this->itemsContainer->addItem($item, $quantity);
    }

    /**
     * @throws DocumentItemInvalid
     * @throws DocumentTypeNotFound
     * @throws DocumentInvalid
     */
    public function addIntoDatabase(){

        try {
            DatabaseManager::Connect();

            $getDocumentTypeID = DatabaseManager::PrepareStatement(
                "SELECT ID FROM document_types WHERE LOWER(Title) = LOWER(:title)"
            );

            $documentTypeTitle = 'Invoice';
            $getDocumentTypeID->bindParam(":title", $documentTypeTitle);
            $getDocumentTypeID->execute();

            if (($row = $getDocumentTypeID->fetch(PDO::FETCH_OBJ)) == null)
                throw new DocumentTypeNotFound();

            parent::insertIntoDatabaseDocumentBase($row->ID, true);

            $insertDocumentIntoDatabaseStatement = DatabaseManager::PrepareStatement(
                "INSERT INTO invoices (Documents_ID) values (:documentID)"
            );
            $insertDocumentIntoDatabaseStatement->bindParam(":documentID", $this->ID);
            $insertDocumentIntoDatabaseStatement->execute();

            $this->setEntryID(DatabaseManager::GetLastInsertID());

            foreach ($this->itemsContainer->getDocumentItemRows() as $itemRow) {
                $item = $itemRow->getItemReference();

                $item->fetchFromDatabase(true); /// TODO : implement DocumentItem :: checkItemValidity for pre-addition checks

                if ($item->getID() == null) {
                    $item->addIntoDatabase(true);
                }

                $insertIntoDocumentItemsStatement = DatabaseManager::PrepareStatement(
                    "INSERT INTO document_items (Invoices_ID, Items_ID, Quantity) values (:invoiceID, :itemID, :quantity)"
                );

                $itemID = $item->getID();
                $quantity = $itemRow->getQuantity();

                $insertIntoDocumentItemsStatement->bindParam(":invoiceID", $this->entryID);
                $insertIntoDocumentItemsStatement->bindParam(":itemID", $itemID);
                $insertIntoDocumentItemsStatement->bindParam(":quantity", $quantity);
                $insertIntoDocumentItemsStatement->execute();

            }
        }
        catch (DocumentTypeNotFound $exception){
            throw $exception;
        }
        catch (DocumentInvalid $exception){
            throw $exception;
        }
        catch (DocumentItemInvalid $exception){
            throw $exception;
        }
        catch (PDOException $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }
    }

    public function updateItems($items, $connected = false){
        try{
            if(!$connected)
                DatabaseManager::Connect();

            $this->itemsContainer->clearContents();

            foreach($items as $itemRowJSON){
                if(!array_key_exists('quantity', $itemRowJSON))
                    ResponseHandler::getInstance()
                        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ITEM_QUANTITY_INVALID"))
                        ->send();

                if(!array_key_exists('item', $itemRowJSON))
                    ResponseHandler::getInstance()
                        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ITEM_OBJECT_INVALID"))
                        ->send();

                $itemJSON = $itemRowJSON['item'];

                $item = new DocumentItem();

                foreach(array_keys($itemJSON) as $itemKey){
                    switch ($itemKey) {
                        case 'ID' :             $item->setID($itemJSON[$itemKey]);                                                              break;
                        case 'productNumber':   $item->setProductNumber($itemJSON[$itemKey]);                                                   break;
                        case 'description' :    $item->setTitle($itemJSON[$itemKey]);                                                           break;
                        case 'unitPrice' :      $item->setValueBeforeTax($itemJSON[$itemKey]);                                                  break;
                        case 'taxPercentage' :  $item->setTaxPercentage($itemJSON[$itemKey]);                                                   break;
                        case 'currencyTitle' :  $item->setCurrency(Currency::getCurrencyByTitle($itemJSON[$itemKey], true));    break;
                    }
                }

                try {
                    $item->addIntoDatabase(true);
                } catch (DocumentItemInvalid $exception){
                    continue;
                }

                $this->addItem($item, $itemRowJSON['quantity']);

            }
            $removeCurrentItemLinksStatement = DatabaseManager::PrepareStatement("
                DELETE FROM document_items WHERE Invoices_ID = :invoiceID
            ");
            $removeCurrentItemLinksStatement->bindParam("invoiceID", $this->entryID);
            $removeCurrentItemLinksStatement->execute();

            foreach($this->itemsContainer->getDocumentItemRows() as $itemContainerRow){

                $insertNewLinksIntoDBStatement = DatabaseManager::PrepareStatement("
                    INSERT INTO document_items (Invoices_ID, Items_ID, Quantity) VALUES (:entryID, :itemID, :quantity)
                ");

                $itemID = $itemContainerRow->getItemReference()->getID();
                $quantity = $itemContainerRow->getQuantity();

                $insertNewLinksIntoDBStatement->bindParam(":entryID", $this->entryID);
                $insertNewLinksIntoDBStatement->bindParam(":itemID", $itemID);
                $insertNewLinksIntoDBStatement->bindParam(":quantity", $quantity);
                $insertNewLinksIntoDBStatement->execute();

            }

            if(!$connected)
                DatabaseManager::Disconnect();
        }
        catch (PDOException $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }
    }

    public function updateIntoDatabase($documentJSON){
        if($this->isSent == true)
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_UNMODIFIABLE_SENT"))
                ->send();

        if(array_key_exists('documentType', $documentJSON))
            if($documentJSON['documentType'] != 'Invoice')
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_TYPE_INVALID"))
                    ->send();

        $updateStatementString = "UPDATE documents SET";

        foreach(array_keys($documentJSON) as $key){
            switch ($key){
                case 'senderID' :
                    $this->setSenderID($documentJSON[$key]);
                    $updateStatementString = $updateStatementString . ' Sender_User_ID = :senderID,';
                    break;
                case 'senderInstitutionID' :
                    $this->setSenderInstitutionID($documentJSON[$key]);
                    $updateStatementString = $updateStatementString . ' Sender_Institution_ID = :senderInstitutionID,';
                    break;
                case 'senderAddressID' :
                    $this->setSenderAddressID($documentJSON[$key]);
                    $updateStatementString = $updateStatementString . ' Sender_Address_ID = :senderAddressID,';
                    break;
                case 'creatorID' :
                    $this->setCreatorID($documentJSON[$key]);
                    $updateStatementString = $updateStatementString . ' Creator_User_ID = :creatorUserID,';
                    break;
                case 'receiverID' :
                    $this->setReceiverID($documentJSON[$key]);
                    $updateStatementString = $updateStatementString . ' Receiver_User_ID = :receiverID,';
                    break;
                case 'receiverInstitutionID' :
                    $this->setReceiverInstitutionID($documentJSON[$key]);
                    $updateStatementString = $updateStatementString . ' Receiver_Institution_ID = :receiverInstitutionID,';
                    break;
                case 'receiverAddressID' :
                    $this->setReceiverAddressID($documentJSON[$key]);
                    $updateStatementString = $updateStatementString . ' Receiver_Address_ID = :receiverAddressID,';
                    break;

                case 'receiptID' :              $this->setReceiptDocumentID($documentJSON[$key]);           break;  /// TODO : receipt id
                case 'items' :                  $this->updateItems($documentJSON['items']);                 break;
            }

            $updateStatementString = substr($updateStatementString, 0, -1) . ' WHERE ID = :ID';
        }

        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement($updateStatementString);
            $statement->bindParam(":ID", $documentJSON['ID']);

            foreach (array_keys($documentJSON) as $key){
                switch($key){
                    case 'senderID' :               $statement->bindParam(":senderID", $documentJSON[$key]);                break;
                    case 'senderInstitutionID' :    $statement->bindParam(":senderInstitutionID", $documentJSON[$key]);     break;
                    case 'senderAddressID' :        $statement->bindParam(":senderAddressID", $documentJSON[$key]);         break;
                    case 'creatorID' :              $statement->bindParam(":creatorID", $documentJSON[$key]);               break;
                    case 'receiverID' :             $statement->bindParam(":receiverID", $documentJSON[$key]);              break;
                    case 'receiverInstitutionID' :  $statement->bindParam(":receiverInstitutionID", $documentJSON[$key]);   break;
                    case 'receiverAddressID' :      $statement->bindParam(":receiverAddressID", $documentJSON[$key]);       break;
                }
            }

            $statement->execute();

            DatabaseManager::Disconnect();
        }
        catch (PDOException $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }

    }

    public function fetchFromDatabase($connected = false){
        $this->fetchFromDatabaseByDocumentID($connected);
    }

    public function getDAO(){
        return new \DAO\Invoice($this);
    }

    public function __construct(){
        parent::__construct();
        $this->itemsContainer           = new DocumentItemContainer();
        $this->entryID                  = null;
        $this->receiptID                = null;
        $this->receiverInstitutionID    = null;
    }

    /**
     *
     * Call Example :
        $invoice = new Invoice();

        $invoice->setID(1)->fetchFromDatabaseDocumentByID();                                MUST HAVE ID SET

        try{
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
                ->addResponseData("documentType", "invoice")
                ->addResponseData("document", $invoice->getDAO())
                ->send();
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
                ->send(StatusCodes::INTERNAL_SERVER_ERROR);
        }
     *
     * Call will populate invoice object, which can be sent into response data with the given model
     * @param bool $connected
     */
    public function fetchFromDatabaseByDocumentID($connected = false)
    {
        try{
            parent::fetchFromDatabaseDocumentBaseByID($connected);

            //echo $this->ID, PHP_EOL;

            if(!$connected)
                DatabaseManager::Connect();
            $statement = DatabaseManager::PrepareStatement(self::$getFromDatabaseByDocumentID);
            $statement->bindParam(":documentID", $this->ID);
            $statement->execute();

            //$statement->debugDumpParams();

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            //print_r($row);

            if($row != null) {
                $this->entryID = $row['ID'];
                //$this->ID = $row['Documents_ID'];
                $this->receiptID = $row['Receipts_ID'];

                if($this->receiptID != null) {
                    $getFromReceiptStatement = DatabaseManager::PrepareStatement(self::$getDocumentsIDFromReceipts);
                    $getFromReceiptStatement->bindParam(":receiptID", $this->receiptID);
                    $getFromReceiptStatement->execute();

                    $getFromReceiptStatement->debugDumpParams();

                    $receiptRow = $getFromReceiptStatement->fetch();
                    $this->receiptDocumentID = $receiptRow['Documents_ID'];
                }

                $getFromDocumentItemsStatement = DatabaseManager::PrepareStatement(self::$getItemByInvoiceID);
                $getFromDocumentItemsStatement->bindParam(":entryID", $this->entryID);
                $getFromDocumentItemsStatement->execute();

                while($itemRow = $getFromDocumentItemsStatement->fetch(PDO::FETCH_ASSOC)){
                    $this->itemsContainer->addItem(
                        DocumentItem::fetchFromDatabaseByID($itemRow['Items_ID'], $connected),
                        $itemRow['Quantity']
                        );
                }
            }

            if(!$connected)
                DatabaseManager::Disconnect();
        }
        catch (Exception $exception) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus('DB_EXCEPT'))
                ->send();
            die();
        }

    }

    /**
     * @return int
     */
    public function getEntryID(){
        return $this->entryID;
    }

    /**
     * @return int
     */
    public function getReceiptID(){
        return $this->receiptID;
    }

    /**
     * @return int
     */
    public function getReceiptDocumentID(){
        return $this->receiptDocumentID;
    }

    /**
     * @return DocumentItemContainer
     */
    public function getItemsContainer(){
        return $this->itemsContainer;
    }

    /**
     * @param int $entryID
     * @return Invoice
     */
    public function setEntryID($entryID){
        $this->entryID = $entryID;
        return $this;
    }

    /**
     * @param int $receiptID
     * @return Invoice
     */
    public function setReceiptID($receiptID){
        $this->receiptID = $receiptID;
        return $this;
    }

    /**
     * @param int $receiptDocumentID
     * @return Invoice
     */
    public function setReceiptDocumentID($receiptDocumentID){
        $this->receiptDocumentID = $receiptDocumentID;
        return $this;
    }

    /**
     * @param DocumentItemContainer $itemsContainer
     * @return Invoice
     */
    public function setItemsList($itemsContainer){
        $this->itemsContainer = $itemsContainer;
        return $this;
    }

    private static $getFromDatabaseByDocumentID = "
    SELECT * from invoices where Documents_ID = :documentID
    ";

    private static $getDocumentsIDFromReceipts = "
    SELECT Documents_ID from receipts where ID = :receiptID
    ";

    private static $getItemByInvoiceID = "
    SELECT * FROM document_items WHERE Invoices_ID = :entryID
    ";

}
?>