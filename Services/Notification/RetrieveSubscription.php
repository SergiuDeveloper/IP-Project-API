<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    require_once("./Utility/Notification.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email              = $_GET["email"];
    $hashedPassword     = $_GET["hashedPassword"];

    if ($email == null || $hashedPassword == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $queryUserId = "SELECT id FROM users WHERE email = :userEmail;";

    $queryGetNotifications = "SELECT n.ID, t.Name, n.Institution_ID, n.Title, n.Content, n.Sender_User_ID FROM notifications n JOIN notification_subscriptions s ON n.ID = s.Notification_ID
     JOIN notification_types t ON t.ID = n.Notification_Types_ID WHERE s.User_ID = :userId;";

    $queryGetMembers = "SELECT User_ID FROM notification_subscriptions WHERE Notification_ID = :notificationID;";

    $notifications = array();
    try {
        DatabaseManager::Connect();

        $getId = DatabaseManager::PrepareStatement($queryUserId);
        $getId->bindParam(":userEmail", $email);
        $getId->execute();

        $getIdRow = $getId->fetch(PDO::FETCH_ASSOC);

        $getNotifications = DatabaseManager::PrepareStatement($queryGetNotifications);
        $getNotifications->bindParam(":userId", $getIdRow["ID"]);
        $getNotifications->execute();

        while($getNotificationsRow = $getNotifications->fetch(PDO::FETCH_ASSOC)){
            $notification = new Notification(
                $getNotificationsRow["Name"],
                $getNotificationsRow["Institution_ID"]
            );
            $notification->setTitle($getNotificationsRow["Title"]);
            $notification->setContent($getNotificationsRow["Content"]);
            $notification->setSenderID($getNotificationsRow["Sender_User_ID"]);

            $getMembers = DatabaseManager::PrepareStatement($queryGetMembers);
            $getMembers->bindParam(":notificationID", $getNotificationsRow["ID"]);
            $getMembers->execute();

            $members = array();
            while($getMembersRow = $getMembers->fetch(PDO::FETCH_ASSOC)){
                array_push($members, $getMembersRow["User_ID"]);
            }
            $notification->setReceiverMembers($members);

            array_push($notifications,$notification);
        }        
        
        DatabaseManager::Disconnect();
    }
    catch (Exception $databaseException) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
    }

    try {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->addResponseData("notifications", $notifications)
            ->send();
    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }
