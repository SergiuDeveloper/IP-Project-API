<?php


class Notification
{

    private $notificationID;
    private $notificationTypeID;
    private $notificationType;
    private $institutionID;
    private $title;
    private $content;
    private $senderID;
    private $receiverMembers;

    public static function getNotificationTypeID($notificationType){

    }

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

    public function changeSubscriptionStatusForUser($receiveMemberID, $isActive)
    {
        DatabaseManager::Connect();

        if ($isActive == false)
        {
            $unsubscribeStatement = DatabaseManager::PrepareStatement(
                "DELETE FROM notification_subscriptions WHERE User_ID = :receiveMemberID"
            );

            $unsubscribeStatement->bindParam(":receiveMemberID", $receiveMemberID);

            $unsubscribeStatement->execute();
        }
        else if ($isActive == true)
        {
            $insertStatement = DatabaseManager::PrepareStatement(
                "INSERT INTO notification_subscriptions (User_ID, Notification_ID)
                 VALUES
                 (:userID, :notificationID)"
            );

            $insertStatement->bindParam(":userID", $receiveMemberID);
            $insertStatement->bindParam(":notificationID", $this->notificationID);

            $insertStatement->execute();
        }

        DatabaseManager::Disconnect();
    }

    public static function findNotificationsByReceiver($receiveMemberID)
    {
        $notificationList = array();

        DatabaseManager::Connect();

        $getNotificationsStatement = DatabaseManager::PrepareStatement(
            "SELECT * FROM notifications n JOIN notification_subscriptions m WHERE m.User_ID = :receiveMember AND n.ID = m.Notification_ID"
        );

        $getNotificationsStatement->bindParam(":receiveMember", $receiveMemberID);

        $getNotificationsStatement->execute();

        $notificationFromSql = $getNotificationsStatement->fetch(PDO_OBJECT);

        foreach ($notificationFromSql as $notification)
        {
            array_push($notificationList, new Notification().__construct($notification["Notification_Types_ID"],
                                                                                       $notification["Institution_ID"], $notification["Title"],
                                                                                        $notification["Content"], $notification["Sender_User_ID"]));
        }

        DatabaseManager::Disconnect();

        return $notificationList;
    }

    public static function findNotificationsByReceivers($receiveMembersID)
    {
        $notificationList = array();

        DatabaseManager::Connect();

        foreach ($receiveMembersID as $receiveMemberID)
        {
            $getNotificationsStatement = DatabaseManager::PrepareStatement(
                "SELECT * FROM notifications n JOIN notification_subscriptions m WHERE m.User_ID = :receiveMember AND n.ID = m.Notification_ID"
            );

            $getNotificationsStatement->bindParam(":receiveMember", $receiveMemberID);

            $getNotificationsStatement->execute();

            $notificationFromSql = $getNotificationsStatement->fetch(PDO_OBJECT);

            foreach ($notificationFromSql as $notification)
            {
                array_push($notificationList, new Notification().__construct($notification["Notification_Types_ID"],
                        $notification["Institution_ID"], $notification["Title"],
                        $notification["Content"], $notification["Sender_User_ID"]));
            }
        }

        DatabaseManager::Disconnect();

        return $notificationList;
    }

}


