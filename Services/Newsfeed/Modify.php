<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "Newsfeed/Utility/NewsfeedCreationFunctions.php");
    require_once(ROOT . "/Utility/Utilities.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email                  = $_POST["email"];
    $hashedPassword         = $_POST["hashedPassword"];
    $newsfeedPostTitle      = $_POST["newsfeedPostTitle"];
    $newsfeedPostNewTitle   = $_POST["newsfeedPostNewTitle"];
    $newsfeedPostContent    = $_POST["newsfeedPostContent"];
    $newsfeedPostURL        = $_POST["newsfeedPostURL"];
    $newsfeedPostTagsJSON   = $_POST["newsfeedPostTags"];

    $newsfeedPostTags = json_decode($newsfeedPostTagsJSON);

    $apiKey = $_POST["apiKey"];

    if($apiKey != null){
        try {
            $credentials = APIKeyHandler::getInstance()->setAPIKey($apiKey)->getCredentials();
        } catch (APIKeyHandlerKeyUnbound $e) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNBOUND_KEY"))
                ->send(StatusCodes::INTERNAL_SERVER_ERROR);
        } catch (APIKeyHandlerAPIKeyInvalid $e) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_KEY"))
                ->send();
        }

        $email = $credentials->getEmail();
        //$hashedPassword = $credentials->getHashedPassword();

        try{
            DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement("
                    SELECT administrators.ID FROM administrators JOIN users ON administrators.Users_ID = users.ID WHERE Email = :email
                ");
            $statement->bindParam(":email", $email);
            $statement->execute();

            if($statement->rowCount() == 0) {
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NOT_ADMIN"))
                    ->send();
            }

            DatabaseManager::Disconnect();
        } catch (PDOException $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }

    } else {
        if ($email == null || $hashedPassword == null) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
                ->send(StatusCodes::BAD_REQUEST);
        }
        CommonEndPointLogic::ValidateAdministrator($email, $hashedPassword);
    }

    //CommonEndPointLogic::ValidateAdministrator($email, $hashedPassword);

    DatabaseManager::Connect();

    $getPostStatement = DatabaseManager::PrepareStatement("SELECT * FROM Newsfeed_Posts WHERE Title = :newsfeedPostTitle");
    $getPostStatement->bindParam(":newsfeedPostTitle", $newsfeedPostTitle);
    $getPostStatement->execute();

    $newsfeedPostRow = $getPostStatement->fetch(PDO::FETCH_OBJ);
    if ($newsfeedPostRow == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("POST_NOT_FOUND"))
            ->send();
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("POST_NOT_FOUND");

        DatabaseManager::Disconnect();

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
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
        DatabaseManager::Disconnect();

        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->send();
        /*
        $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();


        echo json_encode($successResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();*/
    }

    DatabaseManager::Disconnect();

    NewsfeedTagLinker::RemoveAllTagsForPost($newsfeedPostID);

    $newsfeedPostTagsID = NewsfeedTagLinker::createMissingTagsAndgetPostTagsID($newsfeedPostTags);

    NewsfeedTagLinker::AssociatePostWithTags($newsfeedPostID, $newsfeedPostTags, $newsfeedPostTagsID);


    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();

    /*
    $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($successResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::OK);
    */

?>

