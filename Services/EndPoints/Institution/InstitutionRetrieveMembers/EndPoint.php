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

$queryInstitutionMember = "SELECT * FROM Institution_Members i JOIN Users u ON i.User_ID = u.ID
 WHERE u.username = :callerUsername AND i.Institution_ID = :institutionID;";

$queryGetMembers = "SELECT u.username, r.title FROM Institution_Members i JOIN Users u ON i.User_ID = u.ID
JOIN Institution_Roles r ON i.Institution_Roles_ID = r.ID
WHERE i.Institution_ID = :institutionID;";

$institutionMembers = array();
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

    $getCallerUser = DatabaseManager::PrepareStatement($queryInstitutionMember);
    $getCallerUser->bindParam(":callerUsername", $username);
    $getCallerUser->bindParam(":institutionID", $institutionRow["ID"]);
    $getCallerUser->execute();

    $callerUserRow = $getCallerUser->fetch(PDO::FETCH_OBJ);

    if($callerUserRow == null){
        DatabaseManager::Disconnect();
        $response = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

        http_response_code(StatusCodes::OK);
        echo json_encode($response), PHP_EOL;
        die();
    }


    $getMembers = DatabaseManager::PrepareStatement($queryGetMembers);
    $getMembers->bindParam(":institutionID", $institutionRow["ID"]);
    $getMembers->execute();

    while($getMembersRow = $getMembers->fetch(PDO::FETCH_ASSOC)){
        $member=new Member($getMembersRow["username"],$getMembersRow["title"]);

        array_push($institutionMembers,$member);
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
echo json_encode($institutionMembers), PHP_EOL;
http_response_code(StatusCodes::OK);


class Member {
        public $username;
        public $role;
        function __construct($username, $role){
            $this->username = $username;
            $this->role = $role;
        }
        function get_username() {
          return $this->username;
        }
        function get_role() {
          return $this->role;
        }
}
?>
