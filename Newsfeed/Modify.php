<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once("./Utility/NewsfeedCreationFunctions.php");
    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/DatabaseManager.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email                  = $_POST["email"];
    $hashedPassword         = $_POST["hashedPassword"];
    $newsfeedPostTitle      = $_POST["newsfeedPostTitle"];
    $newsfeedPostNewTitle   = $_POST["newsfeedPostNewTitle"];
    $newsfeedPostContent    = $_POST["newsfeedPostContent"];
    $newsfeedPostURL        = $_POST["newsfeedPostURL"];
    $newsfeedPostTagsJSON   = $_POST["newsfeedPostTags"];

    $newsfeedPostTags = json_decode($newsfeedPostTagsJSON);

    if ($email == null || $hashedPassword == null) {
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateAdministrator($email, $hashedPassword);

    DatabaseManager::Connect();

    $getPostStatement = DatabaseManager::PrepareStatement("SELECT * FROM Newsfeed_Posts WHERE Title = :newsfeedPostTitle");
    $getPostStatement->bindParam(":newsfeedPostTitle", $newsfeedPostTitle);
    $getPostStatement->execute();

    $newsfeedPostRow = $getPostStatement->fetch(PDO::FETCH_OBJ);
    if ($newsfeedPostRow == null) {
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("POST_NOT_FOUND");

        DatabaseManager::Disconnect();

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    $newsfeedPostID = $newsfeedPostRow->ID;

    if ($newsfeedPostNewTitle != null || $newsfeedPostContent != null | $newsfeedPostURL != null) {
        $firstModification = true;

        $modifyPostEntryQuery = "UPDATE Newsfeed_Posts SET";
        if ($newsfeedPostNewTitle != null) {
            $modifyPostEntryQuery = sprintf("%s Title = :newsfeedPostNewTitle", $modifyPostEntryQuery);
            $firstModification = false;
        }
        if ($newsfeedPostContent != null) {
            $modifyPostEntryQuery = sprintf("%s%s Content = :newsfeedPostContent", $modifyPostEntryQuery, $firstModification ? "" : ",");
            $firstModification = false;
        }
        if ($newsfeedPostURL != null)
            $modifyPostEntryQuery = sprintf("%s%s URL = :newsfeedPostURL", $modifyPostEntryQuery, $firstModification ? "" : ",");
        $modifyPostEntryQuery = sprintf("%s WHERE ID = :newsfeedPostID", $modifyPostEntryQuery);

        $modifyPostStatement = DatabaseManager::PrepareStatement($modifyPostEntryQuery);
        if ($newsfeedPostNewTitle != null)
            $modifyPostStatement->bindParam(":newsfeedPostNewTitle", $newsfeedPostNewTitle);
        if ($newsfeedPostContent != null)
            $modifyPostStatement->bindParam(":newsfeedPostContent", $newsfeedPostContent);
        if ($newsfeedPostURL != null)
            $modifyPostStatement->bindParam(":newsfeedPostURL", $newsfeedPostURL);
        $modifyPostStatement->bindParam(":newsfeedPostID", $newsfeedPostID);
        $modifyPostStatement->execute();
    }

    if ($newsfeedPostTags == null) {
        $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

        DatabaseManager::Disconnect();

        echo json_encode($successResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    DatabaseManager::Disconnect();

    NewsfeedTagLinker::RemoveAllTagsForPost($newsfeedPostID);

    $newsfeedPostTagsID = NewsfeedTagLinker::createMissingTagsAndgetPostTagsID($newsfeedPostTags);

    NewsfeedTagLinker::AssociatePostWithTags($newsfeedPostID, $newsfeedPostTags, $newsfeedPostTagsID);

    $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($successResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::OK);

