<?php

if( !defined('ROOT') ){
    define('ROOT', dirname(__FILE__) . "/../..");
}

require_once ( ROOT . '/Document/Utility/DocumentItemContainerRow.php' );

class DocumentItemContainer{

    /**
     * @var DocumentItemContainerRow[]
     */
    private $documentItemRows;

    public function __construct(){
        $this->documentItemRows = array();
    }

    /**
     * @return DocumentItemContainerRow[]
     */
    public function getDocumentItemRows(){
        return $this->documentItemRows;
    }

    public function clearContents(){
        $this->documentItemRows = array();
    }

    /**
     * @param DocumentItem $item
     * @return int
     */
    public function indexOf($item){
        for($i = 0; $i < count($this->documentItemRows); $i++)
            if($this->documentItemRows[$i]->getItemReference()->getID() == $item->getID())
                return $i;
        return -1;
    }

    /**
     * @param DocumentItem $item
     * @param int $quantity
     */
    public function addItem($item, $quantity = 1){
        foreach($this->documentItemRows as $documentItemContainerRow)
            if($documentItemContainerRow->getItemReference()->getID() == $item->getID()){
                $documentItemContainerRow->setQuantity($documentItemContainerRow->getQuantity() + $quantity);
                return;
            }
        array_push($this->documentItemRows, new DocumentItemContainerRow($item, $quantity));
    }

    /**
     * @param DocumentItem $item
     */
    public function removeRow($item){
        for($i = 0; $i < count($this->documentItemRows); $i++){
            if($this->documentItemRows[$i]->getItemReference()->getID() == $item->getID() ){
                array_splice($this->documentItemRows, $i, 1);
                return;
            }
        }
    }

    /**
     * @param DocumentItem $item
     * @param int $quantity
     */
    public function removeItem($item, $quantity = 1){
        for($i = 0; $i < count($this->documentItemRows); $i++){
            if($this->documentItemRows[$i]->getItemReference()->getID() == $item->getID() ){
                $this->documentItemRows[$i]->setQuantity($this->documentItemRows[$i]->getQuantity() - $quantity);

                if($this->documentItemRows[$i]->getQuantity() <= 0){
                    array_splice($this->documentItemRows, $i, 1);
                }

                return;
            }
        }
    }
}
