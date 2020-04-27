<?php
    if (!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }
    
    require_once(ROOT . '/Utility/CommonEndPointLogic.php');
    require_once(ROOT . '/Utility/ResponseHandler.php');
    require_once(ROOT . '/Institution/Role/Utility/InstitutionActions.php');
    require_once(ROOT . '/Institution/Role/Utility/InstitutionRoles.php');

    $email                  = $_GET['email'];
    $hashedPassword         = $_GET['hashedPassword'];
    $creatorUserEmail       = $_GET['creatorUserEmail'];
    $institutionName        = $_GET['institutionName'];
    $institutionAddressID   = $_GET['institutionAddress'];
    $documentItems          = json_decode($_GET['documentItems'], true); 

    CommonEndPointLogic::ValidateHTTPGetRequest();

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

    $getInstitutionIDStatement = DatabaseManager::PrepareStatement('
        SELECT ID FROM Institutions WHERE Name = :institutionName
    ');
    $getInstitutionIDStatement->bindParam(':institutionName', $institutionName);
    $getInstitutionIDStatement->execute();

    $institutionRows = $getInstitutionIDStatement->fetch(PDO::FETCH_ASSOC);
    if ($institutionRows === false) {
        DatabaseManager::Disconnect();
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus('INVALID_INSTITUTION'))
            ->send();
    }
    $institutionID = $institutionRows['ID'];

    if ($institutionAddressID !== null) {
        $getInstitutionIDFromAddressStatement = DatabaseManager::PrepareStatement('
            SELECT Institution_ID FROM Institution_Addresses_List WHERE Address_ID = :institutionAddressID
        ');
        $getInstitutionIDFromAddressStatement->bindParam(':institutionAddressID', $institutionAddressID);
        $getInstitutionIDFromAddressStatement->execute();

        $institutionAddressesRows = $getInstitutionIDFromAddressStatement->fetch(PDO::FETCH_ASSOC);
        if ($institutionAddressesRows !== false) {
            $institutionIDFromAddress = $institutionAddressesRows['Institution_ID'];

            if ($institutionIDFromAddress !== $institutionID) {
                DatabaseManager::Disconnect();

                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus('BAD_INSTITUTION_ADDRESS'))
                    ->send();
            }
        }
    }
    else {
        $getMainAddressIDStatement = DatabaseManager::PrepareStatement('
            SELECT ID FROM Institution_Addresses_List WHERE Institution_ID = :institutionID AND Is_Main_Address = TRUE;
        ');
        $getMainAddressIDStatement->bindParam(':institutionID', $institutionID);
        $getMainAddressIDStatement->execute();
        $addressRows = $getMainAddressIDStatement->fetch(PDO::FETCH_ASSOC);

        $institutionAddressID = $addressRows['ID'];
    }

    $creatorUserID = null;
    if ($creatorUserEmail !== null) {
        $getUserIDStatement = DatabaseManager::PrepareStatement('
            SELECT ID FROM Users WHERE Email = :creatorUserEmail
        ');
        $getUserIDStatement->bindParam(':creatorUserEmail', $creatorUserEmail);
        $getUserIDStatement->execute();

        $userRows = $getUserIDStatement->fetch(PDO::FETCH_ASSOC);
        if ($userRows !== false)
            $creatorUserID = $userRows['ID'];
    }

    $getDocumentTypeStatement = DatabaseManager::PrepareStatement('
        SELECT ID FROM Document_Types WHERE Title = \'Invoice\'
    ');
    $getDocumentTypeStatement->execute();
    $documentTypesRow = $getDocumentTypeStatement->fetch(PDO::FETCH_ASSOC);
    $documentTypeID = $documentTypesRow['ID'];

    $insertDocumentStatement = DatabaseManager::PrepareStatement('
        INSERT INTO Documents (
            Date_Created,
            Creator_User_ID,
            Sender_Institution_ID,
            Sender_Address_ID,
            Is_Sent,
            Document_Types_ID
        )
        VALUES (
            NOW(),
            :creatorUserID,
            :institutionID,
            :institutionAddressID,
            FALSE,
            :documentTypeID
        )
    ');
    $insertDocumentStatement->bindParam(':creatorUserID', $creatorUserID);
    $insertDocumentStatement->bindParam(':institutionID', $institutionID);
    $insertDocumentStatement->bindParam(':institutionAddressID', $institutionAddressID);
    $insertDocumentStatement->bindParam(':documentTypeID', $documentTypeID);
    $insertDocumentStatement->execute();

    $documentID = DatabaseManager::GetLastInsertID();
    echo $documentID;

    $insertInvoiceStatement = DatabaseManager::PrepareStatement('
        INSERT INTO Invoices (
            Documents_ID
        )
        VALUES (
            :documentID
        )
    ');
    $insertInvoiceStatement->bindParam(':documentID', $documentID);
    $insertInvoiceStatement->execute();

    $invoiceID = DatabaseManager::GetLastInsertID();

    if ($documentItems === null || count($documentItems) > 0) {
        DatabaseManager::Disconnect();
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->send();
    }

    foreach ($documentItems as $item) {
        $getCurrencyIDStatement = DatabaseManager::PrepareStatement('
            SELECT ID FROM Currencies WHERE LOWER(Title) = LOWER(:currencyTitle)
        ');
        $getCurrencyIDStatement->bindParam(':currencyTitle', $item['currencyTitle']);
        $getCurrencyIDStatement->execute();
        $currencyRow = $getCurrencyIDStatement->fetch(PDO::FETCH_ASSOC);
        if ($currencyRow === false) {
            $addCurrencyStatement = DatabaseManager::PrepareStatement('
                INSERT INTO Currencies (
                    Title
                )
                VALUES (
                    :currencyTitle
                )
            ');
            $addCurrencyStatement->bindParam(':currencyTitle', $item['currencyTitle']);
            $addCurrencyStatement->execute();

            $currencyID = DatabaseManager::GetLastInsertID();
        }
        else
            $curencyID = $currencyRow['ID'];

        $insertItemStatement = DatabaseManager::PrepareStatement('
            INSERT INTO Items (
                Product_Number,
                Title,
                Description,
                Value_Before_Tax,
                Tax_Percentage,
                Value_After_Tax,
                Currencies_ID
            )
            VALUES (
                :productNumber,
                :title,
                :description,
                :valueBeforeTax,
                :taxPercentage,
                :valueAfterTax,
                :curencyID
            )
        ');
        $insertItemStatement->bindParam(':productNumber', $item['productNumber']);
        $insertItemStatement->bindParam(':title', $item['title']);
        $insertItemStatement->bindParam(':description', $item['description']);
        $insertItemStatement->bindParam(':valueBeforeTax', $item['valueBeforeTax']);
        $insertItemStatement->bindParam(':taxPercentage', $item['taxPercentage']);
        $insertItemStatement->bindParam(':valueAfterTax', $item['valueAfterTax']);
        $insertItemStatement->bindParam(':curencyID', $currencyID);
        $insertItemStatement->execute();

        $itemID = DatabaseManager::GetLastInsertID();

        $insertDocumentItemStatement = DatabaseManager::PrepareStatement('
            INSERT INTO Document_Items (
                Invoices_ID,
                Items_ID,
                Quantity
            )
            VALUES (
                :invoiceID,
                :itemID,
                :quantity
            )
        ');
        $insertDocumentItemStatement->bindParam(':invoiceID', $invoiceID);
        $insertDocumentItemStatement->bindParam(':itemID', $itemID);
        $insertDocumentItemStatement->bindParam(':quantity', $item['quantity']);
        $insertDocumentItemStatement->execute();
    }

    DatabaseManager::Disconnect();
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();
?>