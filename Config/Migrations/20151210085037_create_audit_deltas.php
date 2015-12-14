<?php
use Migrations\AbstractMigration;

class CreateAuditDeltas extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('audit_deltas');
        $table->addColumn('audit_id', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('property_name', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('old_value', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('new_value', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->create();
    }
}
