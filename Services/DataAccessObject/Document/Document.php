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

        /**
         * Document constructor.
         * @param \Document $document
         */
        public function __construct($document){
            $this->ID                       = $document->getID();
            $this->senderID                 = $document->getSenderID();
            $this->senderInstitutionID      = $document->getSenderInstitutionID();
            $this->senderAddressID          = $document->getSenderAddressID();
            $this->receiverID               = $document->getReceiverID();
            $this->receiverInstitutionID    = $document->getReceiverInstitutionID();
            $this->receiverAddressID        = $document->getReceiverAddressID();
            $this->creatorID                = $document->getCreatorID();
            $this->dateCreated              = $document->getDateCreated();
            $this->dateSent                 = $document->getDateSent();
            $this->isSent                   = $document->isSent();
        }
    }
}
?>