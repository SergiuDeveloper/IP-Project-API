<?php

    if(!defined('ROOT'))
    {
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    require_once(ROOT . "/Institution/Utility/InstitutionValidator.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");

    require_once(ROOT . "/Document/Utility/Document.php");
    require_once(ROOT . "/Document/Utility/Invoice.php");
    require_once(ROOT . "/Document/Utility/Receipt.php");

    $email = $_GET['email'];
    $hashedPassword = $_GET['hashedPassword'];
    $institutionName = $_GET['institutionName'];

    CommonEndPointLogic::ValidateHTTPGETRequest();

    if ($email == null || $hashedPassword == null)
    {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    if($institutionName != null){
        InstitutionValidator::validateInstitution($institutionName);
    }

    try {
        DatabaseManager::Connect();

        $getUserIDStatement = DatabaseManager::PrepareStatement("
            SELECT ID FROM users WHERE Email = :email
        ");
        $getUserIDStatement->bindParam(":email", $email);
        $getUserIDStatement->execute();

        $userIDRow = $getUserIDStatement->fetch(PDO::FETCH_OBJ);

        $userID = $userIDRow->ID;

        $statementString = "SELECT * FROM documents WHERE Creator_User_ID = :creatorID";
        if($institutionName != null){
            $statementString = $statementString . " AND Sender_Institution_ID = :senderInstitutionID";
        }

        $getDocumentHeadersForCreatorStatement = DatabaseManager::PrepareStatement(
            $statementString
        );
        $getDocumentHeadersForCreatorStatement->bindParam(":creatorID", $userID);
        if($institutionName != null){
            $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();
            $getDocumentHeadersForCreatorStatement->bindParam(":senderInstitutionID", $institutionID);
        }

        $getDocumentHeadersForCreatorStatement->execute();

        $responseArray = array();

        while($row = $getDocumentHeadersForCreatorStatement->fetch(PDO::FETCH_ASSOC)){
            array_push($responseArray,
                new \DAO\Document(
                    $row['ID'],
                    $row['Sender_User_ID'],
                    $row['Sender_Institution_ID'],
                    $row['Sender_Address_ID'],
                    $row['Receiver_User_ID'],
                    $row['Receiver_Institution_ID'],
                    $row['Receiver_Address_ID'],
                    $row['Creator_User_ID'],
                    $row['Date_Created'],
                    $row['Date_Sent'],
                    $row['Is_Sent']
                )
            );
        }

        DatabaseManager::Disconnect();
    }
    catch (PDOException $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
    }

    try {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->addResponseData("createdDocuments", $responseArray)
            ->send();
    } catch (Exception $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

?>