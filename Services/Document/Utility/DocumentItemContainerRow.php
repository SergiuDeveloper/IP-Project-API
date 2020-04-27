<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . '/../..');
}

require_once (ROOT . '/Document/Utility/DocumentItem.php');

class DocumentItemContainerRow{
    private $quantity;
    private $productNumber;
    private $description;
    private $unitPrice;
    private $itemTax;
    private $unitPriceWithTax;

    /**
     * @var DocumentItem
     */
    private $itemReference;

    /**
     * DocumentItemContainerRow constructor.
     * @param DocumentItem $item
     * @param $quantity
     */
    public function __construct($item, $quantity){
        $this->quantity         = $quantity;
        $this->productNumber    = $item->getProductNumber();
        $this->description      = $item->getTitle();
        $this->unitPrice        = $item->getValueBeforeTax() * $this->quantity;
        $this->itemTax          = $item->getTaxPercentage();
        $this->unitPriceWithTax = $item->getValueAfterTax() * $this->quantity;

        $this->itemReference    = $item;
    }

    /**
     * @param string $description
     * @return DocumentItemContainerRow
     */
    public function setDescription($description){
        $this->description = $description;
        return $this;
    }

    /**
     * @param DocumentItem $itemReference
     * @return DocumentItemContainerRow
     */
    public function setItemReference($itemReference){
        $this->itemReference = $itemReference;

        $this->itemTax          = $this->itemReference->getTaxPercentage();
        $this->unitPrice        = $this->quantity * $this->itemReference->getValueBeforeTax();
        $this->unitPriceWithTax = $this->quantity * $this->itemReference->getValueAfterTax();

        return $this;
    }

    /**
     * @param float $itemTax
     * @return DocumentItemContainerRow
     */
    public function setItemTax($itemTax){
        $this->itemTax = $itemTax;
        return $this;
    }

    /**
     * @param int $productNumber
     * @return DocumentItemContainerRow
     */
    public function setProductNumber($productNumber){
        $this->productNumber = $productNumber;
        return $this;
    }

    /**
     * @param int $quantity
     * @return DocumentItemContainerRow
     */
    public function setQuantity($quantity){
        $this->quantity = $quantity;

        $this->unitPrice        = $this->quantity * $this->itemReference->getValueBeforeTax();
        $this->unitPriceWithTax = $this->quantity * $this->itemReference->getValueAfterTax();

        return $this;
    }

    /**
     * @param float $unitPrice
     * @return DocumentItemContainerRow
     */
    public function setUnitPrice($unitPrice){
        $this->unitPrice = $unitPrice;
        return $this;
    }

    /**
     * @param float $unitPriceWithTax
     * @return DocumentItemContainerRow
     */
    public function setUnitPriceWithTax($unitPriceWithTax){
        $this->unitPriceWithTax = $unitPriceWithTax;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(){
        return $this->description;
    }

    /**
     * @return DocumentItem
     */
    public function getItemReference()
    {
        return $this->itemReference;
    }

    /**
     * @return float
     */
    public function getItemTax(){
        return $this->itemTax;
    }

    /**
     * @return int
     */
    public function getProductNumber(){
        return $this->productNumber;
    }

    /**
     * @return int
     */
    public function getQuantity(){
        return $this->quantity;
    }

    /**
     * @return float
     */
    public function getUnitPrice(){
        return $this->unitPrice;
    }

    /**
     * @return float
     */
    public function getUnitPriceWithTax(){
        return $this->unitPriceWithTax;
    }
}