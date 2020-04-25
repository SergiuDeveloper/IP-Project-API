<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    require_once(ROOT . "Institution/Role/Utility/InstitutionActions.php");
    require_once(ROOT . "Institution/Role/Utility/InstitutionRoles.php");

    require_once(ROOT . "/Utility/Document.php");
    require_once(ROOT . "/Utility/Invoice.php");
    require_once(ROOT . "/Utility/Receipt.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email              = $_GET["email"];
    $hashedPassword     = $_GET["hashedPassword"];
    $institutionName    = $_GET["institutionName"];
    $documentId    = $_GET["documentId"];

    if ($email == null || $hashedPassword == null || $institutionName == null || $documentId == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

    $documentQuery = "SELECT * FROM documents WHERE ID = :documentId;";

    $receiptQuery = "SELECT * FROM Receipts WHERE Documents_ID = :documentId;";

    $invoiceQuery = "SELECT * FROM Invoices WHERE Documents_ID = :documentId;";

    $queryGetItems = "SELECT * FROM document_items WHERE Documents_ID = :documentId;";

    $queryGetItem = "SELECT * FROM Items WHERE ID = :itemId;";

    $institutionRoles = array();
    try {
        DatabaseManager::Connect();

        $getInstitution = DatabaseManager::PrepareStatement($queryIdInstitution);
        $getInstitution->bindParam(":institutionName", $institutionName);
        $getInstitution->execute();

        $institutionRow = $getInstitution->fetch(PDO::FETCH_ASSOC);

        if($institutionRow == null){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND"))
                ->send();
        }

        $getDocument = DatabaseManager::PrepareStatement($documentQuery);
        $getDocument->bindParam(":documentId", $documentId);
        $getDocument->execute();

        $documentRow = $documentRow->fetch(PDO::FETCH_ASSOC);

        if($documentRow == null){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_INVALID"))
                ->send();
        } else {
            if($documentRow["Sender_Institution_ID"] == $institutionRow["ID"]){
                $type = "intern";
            }
            
            if($documentRow["Receiver_Institution_ID"] == $institutionRow["ID"]){
                $type = "received";
            }
        }

        if($type == "intern"){
            if( false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::PREVIEW_UPLOADED_DOCUMENTS)) {
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                    ->send();
            }
        } else if($type == "received"){
            if( false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::PREVIEW_SPECIFIC_RECEIVED_DOCUMENT)) {
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                    ->send();
            }
        }


        DatabaseManager::Connect();

        $getReceipt = DatabaseManager::PrepareStatement($receiptQuery);
        $getReceipt->bindParam(":documentId", $documentId);
        $getReceipt->execute();

        $receiptRow = $getReceipt->fetch(PDO::FETCH_ASSOC);

        $getInvoice = DatabaseManager::PrepareStatement($invoiceQuery);
        $getInvoice->bindParam(":documentId", $documentId);
        $getInvoice->execute();

        $invoiceRow = $getInvoice->fetch(PDO::FETCH_ASSOC);

        if($receiptRow == null && $invoiceQuery == null){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_NOT_FOUND"))
                ->send();
        }

        if($receiptRow == null){
            $document = new Invoice();
            $document->setID($documentRow["ID"]);
            $document->setReceiverAddressID($documentRow["Receiver_Address_ID"]);
            $document->setReceiverID($documentRow["Receiver_User_ID"]);
            $document->setReceiverInstitutionID($documentRow["Receiver_Institution_ID"]);
            $document->setSenderAddressID($documentRow["Sender_Address_ID"]);
            $document->setSenderID($documentRow["Sender_User_ID"]);
            $document->setSenderInstitutionID($documentRow["Sender_Institution_ID"]);
            $document->setCreatorID($documentRow["Creator_User_ID"]);
            $document->getReceiptID($invoiceRow["Invoices_ID"]);


            $getItems = DatabaseManager::PrepareStatement($queryGetItems);
            $getItems->bindParam(":documentId",$documentRow["ID"]);
            $getItems->execute();
    
            while($getItemsRow = $getItems->fetch(PDO::FETCH_ASSOC)){

                $getItem = DatabaseManager::PrepareStatement($queryGetItem);
                $getItem->bindParam(":itemId",$getItemRow["Items_ID"]);
                $getItem->execute();

                $getItemRow = $getInvoice->fetch(PDO::FETCH_ASSOC);
                
                $item = new Item($getItemRow["Product_Number"], $getItemRow["Description"],$getItemRow["Value_Before_Tax"],$getItemRow["Tax_Percentage"],$getItemRow["Value_After_Tax"]);

                $itemContainer = new ItemContainer($getItemsRow["Quantity"],$item);
                $document->addItem($itemContainer);
            }
        }

        if($invoiceRow == null){
            $document = new Receipt();
            $document->setID($documentRow["ID"]);
            $document->setReceiverAddressID($documentRow["Receiver_Address_ID"]);
            $document->setReceiverID($documentRow["Receiver_User_ID"]);
            $document->setReceiverInstitutionID($documentRow["Receiver_Institution_ID"]);
            $document->setSenderAddressID($documentRow["Sender_Address_ID"]);
            $document->setSenderID($documentRow["Sender_User_ID"]);
            $document->setSenderInstitutionID($documentRow["Sender_Institution_ID"]);
            $document->setCreatorID($documentRow["Creator_User_ID"]);
            //$document->getInvoiceID($receiptRow["Invoices_ID"]); -- nu exista in clasa functia


            $getItems = DatabaseManager::PrepareStatement($queryGetItems);
            $getItems->bindParam(":documentId",$documentRow["ID"]);
            $getItems->execute();
    
            while($getItemsRow = $getItems->fetch(PDO::FETCH_ASSOC)){

                $getItem = DatabaseManager::PrepareStatement($queryGetItem);
                $getItem->bindParam(":itemId",$getItemRow["Items_ID"]);
                $getItem->execute();

                $getItemRow = $getInvoice->fetch(PDO::FETCH_ASSOC);
                
                $item = new Item($getItemRow["Product_Number"], $getItemRow["Description"],$getItemRow["Value_Before_Tax"],$getItemRow["Tax_Percentage"],$getItemRow["Value_After_Tax"]);

                $itemContainer = new ItemContainer($getItemsRow["Quantity"],$item);
                $document->addItem($itemContainer);
            }

            $document->setPaymentAmount($documentRow["Payment_Number"]);
            
            // $getPaymentMethod = DatabaseManager::PrepareStatement($queryGetPayment); // asa apare pe flow, dar clasa permite 
            // $getPaymentMethod->bindParam(":ID",$receiptRow["Payment_Methods_ID"]); // doar trimiterea ID-ului
            // $getPaymentMethod->execute();

            // $getPayment = $getPaymentMethod->fetch(PDO::FETCH_ASSOC);

            // $paymentMethod = new PaymentMethod($getPayment["ID"],$getPayment["title"]);

            // $document->setPaymentMethod($paymentMethod);  // gen nu exista functie care sa preia obiectul asta :D

            $document->setPaymentMethodID($documentRow["Payment_Methods_ID"]); // so, I'll do smth like this
        }
       
        DatabaseManager::Disconnect();
    }
    catch (Exception $databaseException) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
    }

    try {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->addResponseData("document", $document)
            ->send();
    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }


    class Item {
        public $productNumber;
        public $description;
        public $unitPrice;
        public $itemTax;
        public $unitPriceWithTax;
        public function __construct($productNumber, $description, $unitPrice, $itemTax, $unitPriceWithTax){
          $this->productNumber = $productNumber;
          $this->description = $description;
          $this->unitPrice = $unitPrice;
          $this->itemTax = $itemTax;
          $this->unitPriceWithTax = $unitPriceWithTax;
        }
    }
    class ItemContainer {
        public $quantity;
        public $item;

        function __construct($quantity, $item)
        {
            $this->quantity = $quantity;
            $this->item = $item;
        }
    }
    // class PaymentMethod { 
    //     public $ID;
    //     public $title;

    //     function __construct($ID, $title)
    //     {
    //         $this->ID = $ID;
    //         $this->title = $title;
    //     }
    // } 
?>