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

        public $documentType;

        /**
         * Document constructor.
         * @param \Document $document
         */
        public function __construct($document = null){
            if($document != null){
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
                $this->documentType             = null;
            }
        }

        /**
         * @param $type
         * @return $this
         */
        public function setDocumentType($type){
            $this->documentType = $type;
            return $this;
        }
    }
}
?>