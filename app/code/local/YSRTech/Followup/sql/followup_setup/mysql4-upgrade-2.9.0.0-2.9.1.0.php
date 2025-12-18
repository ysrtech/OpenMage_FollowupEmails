<?php
/**
 * Upgrade script to add mailgun_email_id column for integration with Mailgun tracking
 */

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

// Add mailgun_email_id column to events table to store reference to Mailgun email record
if (!$connection->tableColumnExists($installer->getTable('followup/events'), 'mailgun_email_id')) {
    $connection->addColumn(
        $installer->getTable('followup/events'),
        'mailgun_email_id',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'unsigned' => true,
            'nullable' => true,
            'comment'  => 'Reference to Mailgun email tracking record ID'
        )
    );
}

$installer->endSetup();
