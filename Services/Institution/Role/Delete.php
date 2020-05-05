<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    require_once("./Utility/InstitutionActions.php");
    require_once("./Utility/InstitutionRoles.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email              = $_POST['email'];
    $hashedPassword     = $_POST['hashedPassword'];
    $institutionName    = $_POST['institutionName'];
    $roleName           = $_POST['roleName'];

    if( $email           == null ||
        $hashedPassword  == null ||
        $institutionName == null ||
        $roleName        == null
    ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
        /*
        $response = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");
        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
        */
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    try{
        if (InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::DELETE_ROLE) == false) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
            /*
            $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

            echo json_encode($failureResponseStatus), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
            */
        }
    }
    catch (InstitutionRolesInvalidAction $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
            ->send();
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    InstitutionRoles::deleteRole($roleName, $institutionName);

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();

    /*
    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response);
    http_response_code(StatusCodes::OK);
    */


?>
