-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: sergiu-mysql-server.mysql.database.azure.com    Database: fiscal_documents_edi_test
-- ------------------------------------------------------
-- Server version	5.6.42.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES (1,1001001000,'Chair','Comfy Chair',80,0,80,1),(2,1001001001,'Table','Standard Table',150,0.2,180,1),(3,1001001002,'Rug','Regular Rug',20,0.1,22,1),(4,1001001003,'Tall Lamp','Bright, Tall Lamp',60,0,60,1),(5,1001001004,'Test Item 5','Unidentified Item',5,0,5,1),(6,1001001004,'Test Item 6','Unidentified Item',10,0.5,15,1),(7,1001009999,'Test Din App','Sper sa mearga',100,0.2,120,1),(9,1001009999,'Test Din App 2','Sper sa mearga 2',100,0.2,120,8),(13,1001009999,'Test Din App 3','Sper sa mearga 3',50,0.2,60,13),(14,1001009999,'Test Din App 4','Sper sa mearga 4',50,0.2,60,13),(15,1001005000,'Test Din App 5','Sper sa mearga 5',50,0.2,60,15),(16,99901555,'Scaun','Scaun Lemn Vopsit',20,50,1020,1),(17,99901500,'Masa','Masa Bucatarie',50,20,1050,1),(18,99901400,'Covor','Covor 50x20',30,0,30,1),(19,9990123,'Cui','Cui Metal',20,0.5,30,1),(20,99901524,'Masa Bucatarie','Masa Bucatarie PAL',50,0.2,60,1),(21,9990125,'Faianta','Faianta 15x5',30,0,30,1),(22,99910231,'Surub','Surub 10mm',20,0.1,22,1),(23,99950231,'Faianta Baie','Faianta Gri 5x10cm',15,0.1,16.5,1);
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-05-09 14:54:17
