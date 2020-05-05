<?php

namespace DAO {
    class Document{
        public $ID;
        public $senderID;
        public $senderInstitutionID;
        public $senderAddressID;
        public $receiverID;
        public $receiverInstitutionID;
        public $receiverAddressID;
        public $creatorID;
        public $dateCreated;
        public $dateSent;
        public $isSent;

        public function __construct(
            $ID,
            $senderID,
            $senderInstitutionID,
            $senderAddressID,
            $receiverID,
            $receiverInstitutionID,
            $receiverAddressID,
            $creatorID,
            $dateCreated = null,
            $dateSent = null,
            $isSent = null
        ){
            $this->ID                       = $ID;
            $this->senderID                 = $senderID;
            $this->senderInstitutionID      = $senderInstitutionID;
            $this->senderAddressID          = $senderAddressID;
            $this->receiverID               = $receiverID;
            $this->receiverInstitutionID    = $receiverInstitutionID;
            $this->receiverAddressID        = $receiverAddressID;
            $this->creatorID                = $creatorID;
            $this->dateCreated              = $dateCreated;
            $this->dateSent                 = $dateSent;
            $this->isSent                   = $isSent;
        }
    }
}
?>