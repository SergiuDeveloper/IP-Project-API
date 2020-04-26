<?php


namespace DAO {

    if (!defined('ROOT')) {
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . '/DataAccessObject/Document/ItemRow.php');

    class Receipt extends Document {
        /**
         * @var ItemRow[]
         */
        public $items;
        public $invoiceID;
        public $paymentMethod;

        /**
         * Invoice constructor.
         * @param \Receipt $receipt
         */
        public function __construct($receipt)
        {
            parent::__construct(
                $receipt->getID(),
                $receipt->getSenderID(),
                $receipt->getSenderInstitutionID(),
                $receipt->getSenderAddressID(),
                $receipt->getReceiverID(),
                $receipt->getReceiverInstitutionID(),
                $receipt->getReceiverAddressID(),
                $receipt->getCreatorID()
            );

            $this->invoiceID        = $receipt->getInvoiceDocumentID();
            $this->paymentMethod    = $receipt->getPaymentMethod()->getTitle();

            $this->items = array();

            foreach ($receipt->getItemsContainer()->getDocumentItemRows() as $itemRow){
                array_push($this->items, new ItemRow($itemRow));
            }
        }
    }
}