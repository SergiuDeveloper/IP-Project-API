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
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES (33,'Grecia','Vaslui','Tecuci','Ostrada',3,'T20',2,1),(53,'Iasi','Iasi','Iasi','strada',4,'TW',3,33),(35,'Romania',' Iasi',' Iasi',' Str Cerna',10,' Pentagonul',-1,10),(47,'Romania',' Iasi',' Iasi',' Str Cerna',10,' Pentagonul',-1,20),(57,'Romania','Iasi','Iasi','Calea Chisinaului',142,'A',-1,-1),(58,'Romania','Iasi','Iasi','Florilor',5,'A',-1,6),(59,'Romania','Iasi','Iasi','Florilor',5,'A',2,5),(8,'Romania','Iasi','Iasi','O strada',1,'A',4,NULL),(9,'Romania','Iasi','Iasi','O strada',1,'A',4,NULL),(10,'Romania','Iasi','Iasi','O strada',1,'A',4,NULL),(7,'Romania','Iasi','Iasi','O strada oarecare',13,'C',5,20),(50,'Romania','Iasi','Iasi','Revolutiei',14,'X7',-1,39),(56,'Romania','Iasi','Iasi','Sos. Nicolina',20,'X1',3,6),(51,'Romania','Iasi','Iasi','Str Florilor',23,'X6',-1,59),(5,'Romania','Iasi','Iasi','Strada Crunch-ului',30,'UAIC Corp C',8,3),(60,'Romania','Iasi','Iasi','Strada Mea',10,'B',2,10),(64,'Romania','Iasi','Iasi','Strada1',1,'C',-1,12),(61,'Romania','Iasi','Iasi','StradaStr',6,'A',-1,12),(24,'Romania','Iasi','Iasi','Strapungere Silvestru',31,'T6',4,20),(11,'Romania','Iasi','Iasi','Strapungere Silvestru',33,'T6',NULL,20),(12,'Romania','Iasi','Iasi','Strapungere Silvestru',33,'T6',NULL,20),(13,'Romania','Iasi','Iasi','Strapungere Silvestru',33,'T6',NULL,20),(14,'Romania','Iasi','Iasi','Strapungere Silvestru',33,'T6',NULL,20),(15,'Romania','Iasi','Iasi','Strapungere Silvestru',33,'T6',NULL,25),(16,'Romania','Iasi','Iasi','Strapungere Silvestru',33,'T6',-1,20),(20,'Romania','Iasi','Iasi','Strapungere Silvestru',33,'T6',-1,25),(17,'Romania','Iasi','Iasi','Strapungere Silvestru',33,'T6',4,20),(18,'Romania','Iasi','Iasi','Strapungere Silvestru',33,'T6',5,25),(25,'Romania','Iasi','Iasi','Strapungere Silvestru',312312,'T6',NULL,20),(65,'Romania','Neamt','Piatra Neamt','Dascalescu',2,'B6',2,10),(29,'Romaniaa','Iasia','Iasia','Strapungere Silvestru',33,'T6',4,20),(31,'Romaniaa','Iasia','Tecuci','Strapungere Silvestru',33,'T6',4,20),(63,'test','test','test','test',1,'test',1,1),(54,'test','test','test','test1',1,'test',2,3),(62,'testc','testr','testc1','tests',1,'testb',3,33);
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-05-09 14:53:25
