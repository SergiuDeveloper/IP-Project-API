<?php

if (!defined('ROOT'))
    define('ROOT', dirname(__FILE__) . '/..');

require_once(ROOT . '/Utility/DatabaseManager.php');

try{

    DatabaseManager::Connect();

    $pass  = 'parola';

    $pass = password_hash($pass, PASSWORD_BCRYPT);

    $statement = DatabaseManager::PrepareStatement("UPDATE users SET Hashed_Password = :pass WHERE Email = 'vlad.loghin00@gmail.com'" );

    $statement->bindParam(":pass", $pass);
    $statement->execute();

    DatabaseManager::Disconnect();

}
catch (Exception $exception){

}

echo 'DONE', PHP_EOL;

?>
