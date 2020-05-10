<?php

namespace DAO {

    if (!defined('ROOT')) {
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . '/DataAccessObject/Document/ItemRow.php');

    class Receipt extends Document {
        /**
         * @var ItemRow[]
         */
        public $items;
        public $invoiceID;
        public $paymentMethod;
        public $paymentAmount;
        public $paymentNumber;

        /**
         * Invoice constructor.
         * @param \Receipt $receipt
         */
        public function __construct($receipt)
        {
            parent::__construct($receipt);

            $this->invoiceID        = $receipt->getInvoiceDocumentID();
            $this->paymentMethod    = $receipt->getPaymentMethod()->getTitle();
            $this->paymentAmount    = $receipt->getPaymentAmount();
            $this->paymentNumber    = $receipt->getPaymentNumber();

            $this->items = array();

            foreach ($receipt->getItemsContainer()->getDocumentItemRows() as $itemRow){
                array_push($this->items, new ItemRow($itemRow));
            }
        }
    }
}


?>