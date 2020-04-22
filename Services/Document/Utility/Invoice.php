<?php


class Invoice extends Document
{
    /**
     * @var integer Database entry of the invoice ID. Different from the ID of the document (DB Entries separated). Only used in DB
     */
    private $entryID;

    /**
     * @var integer document ID of the linked receipt of this invoice. SAME as in documents table, not receipts table.
     */
    private $receiptID;

    /**
     * @var integer receipt ID of the linked receipt of this invoice. SAME as as in receipts table, not documents table. Not to be shown in document.
     */
    private $receiptDocumentID;

    /**
     * @var array(Item) items mentioned in the invoice
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
     * @param array $itemsList
     * @return Invoice
     */
    public function setItemsList($itemsList){
        $this->itemsList = $itemsList;
        return $this;
    }
}