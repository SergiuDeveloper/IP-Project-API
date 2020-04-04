<?php

require_once("../../../HelperClasses/CommonEndPointLogic.php");
require_once("../../../HelperClasses/ValidationHelper.php");
require_once("../../../HelperClasses/SuccessStates.php");
require_once("../../../HelperClasses/DatabaseManager.php");

CommonEndPointLogic::ValidateHTTPPOSTRequest();

$username = $_POST["username"];
$hashedPassword = $_POST["hashedPassword"];
$institutionName = $_POST["institutionName"];
$memberUsername= $_POST["memberUsername"];

    
if ($username == null || $hashedPassword == null || $institutionName == null || $memberUsername == null) {
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

    echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::BAD_REQUEST);
    die();
}   

$responseStatus = CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

$queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

$queryCallerRight = "SELECT ri.Can_Remove_Members FROM Institution_Members i JOIN Users u ON i.User_ID = u.ID
JOIN Institution_Roles r ON i.Institution_Roles_ID = r.ID
JOIN institution_rights ri ON r.institution_rights_id = ri.ID
WHERE i.Institution_ID = :institutionID AND u.username = :callerUsername;";


$queryInstitutionMember = "SELECT u.ID FROM Institution_Members i JOIN Users u ON i.User_ID = u.ID
WHERE u.username = :username AND i.Institution_ID = :institutionID;";

$queryDeleteMember = "DELETE FROM Institution_Members WHERE user_id = :userID AND Institution_ID = :institutionID;";

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

    $getCaller = DatabaseManager::PrepareStatement($queryCallerRight);
    $getCaller->bindParam(":callerUsername", $username);
    $getCaller->bindParam(":institutionID", $institutionRow["ID"]);
    $getCaller->execute();

    $callerRow = $getCaller->fetch(PDO::FETCH_ASSOC);

    if($callerRow == null || $callerRow["Can_Remove_Members"] != 1){
        DatabaseManager::Disconnect();
        $response = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

        http_response_code(StatusCodes::OK);
        echo json_encode($response), PHP_EOL;
        die();
    }

    $getMember = DatabaseManager::PrepareStatement($queryInstitutionMember);
    $getMember->bindParam(":username", $memberUsername);
    $getMember->bindParam(":institutionID", $institutionRow["ID"]);
    $getMember->execute();

    $memberRow = $getMember->fetch(PDO::FETCH_ASSOC);

    if($memberRow == null){
        DatabaseManager::Disconnect();
        $response = CommonEndPointLogic::GetFailureResponseStatus("MEMBER_NOT_FOUND");

        http_response_code(StatusCodes::OK);
        echo json_encode($response), PHP_EOL;
        die();
    }

    $deleteMembers = DatabaseManager::PrepareStatement($queryDeleteMember);
    $deleteMembers->bindParam(":userID", $memberRow["ID"]);
    $deleteMembers->bindParam(":institutionID", $institutionRow["ID"]);
    $deleteMembers->execute();

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
