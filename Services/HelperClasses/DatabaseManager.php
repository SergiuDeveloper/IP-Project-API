<?php

class DatabaseManager {
    private static $ServerName = 'sergiu-mysql-server.mysql.database.azure.com';
    private static $SchemaName = 'Fiscal_Documents_EDI_Test';
    private static $Username = 'Fiscal_Documents_EDI_User@sergiu-mysql-server';
    private static $Password = 'Fiscal_Documents_EDI_Password';

    private static $pdoDatabaseConnection;
    private static $connectionActive = false;

    public static function Connect() {
        if (DatabaseManager::$connectionActive)
            return false;

        $connectionString = sprintf(
            'mysql:host=%s;dbname=%s;',
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

    public static function Disconnect() {
        if (!DatabaseManager::$connectionActive)
            return false;

        DatabaseManager::$pdoDatabaseConnection = null;
        DatabaseManager::$connectionActive = false;
        return true;
    }

    public static function PrepareStatement($preparedStatementQuery) {
        if (!DatabaseManager::$connectionActive)
            return null;

        $preparedStatement = DatabaseManager::$pdoDatabaseConnection->prepare($preparedStatementQuery);
        return $preparedStatement;      
    }
}

?>