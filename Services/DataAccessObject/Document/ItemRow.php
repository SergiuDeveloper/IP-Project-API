<?php

namespace DAO {

    if (!defined('ROOT')) {
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . '/DataAccessObject/Document/Item.php');

    use DocumentItemContainerRow;

    class ItemRow{
        public $quantity;
        public $unitPrice;
        public $unitPriceWithTax;

        /**
         * @var Item
         */
        public $item;

        /**
         * ItemRow constructor.
         * @param DocumentItemContainerRow $itemContainerRow
         */
        public function __construct($itemContainerRow){
            $this->quantity         = $itemContainerRow->getQuantity();
            $this->unitPrice        = $itemContainerRow->getUnitPrice();
            $this->unitPriceWithTax = $itemContainerRow->getUnitPriceWithTax();

            $this->item             = new Item($itemContainerRow->getItemReference());
        }
    }
}

?>