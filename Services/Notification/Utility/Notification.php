<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . "/../..");
}

require_once (ROOT . "/Utility/DatabaseManager.php");
require_once (ROOT . "/Utility/ResponseHandler.php");
require_once (ROOT . "/Utility/StatusCodes.php");
require_once (ROOT . "/Utility/CommonEndPointLogic.php");

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

    public function setNotificationType($notificationType) {
        $this->notificationType = $notificationType;

        $this->notificationTypeID = self::getNotificationTypeID($this->notificationType);
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

    public static function getNotificationTypeID($notificationType){
        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$insertIntoNotificationTypesStatement);
            $statement->bindParam(":name", $notificationType);
            $statement->execute();

            $statement = DatabaseManager::PrepareStatement(self::$getNotificationTypeIDByNameStatement);
            $statement->bindParam(":name", $notificationType);
            $statement->execute();

            $IDRow = $statement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();

            return $IDRow->ID;
        }
        catch(Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();

            die();
        }
    }

    public function __construct($notificationType, $institutionID, $title = null, $content = null, $senderID = null, $receiverMembers = null) {
        $this->notificationType = $notificationType;
        $this->institutionID = $institutionID;
        $this->title = $title;
        $this->content = $content;
        $this->senderID = $senderID;
        $this->receiverMembers = $receiverMembers;
    }

    public function insertIntoDB() {

        if ($this->notificationTypeID == null) {
            $this->notificationTypeID = self::getNotificationTypeID($this->notificationType);
        }

        try {
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

            DatabaseManager::Disconnect();
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }
    }

    public function changeSubscriptionStatusForUser($receiveMemberID, $isActive)
    {
        try {
            DatabaseManager::Connect();

            if ($isActive == false) {
                $unsubscribeStatement = DatabaseManager::PrepareStatement(
                    "DELETE FROM notification_subscriptions WHERE User_ID = :receiveMemberID AND Notification_ID = :notificationID"
                );

                $unsubscribeStatement->bindParam(":receiveMemberID", $receiveMemberID);
                $unsubscribeStatement->bindParam(":notificationID", $this->notificationID);

                $unsubscribeStatement->execute();
            } else if ($isActive == true) {
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
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }
    }

    public static function findNotificationsByReceiver($receiverMemberID)
    {
        try {
            DatabaseManager::Connect();

            $notificationList = array();

            $getNotificationsStatement = DatabaseManager::PrepareStatement(
                "SELECT * FROM notifications n JOIN notification_subscriptions m
                                            ON n.ID = m.Notification_ID
                                            WHERE m.User_ID = :receiverMember"
            );

            $getNotificationsStatement->bindParam(":receiverMember", $receiverMemberID);

            $getNotificationsStatement->execute();

            while ($notificationFromSql = $getNotificationsStatement->fetch(PDO::FETCH_OBJ)) {
                array_push($notificationList, new Notification(
                        $notificationFromSql->Name,
                        $notificationFromSql->InstitutionID,
                        $notificationFromSql->Title,
                        $notificationFromSql->Content,
                        $notificationFromSql->Sender_User_ID
                    )
                );
            }

            DatabaseManager::Disconnect();

            return $notificationList;
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();

            die();
        }
    }

    public static function findNotificationsByReceivers($receiveMembersID)
    {
        try {
            DatabaseManager::Connect();

            $notificationList = array();

            foreach ($receiveMembersID as $receiveMemberID) {
                $getNotificationsStatement = DatabaseManager::PrepareStatement(
                    "SELECT * FROM notifications n JOIN notification_subscriptions m
                                            ON n.ID = m.Notification_ID
                                            WHERE m.User_ID = :receiveMember
                                     "
                );

                $getNotificationsStatement->bindParam(":receiveMember", $receiveMemberID);

                $getNotificationsStatement->execute();

                while ($notification = $getNotificationsStatement->fetch(PDO::FETCH_OBJ)) {
                    array_push(
                        $notificationList,
                        new Notification(
                            $notification->Name,
                            $notification->Institution_ID,
                            $notification->Title,
                            $notification->Content,
                            $notification->Sender_User_ID
                        )
                    );
                }
            }

            DatabaseManager::Disconnect();

            return $notificationList;
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            die();
        }
    }

    private static $insertIntoNotificationTypesStatement = "
        INSERT INTO notification_types (Name) VALUE (:name)
    ";

    private static $getNotificationTypeIDByNameStatement = "
        SELECT ID FROM notification_types WHERE Name = :name
    ";

}
?>


