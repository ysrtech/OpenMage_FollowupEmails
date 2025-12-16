<?php


$installer = $this;
$installer->startSetup();

$installer->run("ALTER TABLE `{$installer->getTable('newsletter_subscriber')}` ADD COLUMN  `followup_updated_at` datetime DEFAULT CURRENT_TIMESTAMP");

$installer->run("CREATE TRIGGER `followup_change_update` BEFORE UPDATE ON `{$installer->getTable('newsletter_subscriber')}` FOR EACH ROW set NEW.followup_updated_at = IF(NEW.subscriber_status=1 AND OLD.subscriber_status!=1,NOW(),OLD.followup_updated_at)");

$installer->endSetup();
