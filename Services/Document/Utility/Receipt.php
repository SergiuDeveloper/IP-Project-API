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
require_once (ROOT . '/Document/Utility/Exception/DocumentExceptions.php');
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
     * @var string record of the payment acknowledgement
     */
    private $paymentNumber;

    /**
     * @var DocumentItemContainer items mentioned in the receipt
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
     * TODO : in service or in here
     * @throws DocumentTypeNotFound
     * @throws DocumentInvalid
     * @throws DocumentItemInvalid
     */
    public function addIntoDatabase(){
        try {
            DatabaseManager::Connect();

            $getDocumentTypeID = DatabaseManager::PrepareStatement(
                "SELECT ID FROM document_types WHERE LOWER(Title) = LOWER(:title)"
            );

            $documentTypeTitle = 'Receipt';
            $getDocumentTypeID->bindParam(":title", $documentTypeTitle);
            $getDocumentTypeID->execute();

            if (($row = $getDocumentTypeID->fetch(PDO::FETCH_OBJ)) == null)
                throw new DocumentTypeNotFound();

            parent::insertIntoDatabaseDocumentBase($row->ID, true);

            $invoice = null;

            if($this->invoiceDocumentID != null){
                $invoice = new Invoice();
                $invoice->setID($this->invoiceDocumentID)->fetchFromDatabase(true);
                if($invoice->getEntryID() == null)
                    $invoice = null;

                //echo json_encode($invoice->getDAO()), PHP_EOL;

                if($invoice != null){
                    $paymentAmount = 0;

                    foreach($invoice->getItemsContainer()->getDocumentItemRows() as $itemRow){
                        $paymentAmount = $itemRow->getUnitPriceWithTax() + $paymentAmount;

                        $this->addItem($itemRow->getItemReference(), $itemRow->getQuantity());
                    }

                    $this->paymentAmount = $paymentAmount;
                    $this->setItemsList($invoice->getItemsContainer());
                }
            }

            $insertReceiptIntoDatabaseStatement = "INSERT INTO receipts (Documents_ID";

            if($invoice != null)
                $insertReceiptIntoDatabaseStatement = $insertReceiptIntoDatabaseStatement . ', Invoices_ID';

            if($this->paymentNumber != null)
                $insertReceiptIntoDatabaseStatement = $insertReceiptIntoDatabaseStatement . ', Payment_Number';

            if($this->paymentMethod != null)
                $insertReceiptIntoDatabaseStatement = $insertReceiptIntoDatabaseStatement . ', Payment_Methods_ID';

            $insertReceiptIntoDatabaseStatement = $insertReceiptIntoDatabaseStatement . ') values (:documentID';

            if($invoice != null)
                $insertReceiptIntoDatabaseStatement = $insertReceiptIntoDatabaseStatement . ', :invoiceID';

            if($this->paymentNumber != null)
                $insertReceiptIntoDatabaseStatement = $insertReceiptIntoDatabaseStatement . ', :paymentNumber';

            if($this->paymentMethod != null)
                $insertReceiptIntoDatabaseStatement = $insertReceiptIntoDatabaseStatement . ', :paymentMethodID';

            $insertReceiptIntoDatabaseStatement = $insertReceiptIntoDatabaseStatement . ')';

            $insertDocumentIntoDatabaseStatement = DatabaseManager::PrepareStatement(
                $insertReceiptIntoDatabaseStatement
            );
            $insertDocumentIntoDatabaseStatement->bindParam(":documentID", $this->ID);

            $invoiceID = $invoice->getEntryID();

            if($invoice != null)
                $insertDocumentIntoDatabaseStatement->bindParam(":invoiceID", $invoiceID);

            if($this->paymentNumber != null)
                $insertDocumentIntoDatabaseStatement->bindParam(":paymentNumber", $this->paymentNumber);

            if($this->paymentMethod != null){
                $paymentMethodID = $this->paymentMethod->getID();
                $insertDocumentIntoDatabaseStatement->bindParam(":paymentMethodID", $paymentMethodID);
            }

            $insertDocumentIntoDatabaseStatement->execute();
            //$insertDocumentIntoDatabaseStatement->debugDumpParams();

            $this->setEntryID(DatabaseManager::GetLastInsertID());

            if($invoice != null){
                foreach($invoice->getItemsContainer()->getDocumentItemRows() as $itemRow){
                    $updateItemStatement = DatabaseManager::PrepareStatement(
                        "UPDATE document_items set Receipts_ID = :receiptID WHERE Invoices_ID = :invoiceID AND Items_ID = :itemID"
                    );

                    $itemID = $itemRow->getItemReference()->getID();
                    $invoiceTableID = $invoice->getEntryID();

                    $updateItemStatement->bindParam(":invoiceID", $invoiceTableID);
                    $updateItemStatement->bindParam(":itemID", $itemID);
                    $updateItemStatement->bindParam(":receiptID", $this->entryID);

                    $updateItemStatement->execute();

                    //$updateItemStatement->debugDumpParams();
                }
            }

            if($invoice == null) {
                foreach ($this->itemsContainer->getDocumentItemRows() as $itemRow) {
                    $item = $itemRow->getItemReference();

                    $item->fetchFromDatabase(true); /// TODO : implement DocumentItem :: checkItemValidity for pre-addition checks

                    if ($item->getID() == null) {
                        $item->addIntoDatabase(true);
                    }

                    $insertIntoDocumentItemsStatement = DatabaseManager::PrepareStatement(
                        "INSERT INTO document_items (Receipts_ID, Items_ID, Quantity) values (:receiptID, :itemID, :quantity)"
                    );

                    $itemID = $item->getID();
                    $quantity = $itemRow->getQuantity();

                    $insertIntoDocumentItemsStatement->bindParam(":receiptID", $this->entryID);
                    $insertIntoDocumentItemsStatement->bindParam(":itemID", $itemID);
                    $insertIntoDocumentItemsStatement->bindParam(":quantity", $quantity);
                    $insertIntoDocumentItemsStatement->execute();

                }
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

                $this->paymentAmount = 0;

                while($itemRow = $getFromDocumentItemsStatement->fetch(PDO::FETCH_ASSOC)){
                    $this->itemsContainer->addItem(
                            DocumentItem::fetchFromDatabaseByID($itemRow['Items_ID']),
                            $itemRow['Quantity']
                        );
                }

                foreach($this->getItemsContainer()->getDocumentItemRows() as $itemRow)
                    $this->paymentAmount = $this->paymentAmount + $itemRow->getUnitPriceWithTax();

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

    /**
     * @return string
     */
    public function getPaymentNumber(){
        return $this->paymentNumber;
    }

    /**
     * @param $paymentNumber
     * @return $this
     */
    public function setPaymentNumber($paymentNumber){
        $this->paymentNumber = $paymentNumber;
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
?>