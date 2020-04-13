<?php 

require_once ("../../../HelperClasses/DatabaseManager.php");
require_once ("../../../HelperClasses/CommonEndPointLogic.php");
require_once ("../../../HelperClasses/SuccessStates.php");
require_once ("../../../HelperClasses/StatusCodes.php");
require_once("../../../HelperClasses/Institution/InstitutionRoles.php");
require_once("../Create/InstitutionCreation.php");

CommonEndPointLogic::ValidateHTTPGETRequest();

$callerUsername = $_POST["Username"];
$callerPassword = $_POST["hashedPassword"];
$institutionName = $_POST["Name"];
$institutionNewName = $_POST["newName"];
$institutionNewAddress = json_decode ($_POST["newAddress"], true);


if ($callerPassword == null || $callerUsername == null)
{
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");
    echo json_encode($failureResponseStatus);
    die();
}

if ($institutionName == null)
{
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");
    echo json_encode($failureResponseStatus);
    die();
}

if ($institutionNewAddress == null || $institutionNewName == null)
{
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");
    echo json_encode($failureResponseStatus);
    die();
}

$successfullyConnectedToDB = DatabaseManager::Connect();

CommonEndPointLogic::ValidateUserCredentials($callerUsername, $callerPassword);

try
{
    if (InstitutionRoles::isUserAuthorized($callerUsername, $institutionName, InstitutionActions::MODIFY_INSTITUTION) == false)
    {
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCode::OK);
        die();
    }
}
catch(InstitutionRolesInvalidAction $exception)
{
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");

    echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCode::OK);
    die();
}


if ($institutionNewName != null)
{
    $sqlNameUpdate = "UPDATE institutions 
                    SET Name = :institutionNewName, DateTime_Modified = CURRENT_TIMESTAMP WHERE ID = :id";
    $sqlStatementToExecute = DatabaseManager::PrepareStatement($sqlNameUpdate);                    
    $sqlStatementToExecute->bindParam(":institutionNewName", $institutionNewName);
    $sqlStatementToExecute->bindParam(":id", $institutionID);     
    $sqlStatementToExecute->execute();
}

if ($institutionNewAddresses != null)
{
    $sqlDeleteAddress = "DELETE FROM institution_addresses_list WHERE Institution_ID = :institutionID";
    $mainAddressCount = 0;

    foreach ($institutionNewAddresses as $address)
    {
        if ($address['isMainAddress'] == 1)
        {
            $mainAddressCount++;
        }
        if (InstitutionCreation::checkAddressValidity($address) == false)
        {
            $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ADDRESS");
            echo json_encode($failureResponseStatus);

            http_response_code(StatusCodes::OK);
            die();
        }
    }

    if ($mainAddressCount > 1)
    {
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("ONLY_ONE_MAIN_ADDRESS");
        echo json_encode($failureResponseStatus);

        http_response_code(StatusCodes::OK);
        die();
    }

    $sqlStatementToExecute = DatabaseManager::PrepareStatement($sqlDeleteAddress);
    $sqlStatementToExecute->bindParam(":institutionID", $institutionID);
    $sqlStatementToExecute->execute();

    foreach ($institutionNewAddresses as $address)
    {
        $addressID = InstitutionCreation::insertAddressIntoDatabase($address); 
        InstitutionCreation::linkInstitutionWithAddress($institutionID, $addressID);
    }

}


DatabaseManager::Disconnect();

?>
