<?php

    /*
    parameters example: 
    username=testuser1&hashedPassword=parola&nameOfPost=Titlu1&contentOfPost=Content1&linkOfPost=pnm1&tagsOfPost=a:2:{i:1;s:4:"News";i:2;s:9:"Reminders";}
    */

    require_once("../../HelperClasses/CommonEndPointLogic.php");
    require_once("../../HelperClasses/DatabaseManager.php");
    require_once("../../HelperClasses/ValidationHelper.php");
    require_once("../../HelperClasses/StatusCodes.php");
    require_once("./NewsfeedCreationFunctions.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $username = $_POST["username"];
    $hashedPassword = $_POST["hashedPassword"];
    $notificationName = $_POST["nameOfPost"];
    $notificationContent = $_POST["contentOfPost"];
    $notificationLink = $_POST["linkOfPost"];
    $notificationTags = $_POST["tagsOfPost"];
    $notificationTagsArray = json_decode($notificationTags);

    if($username == null
        || $hashedPassword == null
        || $notificationName == null
        || $notificationLink == null
        || $notificationTagsArray == null
    ){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

    $tagsIDsArray = NewsfeedCreation::validateNewsfeedPostAndGetTagsID($notificationName, $notificationTagsArray);

    NewsfeedCreation::CreatePostIntoDatabase($notificationName, $notificationLink, $notificationContent);

    NewsfeedCreation::AssociatePostWithTags($notificationName, $notificationTagsArray, $tagsIDsArray);

    $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($successResponseStatus);
    http_response_code(StatusCodes::OK);
    
?>