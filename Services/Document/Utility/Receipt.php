<?php


class Receipt extends Document
{
    /**
     * @var integer Database entry of the receipt ID. Different from the ID of the document (DB Entries separated). Only used in DB
     */
    private $entryID;

    /**
     * @var integer document ID of the linked invoice of this receipt. SAME as in documents table, not invoices table.
     */
    private $receiptID;

    /**
     * @var integer receipt ID of the linked invoice of this receipt. SAME as as in invoices table, not documents table. Not to be shown in document.
     */
    private $receiptDocumentID;

    /**
     * @var double total payment amount
     */
    private $paymentAmount;
    /**
     * @var integer ID of the payment method
     */
    private $paymentMethodID;

    /**
     * @var array(Item) items mentioned in the receipt
     */
    private $itemsList;

    public function addIntoDatabase(){
        // TODO: Implement addIntoDatabase() method.
    }

    public function updateIntoDatabase(){
        // TODO: Implement updateIntoDatabase() method.
    }

    public function fetchFromDatabase(){
        // TODO: Implement fetchFromDatabase() method.
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
     * @return array
     */
    public function getItemsList(){
        return $this->itemsList;
    }

    /**
     * @return float
     */
    public function getPaymentAmount()
    {
        return $this->paymentAmount;
    }

    /**
     * @return int
     */
    public function getPaymentMethodID()
    {
        return $this->paymentMethodID;
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
     * @param int $receiptID
     * @return Receipt
     */
    public function setReceiptID($receiptID){
        $this->receiptID = $receiptID;
        return $this;
    }

    /**
     * @param int $receiptDocumentID
     * @return Receipt
     */
    public function setReceiptDocumentID($receiptDocumentID){
        $this->receiptDocumentID = $receiptDocumentID;
        return $this;
    }

    /**
     * @param array $itemsList
     * @return Receipt
     */
    public function setItemsList($itemsList){
        $this->itemsList = $itemsList;
        return $this;
    }

    /**
     * @param float $paymentAmount
     * @return Receipt
     */
    public function setPaymentAmount($paymentAmount)
    {
        $this->paymentAmount = $paymentAmount;
        return $this;
    }

    /**
     * @param int $paymentMethodID
     * @return Receipt
     */
    public function setPaymentMethodID($paymentMethodID)
    {
        $this->paymentMethodID = $paymentMethodID;
        return $this;
    }
}