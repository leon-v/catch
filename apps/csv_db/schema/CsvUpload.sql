CREATE DATABASE IF NOT EXISTS symfony;
USE symfony;

CREATE TABLE IF NOT EXISTS  `CsvUpload` (
  `csvUploadId` int unsigned NOT NULL AUTO_INCREMENT,
  `fileName` varchar(64) NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`csvUploadId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;