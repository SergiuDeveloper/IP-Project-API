<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");

    $email = $_POST["email"];
    $hashedPassword = $_POST["hashedPassword"];
    $institutionName = $_POST["institutionName"];
    $userIdentifier = $_POST["userIdentifier"];
    $roleName = $_POST["roleName"];

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    if ($email == null || $hashedPassword == null || $institutionName == null || $userIdentifier == null || $roleName == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
        /*
        $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($failureResponse), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
        */
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    try {
        $userCanAddMembers = InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::ADD_MEMBERS);
    }
    catch (InstitutionRolesInvalidAction $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
            ->send();
        /*
        $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");

        echo json_encode($failureResponse), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    if (!$userCanAddMembers) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
            ->send();
        /*
        $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("NOT_ENOUGH_RIGHTS");

        echo json_encode($failureResponse), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    /**
     * Date : 10/04/2020
     * @deprecated
     * Reduntant. Username not a thing anymore
     */

    /*
    $isUserIdentifierEmail = (strpos($userIdentifier, "@") !== false);


    if ($isUserIdentifierEmail) {


    try {
        DatabaseManager::Connect();

        $getUsernameStatement = DatabaseManager::PrepareStatement("SELECT Email FROM Users WHERE Email = :email");
        $getUsernameStatement->bindParam(":email", $userIdentifier);
        $getUsernameStatement->execute();
        $userRow = $getUsernameStatement->fetch(PDO::FETCH_OBJ);

        if ($userRow == null) {
            $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("USER_NOT_FOUND");

            echo json_encode($failureResponse), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
        }

        $userIdentifier = $userRow->Username;

        DatabaseManager::Disconnect();
    }
    catch(Exception $exception){
        $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

        echo json_encode($failureResponse), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

     * }
     */

    InstitutionRoles::addAndAssignMemberToInstitution($userIdentifier, $institutionName, $roleName);

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();

    /*
    $successResponse = CommonEndPointLogic::GetSuccessResponseStatus();
    echo json_encode($successResponse);
    http_response_code(StatusCodes::OK);
    */

?>