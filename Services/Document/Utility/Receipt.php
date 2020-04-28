<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . '/../..');
}

require_once (ROOT . '/Document/Utility/Document.php' );
require_once (ROOT . '/Document/Utility/DocumentItem.php' );
require_once (ROOT . '/Document/Utility/DocumentItemContainer.php' );
require_once (ROOT . '/Document/Utility/DocumentItemContainerRow.php' );
require_once (ROOT . '/Document/Utility/Invoice.php' );
require_once (ROOT . '/Document/Utility/PaymentMethod.php' );
require_once (ROOT . '/Document/Utility/Currency.php' );

require_once ( ROOT . '/DataAccessObject/DataObjects.php' );

class Receipt extends Document
{
    /**
     * @var integer Database entry of the receipt ID. Different from the ID of the document (DB Entries separated). Only used in DB
     */
    private $entryID;

    /**
     * @var integer document ID of the linked invoice of this receipt. SAME as in documents table, not invoices table.
     */
    private $invoiceID;

    /**
     * @var integer receipt ID of the linked invoice of this receipt. SAME as as in invoices table, not documents table. Not to be shown in document.
     */
    private $invoiceDocumentID;

    /**
     * @var double total payment amount
     */
    private $paymentAmount;
    /**
     * @var PaymentMethod ID of the payment method
     */
    private $paymentMethod;

    /**
     * @var DocumentItemContainer items mentioned in the receipt
     */
    private $itemsContainer;

    /**
     * @param DocumentItem $item
     */
    public function addItem($item){
        $this->itemsContainer->addItem($item);
    }

    /**
     * TODO : in service or in here
     */
    public function addIntoDatabase(){
        // TODO: Implement addIntoDatabase() method.
    }

    /**
     * TODO : in service or in here
     */
    public function updateIntoDatabase(){
        // TODO: Implement updateIntoDatabase() method.
    }

    public function fetchFromDatabaseByDocumentID(){
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
                $this->invoiceID = $row['Invoices_ID'];
                $this->paymentAmount= $row['Payment_Number'];
                $paymentID = $row['Payment_Methods_ID'];

                if($this->invoiceID != null) {
                    $getFromInvoiceStatement = DatabaseManager::PrepareStatement(self::$getDocumentsIDFromInvoices);
                    $getFromInvoiceStatement->bindParam(":invoiceID", $this->invoiceID);
                    $getFromInvoiceStatement->execute();

                    //$getFromInvoiceStatement->debugDumpParams();

                    $invoiceRow = $getFromInvoiceStatement->fetch();
                    $this->invoiceDocumentID = $invoiceRow['Documents_ID'];
                }

                $getFromDocumentItemsStatement = DatabaseManager::PrepareStatement(self::$getItemByReceiptID);
                $getFromDocumentItemsStatement->bindParam(":entryID", $this->entryID);
                $getFromDocumentItemsStatement->execute();

                while($itemRow = $getFromDocumentItemsStatement->fetch(PDO::FETCH_ASSOC)){
                    $this->itemsContainer->addItem(
                        DocumentItem::fetchFromDatabaseByID($itemRow['Items_ID']),
                        $itemRow['Quantity']
                        );
                }

                $this->paymentMethod = PaymentMethod::getPaymentMethodByID($paymentID);
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
     * @return \DAO\Receipt
     */
    public function getDAO(){
        return new \DAO\Receipt($this);
    }

    public function __construct(){
        parent::__construct();
        $this->itemsContainer           = new DocumentItemContainer();
        $this->entryID                  = null;
        $this->invoiceID                = null;
        $this->invoiceDocumentID        = null;
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
    public function getInvoiceID(){
        return $this->invoiceID;
    }

    /**
     * @return int
     */
    public function getInvoiceDocumentID(){
        return $this->invoiceDocumentID;
    }

    /**
     * @return DocumentItemContainer
     */
    public function getItemsContainer(){
        return $this->itemsContainer;
    }

    /**
     * @return float
     */
    public function getPaymentAmount(){
        return $this->paymentAmount;
    }

    /**
     * @return PaymentMethod
     */
    public function getPaymentMethod(){
        return $this->paymentMethod;
    }

    /**
     * @param int $entryID
     * @return Receipt
     */
    public function setEntryID($entryID){
        $this->entryID = $entryID;
        return $this;
    }

    /**
     * @param int $invoiceID
     * @return Receipt
     */
    public function setInvoiceID($invoiceID){
        $this->invoiceID = $invoiceID;
        return $this;
    }

    /**
     * @param int $invoiceDocumentID
     * @return Receipt
     */
    public function setReceiptDocumentID($invoiceDocumentID){
        $this->invoiceDocumentID = $invoiceDocumentID;
        return $this;
    }

    /**
     * @param DocumentItemContainer $itemsContainer
     * @return Receipt
     */
    public function setItemsList($itemsContainer){
        $this->itemsContainer = $itemsContainer;
        return $this;
    }

    /**
     * @param float $paymentAmount
     * @return Receipt
     */
    public function setPaymentAmount($paymentAmount){
        $this->paymentAmount = $paymentAmount;
        return $this;
    }

    /**
     * @param PaymentMethod $paymentMethod
     * @return Receipt
     */
    public function setPaymentMethod($paymentMethod){
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    private static $getFromDatabaseByDocumentID = "
    SELECT * from receipts where Documents_ID = :ID
    ";

    private static $getDocumentsIDFromInvoices = "
    SELECT Documents_ID from invoices where ID = :invoiceID
    ";

    private static $getItemByReceiptID = "
    SELECT * FROM document_items WHERE Receipts_ID = :entryID
    ";

    private static $getPaymentMethodByID = "
    select * from payment_methods WHERE ID = :paymentID
    ";
}