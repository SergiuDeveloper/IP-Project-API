<?php

    if(!defined('ROOT'))
        define('ROOT' , dirname(__FILE__) . '/../../..');

    require_once (ROOT . '/Document/Utility/Exception/DocumentSendAlreadySent.php');
    require_once (ROOT . '/Document/Utility/Exception/DocumentNotFound.php');
    require_once (ROOT . '/Document/Utility/Exception/DocumentInvalid.php');
    require_once (ROOT . '/Document/Utility/Exception/DocumentTypeNotFound.php');
    require_once (ROOT . '/Document/Utility/Exception/DocumentItemDuplicate.php');
    require_once (ROOT . '/Document/Utility/Exception/DocumentItemInvalid.php');
    require_once (ROOT . '/Document/Utility/Exception/DocumentItemMultipleResults.php');
    require_once (ROOT . '/Document/Utility/Exception/DocumentNotEnoughFetchArguments.php');
    require_once (ROOT . '/Document/Utility/Exception/DocumentSendInvalidReceiverInstitution.php');
    require_once (ROOT . '/Document/Utility/Exception/DocumentSendInvalidReceiverUser.php');
    require_once (ROOT . '/Document/Utility/Exception/DocumentSendNoReceiverInstitution.php');
    require_once (ROOT . '/Document/Utility/Exception/DocumentSendUpdateStatementFailed.php');

?>