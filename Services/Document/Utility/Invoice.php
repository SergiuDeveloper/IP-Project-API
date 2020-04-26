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
     */
    public function addItem($item){
        $this->itemsContainer->addItem($item);
    }

    public function addIntoDatabase(){
        // TODO: Implement addIntoDatabase() method.
    }

    public function updateIntoDatabase(){
        // TODO: Implement updateIntoDatabase() method.
    }

    public function fetchFromDatabase(){
        // TODO: Implement fetchFromDatabase() method.
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
     */
    public function fetchFromDatabaseDocumentByID()
    {
        try{
            parent::fetchFromDatabaseDocumentBaseByID();

            DatabaseManager::Connect();
            $statement = DatabaseManager::PrepareStatement(self::$getFromDatabaseByDocumentID);
            $statement->bindParam(":ID", $this->ID);
            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if($row != null) {
                $this->entryID = $row['ID'];
                $this->ID = $row['Documents_ID'];
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
                        DocumentItem::fetchFromDatabaseByID($itemRow['Items_ID']),
                        $itemRow['Quantity']
                        );
                }
            }

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

    public function upload() {
        
    }

    private static $getFromDatabaseByDocumentID = "
    SELECT * from invoices where Documents_ID = :ID
    ";

    private static $getDocumentsIDFromReceipts = "
    SELECT Documents_ID from receipts where ID = :receiptID
    ";

    private static $getItemByInvoiceID = "
    SELECT * FROM document_items WHERE Invoices_ID = :entryID
    ";

}