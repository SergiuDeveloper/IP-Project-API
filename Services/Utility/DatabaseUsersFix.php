<?php

if (!defined('ROOT'))
    define('ROOT', dirname(__FILE__));

require_once(ROOT . '/Utility/DatabaseManager.php');

try{

    DatabaseManager::Connect();

    $getUserRowsStatement = DatabaseManager::PrepareStatement("SELECT * FROM Users");

    $getUserRowsStatement->execute();

    while(($row = ($getUserRowsStatement->fetch(PDO::FETCH_ASSOC))) != null){

        $email = $row['Email'];
        $pass = $row['Hashed_Password'];
        $ID = $row['ID'];

        if(strpos($pass, '@') !== false){
            $updateStatement = DatabaseManager::PrepareStatement("UPDATE users SET Email = :email, Hashed_Password = :pass WHERE ID = :id");
            $updateStatement->bindParam(":email", $pass);
            $updateStatement->bindParam(":pass", $email);
            $updateStatement->bindParam(":id", $ID);

            $updateStatement->execute();
        }
    }

    DatabaseManager::Disconnect();

}
catch (Exception $exception){

}

echo 'DONE', PHP_EOL;

?>
