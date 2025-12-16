<?php
/**
 * Upgrade script to add multi-step email chain support
 */

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

// Add chain_steps column to autoresponders table to store email chain configuration
if (!$connection->tableColumnExists($installer->getTable('Followup/autoresponders'), 'chain_steps')) {
    $connection->addColumn(
        $installer->getTable('Followup/autoresponders'),
        'chain_steps',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable' => true,
            'comment'  => 'Serialized array of chain steps (delay, template_id)',
            'after'    => 'email_template_id'
        )
    );
}

// Add step_number column to events table to track which step of the chain this email is
if (!$connection->tableColumnExists($installer->getTable('Followup/events'), 'step_number')) {
    $connection->addColumn(
        $installer->getTable('Followup/events'),
        'step_number',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'nullable' => false,
            'default'  => 1,
            'comment'  => 'Chain step number (1 = first email, 2 = second email, etc.)',
            'after'    => 'autoresponder_id'
        )
    );
}

// Add cancelled column to events table to mark emails that shouldn't be sent
if (!$connection->tableColumnExists($installer->getTable('Followup/events'), 'cancelled')) {
    $connection->addColumn(
        $installer->getTable('Followup/events'),
        'cancelled',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'length'   => 1,
            'nullable' => false,
            'default'  => 0,
            'comment'  => 'Whether this email was cancelled (1 = yes, 0 = no)',
            'after'    => 'sent'
        )
    );
}

// Add tracking fields to events table
if (!$connection->tableColumnExists($installer->getTable('Followup/events'), 'opened_at')) {
    $connection->addColumn(
        $installer->getTable('Followup/events'),
        'opened_at',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            'nullable' => true,
            'comment'  => 'When the email was opened (tracking pixel loaded)',
            'after'    => 'sent_at'
        )
    );
}

if (!$connection->tableColumnExists($installer->getTable('Followup/events'), 'clicked_at')) {
    $connection->addColumn(
        $installer->getTable('Followup/events'),
        'clicked_at',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            'nullable' => true,
            'comment'  => 'When any link in the email was clicked',
            'after'    => 'opened_at'
        )
    );
}

if (!$connection->tableColumnExists($installer->getTable('Followup/events'), 'click_count')) {
    $connection->addColumn(
        $installer->getTable('Followup/events'),
        'click_count',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => 11,
            'nullable' => false,
            'default'  => 0,
            'comment'  => 'Number of times links were clicked',
            'after'    => 'clicked_at'
        )
    );
}

if (!$connection->tableColumnExists($installer->getTable('Followup/events'), 'converted_at')) {
    $connection->addColumn(
        $installer->getTable('Followup/events'),
        'converted_at',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            'nullable' => true,
            'comment'  => 'When the customer completed the goal (order, review, etc.)',
            'after'    => 'click_count'
        )
    );
}

$installer->endSetup();
