<?php

/**
    Database management class(connection, disconnection, statement preparing)
*/
class DatabaseManager {
    private static $ServerName = "sergiu-mysql-server.mysql.database.azure.com";
    private static $SchemaName = "Fiscal_Documents_EDI_Test";
    private static $Username = "Fiscal_Documents_EDI_User@sergiu-mysql-server";
    private static $Password = "Fiscal_Documents_EDI_Password";

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

        $connectionString = sprintf(
            "mysql:host=%s;dbname=%s;",
            DatabaseManager::$ServerName,
            DatabaseManager::$SchemaName
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
}
