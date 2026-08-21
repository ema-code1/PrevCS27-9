-- Tablas de stock por espacio (migracion 2026-08-21-000001_CreateStockTables).
--
-- Jerarquia: espacio (industries) -> stock_tables -> stock_columns / stock_rows
-- -> stock_cells. Cada fila es un item: su nombre vive en la columna con
-- is_item_column = 1, que es la que van a nombrar las alertas de stock.
--
-- stock_columns.type es VARCHAR y no ENUM a proposito: sumar un tipo nuevo
-- (numero, fecha, chip...) no debe requerir migrar datos.

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `industry_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT 'Tabla de stock',
  `position` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stock_tables_orden` (`industry_id`,`position`),
  KEY `fk_stock_tables_creator` (`created_by`),
  CONSTRAINT `fk_stock_tables_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE SET NULL,
  CONSTRAINT `fk_stock_tables_industry` FOREIGN KEY (`industry_id`) REFERENCES `industries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_columns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'texto',
  `unit` varchar(40) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `is_item_column` tinyint(1) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stock_columns_orden` (`table_id`,`position`),
  CONSTRAINT `fk_stock_columns_table` FOREIGN KEY (`table_id`) REFERENCES `stock_tables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_rows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_id` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stock_rows_orden` (`table_id`,`position`),
  CONSTRAINT `fk_stock_rows_table` FOREIGN KEY (`table_id`) REFERENCES `stock_tables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_cells` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `row_id` int(11) NOT NULL,
  `column_id` int(11) NOT NULL,
  `value_text` text DEFAULT NULL,
  `value_number` decimal(14,3) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_cells_celda` (`row_id`,`column_id`),
  KEY `fk_stock_cells_column` (`column_id`),
  CONSTRAINT `fk_stock_cells_column` FOREIGN KEY (`column_id`) REFERENCES `stock_columns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_cells_row` FOREIGN KEY (`row_id`) REFERENCES `stock_rows` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
