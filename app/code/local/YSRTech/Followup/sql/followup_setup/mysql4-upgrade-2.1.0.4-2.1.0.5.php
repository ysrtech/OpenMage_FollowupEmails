<?php


$installer = $this;
$installer->startSetup();

$installer->run("ALTER TABLE `{$this->getTable('followup_autoresponders_events')}` ADD COLUMN `data_object_id` int");
$installer->run("ALTER TABLE `{$this->getTable('followup_autoresponders_events')}` ADD COLUMN `message` VARCHAR (100)");

$installer->endSetup();
