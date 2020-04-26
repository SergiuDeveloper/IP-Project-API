<?php
    if (!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }
    
    require_once(ROOT . '/Utility/CommonEndPointLogic.php');
    require_once(ROOT . '/Utility/ResponseHandler.php');
    require_once(ROOT . '/Institution/Role/Utility/InstitutionActions.php');
    require_once(ROOT . '/Institution/Role/Utility/InstitutionRoles.php');
    require_once(ROOT . '/Document/Utility/Invoice.php');

    $email                  = $_POST['email'];
    $hashedPassword         = $_POST['hashedPassword'];
    $creatorUserEmail       = $_POST['creatorUserID'];
    $institutionName        = $_POST['institutionName'];
    $institutionAddressID   = $_POST['institutionAddress']; 

    CommonEndPointLogic::ValidateHTTPPostRequest();

    if ($email == null || $hashedPassword == null || $institutionName == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus('NULL_INPUT'))
            ->send(StatusCodes::BAD_REQUEST);
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    if (!InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::UPLOAD_DOCUMENTS)) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus('UNAUTHORIZED_ACTION'))
            ->send();
    }

    DatabaseManager::Connect();

    $getInstitutionIDStatement = DatabaseManager::PrepareStatement('SELECT ID FROM Institutions WHERE Name = :institutionName');
    $getInstitutionIDStatement->bindParam(':institutionName', $institutionName);
    $getInstitutionIDStatement->execute();

    $instiutionRows = $getInstitutionIDStatement->fetch(PDO::FETCH_ASSOC);
    if ($institutionRows === null) {
        DatabaseManager::Disconnect();

        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus('INVALID_INSTITUTION'))
            ->send();
    }

    $institutionID = $institutionRows->ID;

    if ($institutionAddressID !== null) {
        $getInstitutionIDFromAddressStatement = DatabaseManager::PrepareStatement('SELECT Institution_ID FROM Institution_Addresses_List WHERE Address_ID = :institutionAddressID');
        $getInstitutionIDFromAddressStatement->bindParam(':institutionAddressID', $institutionAddressID);
        $getInstitutionIDFromAddressStatement->execute();

        $institutionAddressesRows = $getInstitutionIDFromAddressStatement->fetch(PDO::FETCH_ASSOC);
        if ($institutionAddressesRows !== null) {
            $institutionIDFromAddress = $institutionAddressesRows->Institution_ID;

            if ($institutionIDFromAddress !== $institutionID) {
                DatabaseManager::Disconnect();

                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus('BAD_INSTITUTION_ADDRESS'))
                    ->send();
            }
        }
    }

    $creatorUserID = null;
    if ($creatorUserEmail !== null) {
        $getUserIDStatement = DatabaseManager::PrepareStatement('SELECT ID FROM Users WHERE Email = :creatorUserEmail');
        $getUserIDStatement->bindParam(':creatorUserEmail', $creatorUserEmail);
        $getUserIDStatement->execute();

        $userRows = $getUserIDStatement->fetch(PDO::FETCH_ASSOC);
        if ($userRows !== null)
            $creatorUserID = $userRows->ID;
    }

    DatabaseManager::Disconnect();

    $invoice = new Invoice();
?>