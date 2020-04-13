<?php


class Notification
{
    private $notificationType;
    private $institutionID;
    private $title;
    private $content;
    private $senderID;
    private $receiverMembers;
    private $notificationTypeID;

    public function __construct($notificationType, $institutionID, $title = null, $content = null, $senderID = null, $receiverMembers = null) {
        $this->notificationType = $notificationType;
        $this->institutionID = $institutionID;
        $this->title = $title;
        $this->content = $content;
        $this->senderID = $senderID;
        $this->receiverMembers = $receiverMembers;
    }

    public function setNotificationType($notificationType) {
        $this->notificationType = $notificationType;
    }

    public function setInstitutionID($institutionID) {
        $this->institutionID = $institutionID;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function setSenderID($senderID)
    {
        $this->senderID = $senderID;
    }

    public function setReceiverMembers($receiverMembers)
    {
        $this->receiverMembers = $receiverMembers;
    }

    public function getNotificationType()
    {
        return $this->notificationType;
    }

    public function getInstitutionID()
    {
        return $this->institutionID;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getSenderID()
    {
        return $this->senderID;
    }

    public function getReceiverMembers()
    {
        return $this->receiverMembers;
    }

    public function insertIntoDB() {
        DatabaseManager::Connect();

        $insertStatement = DatabaseManager::PrepareStatement(
            "INSERT INTO notifications (Institution_ID, Notification_Types_ID, Title, Content) 
            VALUES 
            (:institutionID, :notification_types_ID, :title, :content)");

        $insertStatement->bindParam(":institutionID", $this->institutionID);
        $insertStatement->bindParam(":notification_types_ID", $this->notificationTypeID);
        $insertStatement->bindParam(":title", $this->title);
        $insertStatement->bindParam(":content", $this->content);

        $insertStatement->execute();
    }
}


