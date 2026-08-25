<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Tablas de stock por espacio.
 *
 * Jerarquía: usuario → espacio (industries) → tabla de stock → columnas y filas
 * → celdas. Una tabla nunca es visible desde otro espacio porque todo cuelga de
 * industry_id y el acceso se resuelve por industry_users.
 *
 * Cada fila representa UN ítem de stock. La columna de posición 0 tiene
 * is_item_column = 1: es la que guarda el nombre del ítem y la que van a
 * nombrar las alertas ("hay una baja de stock en {item}").
 */
class CreateStockTables extends Migration
{
    public function up()
    {
        $this->createTables();
        $this->createColumns();
        $this->createRows();
        $this->createCells();
    }

    public function down()
    {
        // Orden inverso al de creación para no chocar con las FK.
        foreach (['stock_cells', 'stock_rows', 'stock_columns', 'stock_tables'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    /* ── Tablas: una o varias por espacio ────────────────────────────── */
    private function createTables(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'industry_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'default'    => 'Tabla de stock',
            ],
            'position' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['industry_id', 'position'], false, false, 'idx_stock_tables_orden');
        $this->forge->addKey('created_by', false, false, 'fk_stock_tables_creator');
        $this->forge->addForeignKey('industry_id', 'industries', 'id', 'CASCADE', 'CASCADE', 'fk_stock_tables_industry');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_stock_tables_creator');
        $this->forge->createTable('stock_tables', true);
    }

    /* ── Columnas ────────────────────────────────────────────────────── */
    private function createColumns(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'table_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            // VARCHAR y no ENUM a propósito: sumar un tipo nuevo (numero, fecha,
            // chip...) no debe requerir una migración ni tocar los datos.
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'texto',
            ],
            // Etiqueta del contador o mínimo ("unidades", "cajas", "kg"...).
            'unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            // Opciones propias de cada tipo futuro: lista de chips, formato de
            // fecha, mínimo/máximo del contador para las alertas de stock.
            'settings' => [
                'type' => 'JSON',
                'null' => true,
            ],
            // La columna del ítem: permanente, siempre en la posición 0. No se
            // puede borrar ni cambiar de tipo; solo renombrar su encabezado.
            'is_item_column' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'position' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['table_id', 'position'], false, false, 'idx_stock_columns_orden');
        $this->forge->addForeignKey('table_id', 'stock_tables', 'id', 'CASCADE', 'CASCADE', 'fk_stock_columns_table');
        $this->forge->createTable('stock_columns', true);
    }

    /* ── Filas: cada una es un ítem ──────────────────────────────────── */
    private function createRows(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'table_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'position' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['table_id', 'position'], false, false, 'idx_stock_rows_orden');
        $this->forge->addForeignKey('table_id', 'stock_tables', 'id', 'CASCADE', 'CASCADE', 'fk_stock_rows_table');
        $this->forge->createTable('stock_rows', true);
    }

    /* ── Celdas: intersección fila × columna ─────────────────────────── */
    private function createCells(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'row_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'column_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            // Valor de las columnas de texto (y valor crudo de tipos futuros).
            'value_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Valor de los contadores y mínimos. Columna aparte y tipada para
            // que las alertas de stock puedan comparar sin castear nada.
            'value_number' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,3',
                'null'       => true,
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        // Una sola celda por cruce; es lo que hace seguro el upsert.
        $this->forge->addUniqueKey(['row_id', 'column_id'], 'uq_stock_cells_celda');
        $this->forge->addKey('column_id', false, false, 'fk_stock_cells_column');
        $this->forge->addForeignKey('row_id', 'stock_rows', 'id', 'CASCADE', 'CASCADE', 'fk_stock_cells_row');
        $this->forge->addForeignKey('column_id', 'stock_columns', 'id', 'CASCADE', 'CASCADE', 'fk_stock_cells_column');
        $this->forge->createTable('stock_cells', true);
    }
}
