<?php

require_once("../../../HelperClasses/CommonEndPointLogic.php");
require_once("../../../HelperClasses/ValidationHelper.php");
require_once("../../../HelperClasses/SuccessStates.php");
require_once("../../../HelperClasses/DatabaseManager.php");

CommonEndPointLogic::ValidateHTTPGETRequest();

$username = $_GET["username"];
$hashedPassword = $_GET["hashedPassword"];
$institutionName = $_GET["institutionName"];
    
if ($username == null || $hashedPassword == null || $institutionName == null) {
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

    echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::BAD_REQUEST);
    die();
}   

$responseStatus = CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

$queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

$queryGetRoles = "SELECT DISTINCT title FROM Institution_Roles
WHERE Institution_ID = :institutionID;";

$institutionRoles = array();
try {
    DatabaseManager::Connect();

    $getInstitution = DatabaseManager::PrepareStatement($queryIdInstitution);
    $getInstitution->bindParam(":institutionName", $institutionName);
    $getInstitution->execute();

    $institutionRow = $getInstitution->fetch(PDO::FETCH_ASSOC);

    if($institutionRow == null){
        DatabaseManager::Disconnect();
        $response = CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND");

        http_response_code(StatusCodes::OK);
        echo json_encode($response), PHP_EOL;
        die();
    }

    $Can_Assign_Roles = InstitutionRoles::isUserAuthorized($username, $institutionName, InstitutionActions::ASSIGN_ROLE);

    if($Can_Assign_Roles != 'Can_Assign_Roles'){
        DatabaseManager::Disconnect();
        $response = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

        http_response_code(StatusCodes::OK);
        echo json_encode($response), PHP_EOL;
        die();
    }


    $getRoles = DatabaseManager::PrepareStatement($queryGetRoles);
    $getRoles->bindParam(":institutionID", $institutionRow["ID"]);
    $getRoles->execute();

    while($getRolesRow = $getRoles->fetch(PDO::FETCH_ASSOC)){
        array_push($institutionRoles,$getRolesRow["title"]);
    }
    DatabaseManager::Disconnect();
}
catch (Exception $databaseException) {
    $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");
    echo json_encode($response), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();
}

$responseSuccess = CommonEndPointLogic::GetSuccessResponseStatus();
echo json_encode($responseSuccess), PHP_EOL;
echo json_encode($institutionRoles), PHP_EOL;
http_response_code(StatusCodes::OK);

?>
