<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/Utilities.php");

    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
    require_once(ROOT . "/Institution/Utility/InstitutionCreation.php");
    require_once(ROOT . "/Institution/Utility/InstitutionValidator.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $callerEmail                    = $_POST["email"];
    $callerPassword                 = $_POST["hashedPassword"];
    $institutionName                = $_POST["institutionName"];
    $institutionNewName             = $_POST["newInstitutionName"];
    $institutionNewAddressesJSON    = $_POST["newInstitutionAddresses"];

    $institutionNewAddresses = json_decode($institutionNewAddressesJSON, true);

    $apiKey = $_POST["apiKey"];

    if($apiKey != null){
        try {
            $credentials = APIKeyHandler::getInstance()->setAPIKey($apiKey)->getCredentials();
        } catch (APIKeyHandlerKeyUnbound $e) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNBOUND_KEY"))
                ->send(StatusCodes::INTERNAL_SERVER_ERROR);
        } catch (APIKeyHandlerAPIKeyInvalid $e) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_KEY"))
                ->send();
        }

        $email = $credentials->getEmail();
        //$hashedPassword = $credentials->getHashedPassword();
    } else {
        if ($email == null || $hashedPassword == null) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
                ->send(StatusCodes::BAD_REQUEST);
        }
        CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);
    }

    if($institutionName == null || $institutionNewAddresses == null){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    $successfullyConnectedToDB = DatabaseManager::Connect();

    try
    {
        if (InstitutionRoles::isUserAuthorized($callerEmail, $institutionName, InstitutionActions::MODIFY_INSTITUTION) == false)
        {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
            /*
            $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

            echo json_encode($failureResponseStatus), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
            */
        }
    }
    catch(InstitutionRolesInvalidAction $exception)
    {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
            ->send();
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
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
                    ResponseHandler::getInstance()
                        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ADDRESS"))
                        ->send();
                    /*
                    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ADDRESS");
                    echo json_encode($failureResponseStatus);

                    http_response_code(StatusCodes::OK);
                    die();
                    */
                }
            }

            if ($mainAddressCount != 1) {
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ONLY_ONE_MAIN_ADDRESS"))
                    ->send();
                /*
                $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("ONLY_ONE_MAIN_ADDRESS");
                echo json_encode($failureResponseStatus);

                http_response_code(StatusCodes::OK);
                die();
                */
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
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
        /*
        $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    DatabaseManager::Disconnect();

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();
    /*
    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();
    */
?>
