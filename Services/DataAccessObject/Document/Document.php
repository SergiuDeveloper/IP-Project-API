<?php

namespace DAO {
    abstract class Document{
        public $ID;
        public $senderID;
        public $senderInstitutionID;
        public $senderAddressID;
        public $receiverID;
        public $receiverInstitutionID;
        public $receiverAddressID;
        public $creatorID;

        protected function __construct(
            $ID,
            $senderID,
            $senderInstitutionID,
            $senderAddressID,
            $receiverID,
            $receiverInstitutionID,
            $receiverAddressID,
            $creatorID
        ){
            $this->ID                       = $ID;
            $this->senderID                 = $senderID;
            $this->senderInstitutionID      = $senderInstitutionID;
            $this->senderAddressID          = $senderAddressID;
            $this->receiverID               = $receiverID;
            $this->receiverInstitutionID    = $receiverInstitutionID;
            $this->receiverAddressID        = $receiverAddressID;
            $this->creatorID                = $creatorID;
        }
    }
}
?>