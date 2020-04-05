<?php

require_once("../../../HelperClasses/CommonEndPointLogic.php");
require_once("../../../HelperClasses/ValidationHelper.php");
require_once("../../../HelperClasses/SuccessStates.php");
require_once("../../../HelperClasses/DatabaseManager.php");
require_once("../../../HelperClasses/Institution/InstitutionRoles.php");

CommonEndPointLogic::ValidateHTTPPOSTRequest();

$username = $_POST["username"];
$hashedPassword = $_POST["hashedPassword"];
$institutionName = $_POST["institutionName"];
    
if ($username == null || $hashedPassword == null || $institutionName == null) {
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

    echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::BAD_REQUEST);
    die();
}   

$responseStatus = CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

$queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

$queryDeleteRoles = "DELETE FROM Institution_Roles Where Institution_ID = :institutionID;";
$queryDeleteMembers = "DELETE FROM Institution_Members Where Institution_ID = :institutionID;";
$queryDeleteAdresses = "DELETE FROM institution_addresses_list Where Institution_ID = :institutionID;";
$queryDeleteInstitution = "DELETE FROM Institutions Where ID = :institutionID;";


try {
    if( false == InstitutionRoles::isUserAuthorized($username, $institutionName, InstitutionActions::DELETE_INSTITUTION) ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

        http_response_code(StatusCodes::OK);
        echo json_encode($response), PHP_EOL;
        die();
    }
}
catch (InstitutionRolesInvalidAction $e) {
    $response = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");

    http_response_code(StatusCodes::OK);
    echo json_encode($response), PHP_EOL;
    die();
}

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

    DatabaseManager::Connect();
    
    $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteRoles);
    $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
    $deleteInstitution->execute();
    
    $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteMembers);
    $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
    $deleteInstitution->execute();

    $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteAdresses);
    $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
    $deleteInstitution->execute();

    $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteInstitution);
    $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
    $deleteInstitution->execute();
  
    
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
http_response_code(StatusCodes::OK);
?>
