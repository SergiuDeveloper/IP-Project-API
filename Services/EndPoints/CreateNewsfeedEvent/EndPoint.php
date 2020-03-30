<?php

    require_once("../../HelperClasses/CommonEndPointLogic.php");
    require_once("../../HelperClasses/DatabaseManager.php");
    require_once("../../HelperClasses/ValidationHelper.php");
    require_once("../../HelperClasses/StatusCodes.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $username = $_POST["username"];
    $hashedPassword = $_POST["hashedPassword"];
    $notificationName = $_POST["nameOfPost"];
    $notificationContent = $_POST["contentOfPost"];
    $notificationLink = $_POST["linkOfPost"];
    $notificationTags = $_POST["tagsOfPost"];
    $notificationTagsArray = unserialize(base64_decode($notificationTags));

    if($username == null
        || $hashedPassword == null
        || $notificationName == null
        || $notificationLink == null
        || $notificationTags == null
    ){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

    $tagsIDsArray = NewsfeedCreation::validateNewsfeedPostAndGetTagsID($notificationName, $notificationTagsArray);

    $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($successResponseStatus);
    http_response_code(StatusCodes::OK);
    
?>