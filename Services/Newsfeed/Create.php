<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Newsfeed/Utility/NewsfeedCreationFunctions.php");
    require_once(ROOT . "/Utility/Utilities.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email                  = $_POST["email"];
    $hashedPassword         = $_POST["hashedPassword"];
    $notificationName       = $_POST["nameOfPost"];
    $notificationContent    = $_POST["contentOfPost"];
    $notificationLink       = $_POST["linkOfPost"];
    $notificationTags       = $_POST["tagsOfPost"];
    $notificationTagsArray  = json_decode($notificationTags);

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

    if(
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

    //CommonEndPointLogic::ValidateAdministrator($email, $hashedPassword);

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


?>
