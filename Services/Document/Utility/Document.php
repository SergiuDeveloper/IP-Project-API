<?php


abstract class Document
{
    /**
     * @var integer holds the doc's id. NOT NULL
     */
    protected $ID;

    /**
     * @var integer holds the creator / sender employee id. NOT NULL.
     * TODO: add creator id as separate attribute?
     */
    protected $senderID;
    /**
     * @var integer|null holds the receiver employee id.
     */
    protected $receiverID;

    /**
     * @var integer holds the sender institution id. NOT NULL.
     */
    protected $senderInstitutionID;
    /**
     * @var integer|null holds the receiver institution id.
     */
    protected $receiverInstitutionID;

    /**
     * @var string holds the address of the sender institution
     */
    protected $senderAddressID;
    /**
     * @var string holds the address of the receiver institution
     */
    protected $receiverAddressID;

    protected function __construct($ID, $senderID, $senderInstitutionID, $senderAddressID){
        $this->ID                       = $ID;
        $this->senderID                 = $senderID;
        $this->senderInstitutionID      = $senderInstitutionID;
        $this->senderAddressID          = $senderAddressID;
        $this->receiverID               = null;
        $this->receiverInstitutionID    = null;
        $this->receiverAddressID        = null;
    }

    public abstract function addIntoDatabase();

    public abstract function updateIntoDatabase();

    public abstract function fetchFromDatabase();

    /**
     * @return integer
     */
    public function getID()
    {
        return $this->ID;
    }

    /**
     * @return string
     */
    public function getReceiverAddressID()
    {
        return $this->receiverAddressID;
    }

    /**
     * @return integer|null
     */
    public function getReceiverID()
    {
        return $this->receiverID;
    }

    /**
     * @return integer|null
     */
    public function getReceiverInstitutionID()
    {
        return $this->receiverInstitutionID;
    }

    /**
     * @return string
     */
    public function getSenderAddressID()
    {
        return $this->senderAddressID;
    }

    /**
     * @return integer
     */
    public function getSenderID()
    {
        return $this->senderID;
    }

    /**
     * @return integer
     */
    public function getSenderInstitutionID()
    {
        return $this->senderInstitutionID;
    }

    /**
     * @param integer $ID
     * @return Document
     */
    public function setID($ID){
        $this->ID = $ID;
        return $this;
    }

    /**
     * @param string $receiverAddressID
     * @return Document
     */
    public function setReceiverAddressID($receiverAddressID)
    {
        $this->receiverAddressID = $receiverAddressID;
        return $this;
    }

    /**
     * @param integer $receiverID
     * @return Document
     */
    public function setReceiverID($receiverID)
    {
        $this->receiverID = $receiverID;
        return $this;
    }

    /**
     * @param integer $receiverInstitutionID
     * @return Document
     */
    public function setReceiverInstitutionID($receiverInstitutionID)
    {
        $this->receiverInstitutionID = $receiverInstitutionID;
        return $this;
    }

    /**
     * @param string $senderAddressID
     * @return Document
     */
    public function setSenderAddressID($senderAddressID)
    {
        $this->senderAddressID = $senderAddressID;
        return $this;
    }

    /**
     * @param integer $senderID
     * @return Document
     */
    public function setSenderID($senderID)
    {
        $this->senderID = $senderID;
        return $this;
    }

    /**
     * @param integer $senderInstitutionID
     * @return Document
     */
    public function setSenderInstitutionID($senderInstitutionID)
    {
        $this->senderInstitutionID = $senderInstitutionID;
        return $this;
    }
}