```sql
CREATE TABLE `CsvUpload` (
  `csvUploadId` int unsigned NOT NULL AUTO_INCREMENT,
  `fileName` varchar(64) NOT NULL,
  PRIMARY KEY (`csvUploadId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

```SQL
CREATE TABLE `CsvUploadCell` (
  `csvUploadId` int unsigned NOT NULL,
  `columnIndex` smallint unsigned NOT NULL,
  `rowIndex` bigint unsigned NOT NULL,
  `value` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

```SQL
CREATE TABLE `CsvUploadColumn` (
  `csvUploadId` int unsigned NOT NULL,
  `index` smallint DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```