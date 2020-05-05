<?php

namespace DAO{

    use DocumentItem;

    class Item{
        public $ID;
        public $productNumber;
        public $description;
        public $unitPrice;
        public $itemTax;
        public $unitPriceWithTax;
        public $currencyTitle;

        /**
         * Item constructor.
         * @param DocumentItem $item
         */
        public function __construct($item){
            $this->ID               = $item->getID();
            $this->productNumber    = $item->getProductNumber();
            $this->description      = $item->getTitle();
            $this->unitPrice        = $item->getValueBeforeTax();
            $this->itemTax          = $item->getTaxPercentage();
            $this->unitPriceWithTax = $item->getValueAfterTax();
            $this->currencyTitle    = $item->getCurrency()->getTitle();
        }
    }
}


?>