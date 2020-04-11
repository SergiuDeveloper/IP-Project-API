<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");

    require_once("./Utility/InstitutionActions.php");
    require_once("./Utility/InstitutionRoles.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email              = $_POST['email'];
    $hashedPassword     = $_POST['hashedPassword'];
    $institutionName    = $_POST['institutionName'];
    $roleName           = $_POST['roleName'];
    $newRoleName        = $_POST['newRoleName'];
    $newRoleRights      = json_decode($_POST['newRoleRights'], true);

    if( $email           == null ||
        $hashedPassword  == null ||
        $institutionName == null ||
        $roleName        == null ||
        $newRoleRights   == null
    ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");
        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    try{
        if (InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::MODIFY_ROLE) == false) {
            $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

            echo json_encode($failureResponseStatus), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
        }
    }
    catch (InstitutionRolesInvalidAction $exception){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    InstitutionRoles::updateRole($roleName, $institutionName, $newRoleName, $newRoleRights);

    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response);
    http_response_code(StatusCodes::OK);

?>