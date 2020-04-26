<?php

namespace DAO {

    if (!defined('ROOT')) {
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . '/DataAccessObject/Document/ItemRow.php');

    class Invoice extends Document{
        /**
         * @var ItemRow[]
         */
        public $items;

        /**
         * Invoice constructor.
         * @param \Invoice $invoice
         */
        public function __construct($invoice)
        {
            parent::__construct(
                $invoice->getID(),
                $invoice->getSenderID(),
                $invoice->getSenderInstitutionID(),
                $invoice->getSenderAddressID(),
                $invoice->getReceiverID(),
                $invoice->getReceiverInstitutionID(),
                $invoice->getReceiverAddressID(),
                $invoice->getCreatorID()
            );

            $this->items = array();

            foreach ($invoice->getItemsContainer()->getDocumentItemRows() as $itemRow){
                array_push($this->items, new ItemRow($itemRow));
            }
        }
    }
}