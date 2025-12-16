<?php


$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

// Add email_template_id column to autoresponders table
if (!$connection->tableColumnExists($installer->getTable('Followup/autoresponders'), 'email_template_id')) {
    $connection->addColumn(
        $installer->getTable('Followup/autoresponders'),
        'email_template_id',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'nullable' => true,
            'comment'  => 'Email Template ID for notifications',
            'after'    => 'event'
        )
    );
}

$installer->endSetup();
