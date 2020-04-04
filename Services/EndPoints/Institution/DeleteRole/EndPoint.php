<?php

    require_once ("../../../HelperClasses/DatabaseManager.php");
    require_once ("../../../HelperClasses/CommonEndPointLogic.php");
    require_once ("../../../HelperClasses/StatusCodes.php");
    require_once ("../../../HelperClasses/ValidationHelper.php");
    require_once ("../../../HelperClasses/Institution/InstitutionActions.php");
    require_once ("../../../HelperClasses/Institution/InstitutionRoles.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $username           = $_POST['username'];
    $hashedPassword     = $_POST['hashedPassword'];
    $institutionName    = $_POST['institutionName'];
    $roleName           = $_POST['roleName'];

    if( $username           == null
        || $hashedPassword  == null
        || $institutionName == null
        || $roleName        == null
    ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");
        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

    try{
        if (InstitutionRoles::isUserAuthorized($username, $institutionName, InstitutionActions::DELETE_ROLE) == false) {
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

    InstitutionRoles::deleteRole($roleName, $institutionName);

    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response);
    http_response_code(StatusCodes::OK);

?>