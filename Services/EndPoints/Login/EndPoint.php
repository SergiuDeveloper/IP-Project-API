<?php

require_once("../../HelperClasses/CommonEndPointLogic.php");
require_once("../../HelperClasses/ValidationHelper.php");
require_once("../../HelperClasses/StatusCodes.php");
require_once("../../HelperClasses/SuccessStates.php");

CommonEndPointLogic::ValidateHTTPPOSTRequest();

$username = $_POST["username"];
$hashedPassword = $_POST["hashedPassword"];
    
if ($username == null || $hashedPassword == null) {
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL");

    echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::BAD_REQUEST);
    die();
}   

$responseStatus = CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

echo json_encode($responseStatus), PHP_EOL;
http_response_code(StatusCodes::OK);

?>