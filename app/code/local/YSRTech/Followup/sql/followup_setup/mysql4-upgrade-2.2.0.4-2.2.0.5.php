<?php


$installer = $this;
$installer->startSetup();

$installer->run("DELETE FROM `{$installer->getTable('followup_subscribers')}` WHERE email IS NULL OR LENGTH(email)<5");
$installer->run("DELETE FROM `{$installer->getTable('newsletter_subscriber')}` WHERE subscriber_email IS NULL OR LENGTH(subscriber_email)<5");

$installer->endSetup();
