<?php

if (!defined('ROOT')) {
    define('ROOT', dirname(__FILE__) . '/..' );
}

require_once(ROOT . '/DataAccessObject/Document/Document.php');
require_once(ROOT . '/DataAccessObject/Document/Invoice.php');
require_once(ROOT . '/DataAccessObject/Document/Item.php');
require_once(ROOT . '/DataAccessObject/Document/ItemRow.php');
require_once(ROOT . '/DataAccessObject/Document/Receipt.php');