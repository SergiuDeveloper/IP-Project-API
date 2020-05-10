<?php

namespace DAO {

    if (!defined('ROOT')) {
        define('ROOT', dirname(__FILE__) . '/../..');
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
            parent::__construct($invoice);

            $this->items = array();

            foreach ($invoice->getItemsContainer()->getDocumentItemRows() as $itemRow){
                array_push($this->items, new ItemRow($itemRow));
            }
        }
    }
}

?>