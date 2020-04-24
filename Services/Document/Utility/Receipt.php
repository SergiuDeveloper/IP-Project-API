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
     * @var integer ID of the payment method
     */
    private $paymentMethodID;

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

    /**
     * TODO : in service or in here
     */
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