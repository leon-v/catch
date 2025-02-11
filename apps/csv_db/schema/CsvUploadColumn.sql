CREATE DATABASE IF NOT EXISTS symfony;
USE symfony;

CREATE TABLE IF NOT EXISTS `CsvUploadColumn` (
  `csvUploadId` int unsigned NOT NULL,
  `columnIndex` smallint DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  KEY `csvUploadId_columnIndex` (`csvUploadId`,`columnIndex`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;