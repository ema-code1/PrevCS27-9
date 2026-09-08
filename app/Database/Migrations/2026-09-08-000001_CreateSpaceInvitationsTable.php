<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Invitaciones por correo a un espacio.
 *
 * Funciona como el "compartir" de Google Docs: el admin escribe un mail, se
 * guarda una invitación con un token de un solo uso y se manda el link por
 * correo. Cuando la persona entra al link con la sesión de ESE mail, se le
 * crea la membresía en industry_users con el rol y el permiso que eligió el
 * admin, y la invitación queda marcada como aceptada.
 *
 * El token viaja en la URL y en la base queda solo su SHA-256: si alguien lee
 * la tabla no puede reconstruir el link.
 */
class CreateSpaceInvitationsTable extends Migration
{
    public function up()
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
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 254,
            ],
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['admin_principal', 'supervisor'],
                'default'    => 'supervisor',
            ],
            'permission' => [
                'type'       => 'ENUM',
                'constraint' => ['read', 'edit'],
                'default'    => 'read',
            ],
            // SHA-256 en hexadecimal del token que viaja en el link.
            'token_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'invited_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'accepted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'accepted_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token_hash');
        // Búsqueda del panel del modal: pendientes de un espacio.
        $this->forge->addKey(['industry_id', 'email']);
        $this->forge->addForeignKey('industry_id', 'industries', 'id', '', 'CASCADE');

        $this->forge->createTable('space_invitations', true);
    }

    public function down()
    {
        $this->forge->dropTable('space_invitations', true);
    }
}
