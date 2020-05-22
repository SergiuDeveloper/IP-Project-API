<?php

if (!defined('ROOT'))
    define('ROOT', dirname(__FILE__) . '/..');

require_once(ROOT . '/Utility/DatabaseManager.php');
require_once(ROOT . '/Utility/Utilities.php');

$email = $_GET['email'];

if(strcmp($email, 'vlad.loghin00@gmail.com') === 0){
    echo 'nice try.';
    die();
}

try{

    DatabaseManager::Connect();

    $pass  = 'pass';

    $pass = password_hash($pass, PASSWORD_BCRYPT);

    $statement = DatabaseManager::PrepareStatement("UPDATE users SET Hashed_Password = :pass WHERE Email = :email" );

    $statement->bindParam(":pass", $pass);
    $statement->bindParam(":email", $email);
    $statement->execute();

    DatabaseManager::Disconnect();

}
catch (Exception $exception){

}

echo 'DONE', PHP_EOL;

?>
