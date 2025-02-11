CREATE DATABASE IF NOT EXISTS symfony;
USE symfony;

CREATE TABLE IF NOT EXISTS `CsvUploadCell` (
  `csvUploadId` int unsigned NOT NULL,
  `rowIndex` bigint unsigned NOT NULL,
  `columnIndex` smallint unsigned NOT NULL,
  `value` mediumtext,
  KEY `csvUploadId_rowIndex_columnIndex` (`csvUploadId`,`rowIndex`,`columnIndex`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;