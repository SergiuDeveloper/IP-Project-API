<?php

/**
    Database management class(connection, disconnection, statement preparing)
*/
class DatabaseManager {
    private static $URL;
    private static $Username;
    private static $Password;
    private static $Schema;

    private static $credentialsJSONFilePath = "./../Sensitive/Database.json";
    private static $credentialsBinded = false;

    /**
     * Used by PhpStorm to identify PDO methods
     * @var PDO
     */
    private static $pdoDatabaseConnection;
    private static $connectionActive = false;

    /**
     * @return bool Database Connection success state
     */
    public static function Connect() {
        if (DatabaseManager::$connectionActive)
            return false;

        if (!DatabaseManager::$credentialsBinded)
            DatabaseManager::BindCredentials();

        $connectionString = sprintf(
            "mysql:host=%s;dbname=%s;",
            DatabaseManager::$URL,
            DatabaseManager::$Schema
        );

        try {
            DatabaseManager::$pdoDatabaseConnection = new PDO(
                $connectionString,
                DatabaseManager::$Username,
                DatabaseManager::$Password
            );
        }
        catch (Exception $exception) {
            echo $exception;
            return false;
        }

        DatabaseManager::$connectionActive = true;
        return true;
    }

    /**
     * @return bool Database disconnection success state
     */
    public static function Disconnect() {
        if (!DatabaseManager::$connectionActive)
            return false;

        DatabaseManager::$pdoDatabaseConnection = null;
        DatabaseManager::$connectionActive = false;
        return true;
    }

    /**
     * @param $preparedStatementQuery   string              Prepared statement resulted from the input query
     * @return                          PDOStatement|null   PDOStatement if connection is active, null otherwise
     */
    public static function PrepareStatement($preparedStatementQuery) {
        if (!DatabaseManager::$connectionActive)
            return null;

        return DatabaseManager::$pdoDatabaseConnection->prepare($preparedStatementQuery);
    }

    /**
     *  Binds the database credentials from the JSON file
     */
    private static function BindCredentials() {
        $jsonFileContent = file_get_contents(DatabaseManager::$credentialsJSONFilePath);

        if ($jsonFileContent === false)
            throw new Exception("Could not get Database Credentials JSON file content!");

        $dbCredentialsJSON = json_decode($jsonFileContent, true);
        if ($dbCredentialsJSON === null)
            throw new Exception("Database Credentials JSON syntax error!");

        $dbCredentialsJSON = $dbCredentialsJSON["Connection"];
        if ($dbCredentialsJSON === null)
            throw new Exception("Bad Database Credentials JSON object format");
        DatabaseManager::$URL =         $dbCredentialsJSON["URL"];
        DatabaseManager::$Username =    $dbCredentialsJSON["Username"];
        DatabaseManager::$Password =    $dbCredentialsJSON["Password"];
        DatabaseManager::$Schema =      $dbCredentialsJSON["Schema"];
        if (DatabaseManager::$URL === null || DatabaseManager::$Username === null || DatabaseManager::$Password === null || DatabaseManager::$Schema === null)
            throw new Exception("Bad Database Credentials JSON object format");

        DatabaseManager::$credentialsBinded = true;
    }
}