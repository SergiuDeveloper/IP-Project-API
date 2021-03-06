<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email          = $_POST["email"];
    $hashedPassword = $_POST["hashedPassword"];
    $firstName      = $_POST["firstName"];
    $lastName       = $_POST["lastName"];

    if ($hashedPassword == null || $email == null ||  $firstName == null || $lastName == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
            ->send(StatusCodes::BAD_REQUEST);
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDETIAL");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
        */
    }

    /**
     * @deprecated
     * Update 09/04/2020
     * motiv : Username a disparut
     *
     * caz particular : username cu caractere ilegle
     * adaugat la 6/4/2020
     * motiv : la adaugarea unui user in institutie, se verifica existenta unui '@' in input pentru a diferentia intre adaugare prin eMail sau prin username
     */

    /*
    $usernameIllegalCharacters = "@\\/,'\"?><:;[]{}()=+`~!#\$%^&*\033 \t\n\r";

    $illegalCharacters = str_split($usernameIllegalCharacters);

    foreach($illegalCharacters as $character){
        if(strstr($username, $character)){
            $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("USERNAME_CONTAINS_ILLEGAL_CHAR");

            DatabaseManager::Disconnect();

            echo json_encode($failureResponseStatus), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
        }
    }

    */

    DatabaseManager::Connect();

    /**
     * @deprecated
     * Date : 09/04/2020
     * Reason : Username is gone
    */
    /*
    $getUsernameStatement = DatabaseManager::PrepareStatement('SELECT * from Users where username = :username');
    $getUsernameStatement->bindParam(":username", $username);
    $getUsernameStatement->execute();
    $userRow = $getUsernameStatement->fetch();

    if($userRow != null) {
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("USERNAME_ALREADY_EXISTS");

        DatabaseManager::Disconnect();

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }
    */

    $getEmailStatement = DatabaseManager::PrepareStatement("SELECT * from Users where email = :email");
    $getEmailStatement->bindParam(":email", $email);
    $getEmailStatement->execute();
    $userRow = $getEmailStatement->fetch();

    if($userRow != null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("EMAIL_ALREADY_EXISTS"))
            ->send();
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("EMAIL_ALREADY_EXISTS");

        DatabaseManager::Disconnect();

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    $hashedPassword = password_hash($hashedPassword, PASSWORD_BCRYPT);

    $insertUserStatement = DatabaseManager::PrepareStatement("
        INSERT INTO Users
            (hashed_password, email, first_name, last_name, is_active, datetime_created, datetime_modified) 
        VALUES 
            (:hashedPassword, :email, :firstName, :lastName, false, sysdate(), sysdate())
    ");

    $insertUserStatement->bindParam(":hashedPassword", $hashedPassword);
    $insertUserStatement->bindParam(":email", $email);
    $insertUserStatement->bindParam(":firstName", $firstName);
    $insertUserStatement->bindParam(":lastName", $lastName);
    $insertUserStatement->execute();

    $getIDStatement = DatabaseManager::PrepareStatement("SELECT ID from Users where Email = :email");
    $getIDStatement->bindParam(":email", $email);
    $getIDStatement->execute();
    $userRow = $getIDStatement->fetch(PDO::FETCH_OBJ);

    if($userRow == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INSERT_FAILURE"))
            ->send();
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INSERT_FAILURE");

        DatabaseManager::Disconnect();

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    $userID = $userRow->ID;
    $userActivationKey = hash("md5", $userID, false);

    try{
        $insertKeyStatement = DatabaseManager::PrepareStatement("INSERT into user_activation_keys (user_id, unique_key, datetime_created, datetime_used) values
        (:id, :unique_key, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP())");
        $insertKeyStatement->bindParam(":id", $userID);
        $insertKeyStatement->bindParam(":unique_key", $userActivationKey);
        $insertKeyStatement->execute();
    }
    catch(Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INSERT_FAILURE"))
            ->send();
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("KEY_INSERT_FAILURE");

        DatabaseManager::Disconnect();

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    try {
        CommonEndPointLogic::SendEmail($email, "Fiscal Documents EDI Activation Key", $userActivationKey);
    }
    catch (Exception $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("EMAIL_FAILURE"))
            ->send();
    }

DatabaseManager::Disconnect();

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();

    /*
    $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($successResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::OK);
    */
