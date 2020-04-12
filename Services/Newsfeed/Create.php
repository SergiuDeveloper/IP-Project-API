<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once("./Utility/NewsfeedCreationFunctions.php");
    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email                  = $_POST["email"];
    $hashedPassword         = $_POST["hashedPassword"];
    $notificationName       = $_POST["nameOfPost"];
    $notificationContent    = $_POST["contentOfPost"];
    $notificationLink       = $_POST["linkOfPost"];
    $notificationTags       = $_POST["tagsOfPost"];
    $notificationTagsArray  = json_decode($notificationTags);

    if(
        $email                  == null ||
        $hashedPassword         == null ||
        $notificationName       == null ||
        $notificationLink       == null ||
        $notificationTagsArray  == null
    ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
        */
    }

    CommonEndPointLogic::ValidateAdministrator($email, $hashedPassword);

    $tagsIDsArray = NewsfeedCreation::validateNewsfeedPostAndGetTagsID($notificationName, $notificationTagsArray);

    NewsfeedCreation::CreatePostIntoDatabase($notificationName, $notificationLink, $notificationContent);

    NewsfeedCreation::AssociatePostWithTags($notificationName, $notificationTagsArray, $tagsIDsArray);

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();
    /*
    $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($successResponseStatus);
    http_response_code(StatusCodes::OK);
    */
