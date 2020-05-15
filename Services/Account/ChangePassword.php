<?php

   if(!defined('ROOT'))
   {
       define('ROOT', dirname(__FILE__) . '/..');
   }

   require_once(ROOT . "/Account/Utility/ModifyAccountManager.php");
   require_once(ROOT . "/Utility/CommonEndPointLogic.php");
   require_once(ROOT . "/Utility/StatusCodes.php");
   require_once(ROOT . "/Utility/ResponseHandler.php");

   CommonEndPointLogic::ValidateHTTPPOSTRequest();

   $inputEmail = $_POST["email"];

   if ($inputEmail == null)
   {
       ResponseHandler::getInstance()->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
                                     ->send(StatusCodes::BAD_REQUEST);
   }

   try {
       DatabaseManager::Connect();

       $sqlStatement = DatabaseManager::PrepareStatement("SELECT * FROM users WHERE Email = :email");
       $sqlStatement->bindParam(":email", $inputEmail);
       $sqlStatement->execute();

       if (($userRow = $sqlStatement->fetch()) == null) {
           ResponseHandler::getInstance()->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("EMAIL_NOT_FOUND"))
               ->send();
       }

       DatabaseManager::Disconnect();
   }catch (PDOException $exception){
       ResponseHandler::getInstance()
           ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
           ->send();
   }

   $alphabet = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

   $newPassword = substr(str_shuffle($alphabet), 0, 16);

   try {
       CommonEndPointLogic::SendPasswordEmail($inputEmail, "New Account Password", $newPassword);  // TODO : add different body for email
   }
   catch (Exception $e) {
      ResponseHandler::getInstance()
          ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("EMAIL_FAILURE"))
          ->send();
   }

   $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

   ModifyAccountManager::updateFieldsInDatabase($userRow["ID"], $newHashedPassword, null, null);

   ResponseHandler::getInstance()
      ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
      ->send();

?>