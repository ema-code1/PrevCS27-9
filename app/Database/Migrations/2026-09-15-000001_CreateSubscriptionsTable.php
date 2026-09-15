<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Suscripciones a PrevCS Pro cobradas con Mercado Pago (preapproval con plan).
 *
 * Cada intento de suscripción tiene un external_reference propio y único: es
 * el ancla para reconciliar con Mercado Pago (webhooks, búsquedas) y evita
 * que un reintento se confunda con otro.
 *
 * user_id queda vacío si la persona paga antes de registrarse; Auth::register
 * la vincula al crear la cuenta.
 *
 * status: creating / failed son estados locales (antes o en lugar de la
 * respuesta de Mercado Pago); pending / authorized / paused / canceled son los
 * de Mercado Pago.
 */
class CreateSubscriptionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'external_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'mp_preapproval_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'mp_plan_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'payer_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 254,
            ],
            'payer_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'creating',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'currency' => [
                'type'       => 'CHAR',
                'constraint' => 3,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('external_reference');
        $this->forge->addKey('user_id');
        $this->forge->addKey('mp_preapproval_id');

        $this->forge->createTable('subscriptions', true);
    }

    public function down()
    {
        $this->forge->dropTable('subscriptions', true);
    }
}
