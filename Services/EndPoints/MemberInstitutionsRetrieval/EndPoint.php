<?php

    require_once ("../../HelperClasses/DatabaseManager.php");
    require_once ("../../HelperClasses/StatusCodes.php");
    require_once ("../../HelperClasses/ValidationHelper.php");
    require_once ("../../HelperClasses/CommonEndPointLogic.php");

    CommonEndPointLogic::ValidateHTTPGetRequest();

    $username = $_GET["username"];
    $hashedPassword = $_GET["hashedPassword"];

    if(
        $username       == null ||
        $hashedPassword == null
    ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

    $getInstitutionsAndRolesForUsernameStatement = "
        SELECT Name, Title FROM institution_members 
            JOIN users ON User_ID = users.ID 
            JOIN institution_roles ON institution_members.Institution_Roles_ID = institution_ROles.ID 
            JOIN institutions ON institutions.id = institution_members.Institution_ID 
            WHERE Username = :username
    ";

    $institutionRolesArray = array();

    try{
        DatabaseManager::Connect();

        $SQLStatement = DatabaseManager::PrepareStatement($getInstitutionsAndRolesForUsernameStatement);
        $SQLStatement->bindParam(":username", $username);

        $SQLStatement->execute();

        while($row = $SQLStatement->fetch(PDO::FETCH_OBJ)){
            $institutionRole = new InstitutionRole($row->Name, $row->Title);

            array_push($institutionRolesArray, $institutionRole);
        }

        DatabaseManager::Disconnect();
    }
    catch(Exception $exception){
        $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    $response = CommonEndPointLogic::GetSuccessResponseStatus();
    echo json_encode($response),PHP_EOL;
    echo json_encode($institutionRolesArray), PHP_EOL;
    http_response_code(StatusCodes::OK);

    class InstitutionRole {
        public $institutionName;
        public $roleName;

        function __construct($institutionName, $roleName)
        {
                $this->institutionName = $institutionName;
                $this->roleName = $roleName;
        }

        function getInstitutionName(){
            return $this->institutionName;
        }

        public function getRoleName()
        {
            return $this->roleName;
        }
    }

?>