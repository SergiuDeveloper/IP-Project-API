<?php
    if (!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }
    
    require_once(ROOT . '/Utility/Utilities.php');
    require_once(ROOT . '/Institution/Role/Utility/InstitutionActions.php');
    require_once(ROOT . '/Institution/Role/Utility/InstitutionRoles.php');
    require_once(ROOT . '/Document/Utility/Document.php');
    require_once(ROOT . '/Document/Utility/DocumentItem.php');
    require_once(ROOT . '/DataAccessObject/DataObjects.php');

    $debugHeader = 'In ' . basename(__FILE__) . ', ';

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email                  = $_POST['email'];
    $hashedPassword         = $_POST['hashedPassword'];
    $creatorUserEmail       = $_POST['creatorUserEmail'];
    $institutionName        = $_POST['institutionName'];
    $institutionAddressID   = $_POST['institutionAddress'];
    $documentItems          = json_decode($_POST['documentItems'], true);

    $debugModeExists    = isset($_POST['debugMode']);
    if($debugModeExists)
        $debugMode          = $_POST['debugMode'];

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

    if ($institutionName == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus('NULL_INPUT'))
            ->send(StatusCodes::BAD_REQUEST);
    }

   // CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

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
            SELECT Address_ID FROM Institution_Addresses_List WHERE Institution_ID = :institutionID AND Is_Main_Address = TRUE;
        ');
        $getMainAddressIDStatement->bindParam(':institutionID', $institutionID);
        $getMainAddressIDStatement->execute();
        $addressRows = $getMainAddressIDStatement->fetch(PDO::FETCH_ASSOC);

        $institutionAddressID = $addressRows['Address_ID'];
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

    $invoice = new Invoice();

    $invoice
        ->setCreatorID($creatorUserID)
        ->setSenderInstitutionID($institutionID)
        ->setSenderAddressID($institutionAddressID);


    if($documentItems != null) {
        foreach ($documentItems as $item) {

            //echo print_r($item), PHP_EOL;

            $itemObject = new DocumentItem();
            $itemObject
                ->setProductNumber($item['productNumber'])
                ->setDescription($item['description'])
                ->setTitle($item['title'])
                ->setValueBeforeTax($item['valueBeforeTax'])
                ->setTaxPercentage($item['taxPercentage'])
                ->setInstitutionID($institutionID)
                ->setCurrency(Currency::getCurrencyByTitle($item['currencyTitle']));

            if($debugMode)
                echo $debugHeader . ' PASSED VAR TESTl, Item PRE INSERT, LINE : ', __LINE__ ,', VAR = ', json_encode($itemObject->getDAO()),  PHP_EOL;

            $invoice->addItem($itemObject, $item['quantity']);
        }
    }

    if($debugMode)
        echo $debugHeader . ' PASSED VAR TEST,Invoice PRE INSERT , LINE : ', __LINE__ ,', VAR = ', json_encode($invoice->getDAO()),  PHP_EOL;

    try {
        $invoice->addIntoDatabase();

        if($debugMode)
            echo $debugHeader . ' PASSED VAR TEST, Invoice POST INSERT, LINE : ', __LINE__ ,', VAR = ', json_encode($invoice->getDAO()),  PHP_EOL;
    } catch (DocumentItemInvalid $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ITEM"))
            ->send();
    } catch (DocumentTypeNotFound $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOC_TYPE_INVALID"))
            ->send();
    } catch (DocumentInvalid $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOC_INVALID"))
            ->send();
    }

    DatabaseManager::Disconnect();
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();
?>