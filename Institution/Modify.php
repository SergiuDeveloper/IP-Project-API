<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");

    require_once("Role/Utility/InstitutionRoles.php");
    require_once("Utility/InstitutionCreation.php");
    require_once("Utility/InstitutionValidator.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $callerEmail                    = $_POST["email"];
    $callerPassword                 = $_POST["hashedPassword"];
    $institutionName                = $_POST["institutionName"];
    $institutionNewName             = $_POST["newInstitutionName"];
    $institutionNewAddressesJSON    = $_POST["newInstitutionAddresses"];

    $institutionNewAddresses = json_decode($institutionNewAddressesJSON, true);

    if($callerEmail == null || $callerPassword == null || $institutionName == null || $institutionNewAddresses == null){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    $successfullyConnectedToDB = DatabaseManager::Connect();

    CommonEndPointLogic::ValidateUserCredentials($callerEmail, $callerPassword);

    try
    {
        if (InstitutionRoles::isUserAuthorized($callerEmail, $institutionName, InstitutionActions::MODIFY_INSTITUTION) == false)
        {
            $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

            echo json_encode($failureResponseStatus), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
        }
    }
    catch(InstitutionRolesInvalidAction $exception)
    {
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

    try {
        DatabaseManager::Connect();

        if ($institutionNewName != null) {
            $sqlNameUpdate = "UPDATE institutions 
                            SET Name = :institutionNewName, DateTime_Modified = CURRENT_TIMESTAMP WHERE ID = :id";
            $sqlStatementToExecute = DatabaseManager::PrepareStatement($sqlNameUpdate);
            $sqlStatementToExecute->bindParam(":institutionNewName", $institutionNewName);
            $sqlStatementToExecute->bindParam(":id", $institutionID);
            $sqlStatementToExecute->execute();
        }

        if ($institutionNewAddresses != null) {
            $sqlDeleteAddress = "DELETE FROM institution_addresses_list WHERE Institution_ID = :institutionID";
            $mainAddressCount = 0;

            foreach ($institutionNewAddresses as $address) {
                if ($address['isMainAddress'] == 1) {
                    $mainAddressCount++;
                }
                if (InstitutionCreation::checkAddressValidity($address) == false) {
                    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ADDRESS");
                    echo json_encode($failureResponseStatus);

                    http_response_code(StatusCodes::OK);
                    die();
                }
            }

            if ($mainAddressCount != 1) {
                $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("ONLY_ONE_MAIN_ADDRESS");
                echo json_encode($failureResponseStatus);

                http_response_code(StatusCodes::OK);
                die();
            }

            $sqlStatementToExecute = DatabaseManager::PrepareStatement($sqlDeleteAddress);
            $sqlStatementToExecute->bindParam(":institutionID", $institutionID);
            $sqlStatementToExecute->execute();

            foreach ($institutionNewAddresses as $address) {
                $addressID = InstitutionCreation::insertAddressIntoDatabase($address);
                InstitutionCreation::linkInstitutionWithAddress($institutionID, $addressID, $address['isMainAddress']);
            }

        }
    }
    catch (Exception $exception){
        $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    DatabaseManager::Disconnect();

    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();

