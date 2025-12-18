<?php

class YSRTech_Followup_Model_Autoresponders extends Mage_Core_Model_Abstract
{

    const MYSQL_DATE = 'yyyy-MM-dd';
    const MYSQL_DATETIME = 'yyyy-MM-dd HH:mm:ss';

    protected function _construct()
    {

        $this->_init('followup/autoresponders');
    }

    public function toOptionArray()
    {

        $return = array(
            'order_new'      => Mage::helper('followup')->__('Order - New Order'),
            'order_product'  => Mage::helper('followup')->__('Order - Bought Specific Product'),
            'order_status'   => Mage::helper('followup')->__('Order - Order Status Changes'),
            'abandoned_cart' => Mage::helper('followup')->__('Abandoned Cart'),
            'new_invoice'    => Mage::helper('followup')->__('New Invoice'),
            'new_shipment'   => Mage::helper('followup')->__('New Shipment'),
            'new_creditmemo' => Mage::helper('followup')->__('New Creditmemo'),
        );


        return $return;
    }

    public function newOrderDocument($event)
    {

        if (!Mage::helper('followup')->isFollowupAvailable()) {
            return false;
        }

        $document = $event->getEvent()->getDataObject();

        if ($document instanceof Mage_Sales_Model_Order_Invoice) {
            $type = 'new_invoice';
        } elseif ($document instanceof Mage_Sales_Model_Order_Shipment) {
            $type = 'new_shipment';
        } elseif ($document instanceof Mage_Sales_Model_Order_Creditmemo) {
            $type = 'new_creditmemo';
        } else {
            return false;
        }

        $order = $document->getOrder();

        $autoresponders = $this->_getCollection($order->getStoreId())
            ->addFieldToFilter('event', $type);

        $customer = new Varien_Object;
        $customer->setName($order->getCustomerName())
            ->setEmail($order->getCustomerEmail())
            ->setId($order->getCustomerId());

        foreach ($autoresponders as $autoresponder) {
            $this->_insertData($autoresponder, null, $order->getStoreId(), $customer, $document->getId());
        }
    }

    public function changeStatus($event)
    {

        if (!Mage::helper('followup')->isFollowupAvailable()) {
            return false;
        }

        $order = $event->getEvent()->getOrder();
        $newStatus = $order->getData('status');
        $olderStatus = $order->getOrigData('status');

        if ($newStatus == $olderStatus) {
            return;
        }

        $autoresponders = $this->_getCollection($order->getStoreId())
            ->addFieldToFilter('event', 'order_status')
            ->addFieldToFilter('order_status', $newStatus);

        $customer = new Varien_Object;
        $customer->setName($order->getCustomerName())
            ->setEmail($order->getCustomerEmail())
            ->setId($order->getCustomerId());

        foreach ($autoresponders as $autoresponder) {
            $this->_insertData($autoresponder, null, $order->getStoreId(), $customer, $order->getId());
        }
    }

    public function newOrder($event)
    {

        if (!Mage::helper('followup')->isFollowupAvailable()) {
            return false;
        }

        $order = $event->getEvent()->getOrder();

        $autoresponders = $this->_getCollection($order->getStoreId())
            ->addFieldToFilter('event', array('in' => array('order_product', 'order_new')));

        $customer = new Varien_Object;
        $customer->setName($order->getCustomerName())
            ->setEmail($order->getCustomerEmail())
            ->setId($order->getCustomerId());

        foreach ($autoresponders as $autoresponder) {

            if ($autoresponder->getEvent() == 'order_product') {
                $items = $order->getAllItems();
                $ok = false;
                foreach ($items as $item) {
                    if ($item->getProductId() == $autoresponder->getProduct()) {
                        $ok = true;
                        break;
                    }
                }
                if ($ok === false) {
                    continue;
                }
            }

            $this->_insertData($autoresponder, null, $order->getStoreId(), $customer, $order->getId());
        }
    }

    /**
     * Check for abandoned carts and create autoresponder events
     * Called by cron job
     */
    public function processAbandonedCarts()
    {
        if (!Mage::helper('followup')->isFollowupAvailable()) {
            return false;
        }

        $abandonedHours = Mage::getStoreConfig('followup/config/abandoned_cart_hours');
        if (!$abandonedHours) {
            $abandonedHours = 1; // Default 1 hour
        }

        $currentTime = time();
        $from = $currentTime - (($abandonedHours + 24) * 3600); // Check carts from last 24 hours + threshold
        $to = $currentTime - ($abandonedHours * 3600); // Carts older than threshold

        // Get quotes that are abandoned
        $quotes = Mage::getResourceModel('sales/quote_collection')
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('items_count', array('gt' => 0))
            ->addFieldToFilter('customer_email', array('notnull' => true))
            ->addFieldToFilter('customer_email', array('neq' => ''))
            ->addFieldToFilter('updated_at', array(
                'from' => gmdate('Y-m-d H:i:s', $from),
                'to' => gmdate('Y-m-d H:i:s', $to)
            ));

        foreach ($quotes as $quote) {
            // Check if quote was converted to order
            if ($quote->getIsActive() && !$quote->getReservedOrderId()) {
                
                // Check if we already sent an abandoned cart email for this quote
                $existingEvent = Mage::getModel('followup/events')->getCollection()
                    ->addFieldToFilter('event', 'abandoned_cart')
                    ->addFieldToFilter('data_object_id', $quote->getId())
                    ->getFirstItem();

                if ($existingEvent->getId()) {
                    continue; // Already processed this cart
                }

                // Get active abandoned cart autoresponders
                $autoresponders = $this->_getCollection($quote->getStoreId())
                    ->addFieldToFilter('event', 'abandoned_cart');

                if ($autoresponders->getSize() > 0) {
                    $customer = new Varien_Object;
                    $customer->setName($quote->getCustomerFirstname() . ' ' . $quote->getCustomerLastname())
                        ->setEmail($quote->getCustomerEmail())
                        ->setId($quote->getCustomerId());

                    foreach ($autoresponders as $autoresponder) {
                        $this->_insertData($autoresponder, null, $quote->getStoreId(), $customer, $quote->getId());
                    }
                }
            }
        }
    }

    public function calculateSendDate($autoresponder)
    {
        $timestamp = time();

        if ($autoresponder->getSendMoment() == 'after') {
            // Add delay in seconds
            if ($autoresponder->getAfterHours() > 0) {
                $timestamp += $autoresponder->getAfterHours() * 3600;
            }
            if ($autoresponder->getAfterDays() > 0) {
                $timestamp += $autoresponder->getAfterDays() * 86400;
            }
        }

        // Return UTC datetime string
        return gmdate('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * Calculate send date for a specific chain step
     *
     * @param YSRTech_Followup_Model_Autoresponders $autoresponder
     * @param array $step
     * @return string
     */
    public function calculateSendDateForStep($autoresponder, $step)
    {
        $timestamp = time();

        if ($autoresponder->getSendMoment() == 'after') {
            // Add base delay from autoresponder
            if ($autoresponder->getAfterHours() > 0) {
                $timestamp += $autoresponder->getAfterHours() * 3600;
            }
            if ($autoresponder->getAfterDays() > 0) {
                $timestamp += $autoresponder->getAfterDays() * 86400;
            }
        }

        // Add step-specific delay
        if (!empty($step['delay_hours'])) {
            $timestamp += $step['delay_hours'] * 3600;
        }
        if (!empty($step['delay_days'])) {
            $timestamp += $step['delay_days'] * 86400;
        }

        // Return UTC datetime string
        return gmdate('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * Check if email chain should be cancelled (customer already converted)
     *
     * @param YSRTech_Followup_Model_Events $cron
     * @param YSRTech_Followup_Model_Autoresponders $autoresponder
     * @return bool
     */
    protected function _shouldCancelChain($cron, $autoresponder)
    {
        // For abandoned cart, check if customer completed an order
        if ($autoresponder->getEvent() == 'abandoned_cart') {
            if ($cron->getDataObjectId()) {
                $quote = Mage::getModel('sales/quote')->load($cron->getDataObjectId());
                if ($quote->getId() && $quote->getIsActive() == 0) {
                    // Quote is no longer active (likely converted to order)
                    return true;
                }
                
                // Check if customer has placed any order since the email was queued
                if ($cron->getCustomerEmail()) {
                    $orders = Mage::getModel('sales/order')->getCollection()
                        ->addFieldToFilter('customer_email', $cron->getCustomerEmail())
                        ->addFieldToFilter('created_at', array('gteq' => $cron->getCreatedAt()))
                        ->setPageSize(1);
                    
                    if ($orders->getSize() > 0) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Cancel all remaining unsent emails in this chain
     *
     * @param YSRTech_Followup_Model_Events $cron
     */
    protected function _cancelRemainingChainSteps($cron)
    {
        // Cancel this email
        $cron->setCancelled(1)->save();
        
        // Cancel all future steps in the same chain
        $futureSteps = Mage::getModel('followup/events')->getCollection()
            ->addFieldToFilter('autoresponder_id', $cron->getAutoresponderId())
            ->addFieldToFilter('customer_email', $cron->getCustomerEmail())
            ->addFieldToFilter('data_object_id', $cron->getDataObjectId())
            ->addFieldToFilter('sent', 0)
            ->addFieldToFilter('cancelled', 0)
            ->addFieldToFilter('created_at', $cron->getCreatedAt()); // Same trigger event
        
        foreach ($futureSteps as $step) {
            $step->setCancelled(1)->save();
        }
    }

    public function send()
    {
        if (!Mage::helper('followup')->isFollowupAvailable()) {

            return false;
        }

        $date = gmdate('Y-m-d H:i:s');

        $notificationCollection = Mage::getModel('followup/events')->getCollection()
            ->addFieldToFilter('sent', 0)
            ->addFieldToFilter('cancelled', 0)
            ->addFieldToFilter('send_at', array('lteq' => $date));

        foreach ($notificationCollection as $cron) {

            $autoresponder = Mage::getModel('followup/autoresponders')->load($cron->getAutoresponderId());

            // Check if chain should be cancelled (e.g., customer already converted)
            if ($this->_shouldCancelChain($cron, $autoresponder)) {
                $this->_cancelRemainingChainSteps($cron);
                continue;
            }

            // Register the event ID so observer can link Mailgun email to it
            if (Mage::helper('followup')->isMailgunActive()) {
                Mage::register('followup_current_event_id', $cron->getId());
            }

            // Send via Magento's native email system
            $result = $this->_sendEmail($cron, $autoresponder);

            if ($result === true) {
                $cron->setSent(1)->setSentAt($date);
                
                // If Mailgun is active, retrieve the captured Mailgun email ID from registry
                if (Mage::helper('followup')->isMailgunActive()) {
                    $mailgunEmailId = Mage::registry('followup_mailgun_email_id_' . $cron->getId());
                    if ($mailgunEmailId) {
                        $cron->setMailgunEmailId($mailgunEmailId);
                    }
                    // Clean up registry
                    Mage::unregister('followup_current_event_id');
                    Mage::unregister('followup_mailgun_email_id_' . $cron->getId());
                }
                
                $cron->save();
            } else {
                // Clean up registry on failure
                if (Mage::helper('followup')->isMailgunActive()) {
                    Mage::unregister('followup_current_event_id');
                }
            }
        }
    }

    /**
     * Cleanup sent email logs older than configured retention
     */
    public function cleanupLogs()
    {
        // Respect module toggle
        if (!Mage::helper('followup')->isFollowupAvailable()) {
            return false;
        }

        // Get retention days from config (default 60)
        $days = (int) Mage::getStoreConfig('followup/config/log_retention_days');
        if ($days <= 0) {
            $days = 60;
        }

        // Compute cutoff date
        $cutoff = gmdate('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

        // Delete sent events older than cutoff
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $table = Mage::getSingleton('core/resource')->getTableName('followup/events');

        try {
            $connection->delete($table, array(
                $connection->quoteInto('sent = ?', 1),
                'sent_at IS NOT NULL',
                $connection->quoteInto('sent_at <= ?', $cutoff),
            ));
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

        return true;
    }

    /**
     * Send notification via email using Magento's native email system
     *
     * @param YSRTech_Followup_Model_Events $cron
     * @param YSRTech_Followup_Model_Autoresponders $autoresponder
     * @return bool
     */
    protected function _sendEmail($cron, $autoresponder)
    {
        try {
            $storeId = Mage::app()->getStore()->getId();
            
            // Get email template ID - check chain steps first, then fall back to main template
            $templateId = null;
            $chainSteps = $autoresponder->getChainSteps();
            if ($chainSteps && is_string($chainSteps)) {
                $chainSteps = @unserialize($chainSteps);
            }
            
            if (is_array($chainSteps) && !empty($chainSteps)) {
                $stepNumber = $cron->getStepNumber() ? $cron->getStepNumber() : 1;
                $stepIndex = $stepNumber - 1;
                if (isset($chainSteps[$stepIndex]['template_id'])) {
                    $templateId = $chainSteps[$stepIndex]['template_id'];
                }
            }
            
            // Fall back to main template if no chain-specific template
            if (!$templateId) {
                $templateId = $autoresponder->getEmailTemplateId();
            }
            
            if (!$templateId) {
                Mage::log('Autoresponder ' . $autoresponder->getId() . ' step ' . $cron->getStepNumber() . ' has no email template assigned', Zend_Log::WARN, 'followup.log');
                return false;
            }

            // Prepare template variables
            $vars = array(
                'customer_name' => $cron->getCustomerName(),
                'customer_email' => $cron->getCustomerEmail(),
            );

            // Add order-specific variables
            if ($cron->getDataObjectId()) {
                $order = null;
                
                switch ($autoresponder->getEvent()) {
                    case 'abandoned_cart':
                        $quote = Mage::getModel('sales/quote')->load($cron->getDataObjectId());
                        if ($quote->getId()) {
                            $vars['quote'] = $quote;
                            $vars['cart_url'] = Mage::getUrl('checkout/cart', array('_store' => $storeId));
                            $vars['checkout_url'] = Mage::getUrl('checkout', array('_store' => $storeId));
                            
                            // Generate recovery link
                            $recoveryToken = md5($quote->getId() . $quote->getCustomerEmail() . time());
                            $quote->setData('recovery_token', $recoveryToken)->save();
                            $vars['recovery_url'] = Mage::getUrl('followup/cart/recover', array(
                                'token' => $recoveryToken,
                                'quote' => $quote->getId(),
                                '_store' => $storeId
                            ));
                        }
                        break;
                        
                    case 'new_invoice':
                        $invoice = Mage::getModel('sales/order_invoice')->load($cron->getDataObjectId());
                        if ($invoice->getId()) {
                            $order = $invoice->getOrder();
                            $vars['invoice'] = $invoice;
                        }
                        break;
                        
                    case 'new_shipment':
                        $shipment = Mage::getModel('sales/order_shipment')->load($cron->getDataObjectId());
                        if ($shipment->getId()) {
                            $order = $shipment->getOrder();
                            $vars['shipment'] = $shipment;
                            
                            // Add tracking information
                            $track = $shipment->getTracksCollection()->getFirstItem();
                            if ($track->getId()) {
                                $vars['tracking_number'] = $track->getTrackNumber();
                                $vars['tracking_title'] = $track->getTitle();
                            }
                        }
                        break;
                        
                    case 'new_creditmemo':
                        $creditmemo = Mage::getModel('sales/order_creditmemo')->load($cron->getDataObjectId());
                        if ($creditmemo->getId()) {
                            $order = $creditmemo->getOrder();
                            $vars['creditmemo'] = $creditmemo;
                        }
                        break;
                        
                    case 'order_new':
                    case 'order_status':
                    case 'order_product':
                        $order = Mage::getModel('sales/order')->load($cron->getDataObjectId());
                        break;
                }
                
                if ($order && $order->getId()) {
                    $vars['order'] = $order;
                }
            }

            // Get sender information from system configuration
            $sender = array(
                'name' => Mage::getStoreConfig('trans_email/ident_general/name', $storeId),
                'email' => Mage::getStoreConfig('trans_email/ident_general/email', $storeId),
            );

            // Check if email interception is enabled
            $interceptEmails = Mage::getStoreConfigFlag('followup/config/intercept_emails', $storeId);
            $interceptEmailAddress = Mage::getStoreConfig('followup/config/intercept_email_address', $storeId);
            
            // Determine recipient based on interception settings
            $recipientEmail = $cron->getCustomerEmail();
            $recipientName = $cron->getCustomerName();
            
            if ($interceptEmails && $interceptEmailAddress && Zend_Validate::is($interceptEmailAddress, 'EmailAddress')) {
                // Add original recipient info to template variables for reference
                $vars['original_recipient_email'] = $cron->getCustomerEmail();
                $vars['original_recipient_name'] = $cron->getCustomerName();
                $vars['intercepted'] = true;
                
                // Override recipient
                $recipientEmail = $interceptEmailAddress;
                $recipientName = 'Intercepted Email';
            }
            
            // Send email using OpenMage's transactional email system
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);

            $emailTemplate = Mage::getModel('core/email_template')
                ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId));
            
            // Send using OpenMage's sendTransactional method (uses proper mail transport)
            $emailTemplate->sendTransactional(
                $templateId,
                $sender,
                $recipientEmail,
                $recipientName,
                $vars,
                $storeId
            );

            $translate->setTranslateInline(true);

            return true;

        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    protected function _insertData($autoresponder, $number, $storeId, $customer, $dataObjectId = null, $customSendAt = null)
    {

        $storeIds = explode(',', $autoresponder->getStoreIds());

        if (!in_array($storeId, $storeIds) && !in_array(0,$storeIds)) {
            return false;
        }

        $data = array();
        $data['send_at'] = $customSendAt ? $customSendAt : $this->calculateSendDate($autoresponder);
        $data['autoresponder_id'] = $autoresponder->getId();
        $data['cellphone'] = $number;
        $data['customer_id'] = $customer->getId();
        $data['customer_name'] = $customer->getName();
        $data['customer_email'] = $customer->getEmail();
        $data['event'] = $autoresponder->getEvent();
        $data['created_at'] = gmdate('Y-m-d H:i:s');
        $data['sent'] = 0;
        $data['data_object_id'] = $dataObjectId;

        Mage::getModel('followup/events')->setData($data)->save();
        $autoresponder->setData('number_subscribers', $autoresponder->getData('number_subscribers') + 1)->save();
    }
    
    /**
     * Calculate send date for retroactive processing based on original event date
     * 
     * @param YSRTech_Followup_Model_Autoresponders $autoresponder
     * @param string $eventDate Original event date (Y-m-d H:i:s format)
     * @return string Send date in Y-m-d H:i:s format
     */
    protected function _calculateRetroactiveSendDate($autoresponder, $eventDate)
    {
        $timestamp = strtotime($eventDate);
        
        if ($autoresponder->getSendMoment() == 'after') {
            // Add delay in seconds
            if ($autoresponder->getAfterHours() > 0) {
                $timestamp += $autoresponder->getAfterHours() * 3600;
            }
            if ($autoresponder->getAfterDays() > 0) {
                $timestamp += $autoresponder->getAfterDays() * 86400;
            }
        }
        
        // If calculated date is in the past, send immediately
        $now = time();
        if ($timestamp < $now) {
            $timestamp = $now;
        }
        
        // Return UTC datetime string
        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    public function toFormValues()
    {
        $return = array();
        $collection = $this->getCollection()
            ->addFieldToSelect('name')
            ->addFieldToSelect('autoresponder_id')
            ->setOrder('name', 'ASC');
        foreach ($collection as $autoresponder) {
            $return[$autoresponder->getId()] = $autoresponder->getName() . ' (ID:' . $autoresponder->getId() . ')';
        }

        return $return;
    }

    protected function _getCollection($storeId)
    {

        $date = gmdate('Y-m-d');
        //Version Compatability
        $return = $this->getCollection()
            ->addFieldToFilter('active', 1);

        $return->getSelect()
            ->where(" FIND_IN_SET('0', store_ids) OR FIND_IN_SET(?, store_ids)", $storeId)
            ->where(" from_date <=? or from_date IS NULL ", $date)
            ->where(" to_date >=? or to_date IS NULL ", $date);


        return $return;
    }

    /**
     * Process events retroactively and queue follow-up emails based on autoresponder
     * 
     * @param int $autoresponder_id Autoresponder ID to use
     * @param int $daysAgo Number of days in the past to look for events
     * @return array Results with counts of processed items
     */
    public function processRetroactiveEmails($autoresponder_id, $daysAgo)
    {
        if (!Mage::helper('followup')->isFollowupAvailable()) {
            return array('success' => false, 'message' => 'Follow-up module is not available');
        }

        $results = array(
            'items_found' => 0,
            'emails_queued' => 0,
            'skipped' => 0,
            'errors' => 0,
            'success' => true
        );

        try {
            // Load the autoresponder
            $autoresponder = Mage::getModel('followup/autoresponders')->load($autoresponder_id);
            if (!$autoresponder->getId()) {
                return array('success' => false, 'message' => 'Invalid autoresponder');
            }

            $eventType = $autoresponder->getEvent();
            
            // Calculate date range
            $targetDate = date('Y-m-d', strtotime("-{$daysAgo} days"));
            $nextDay = date('Y-m-d', strtotime("-" . ($daysAgo - 1) . " days"));

            // Process based on event type from autoresponder
            switch ($eventType) {
                case 'new_shipment':
                    $results = $this->_processRetroactiveShipments($autoresponder, $targetDate, $nextDay);
                    break;
                    
                case 'new_invoice':
                    $results = $this->_processRetroactiveInvoices($autoresponder, $targetDate, $nextDay);
                    break;
                    
                case 'new_creditmemo':
                    $results = $this->_processRetroactiveCreditmemos($autoresponder, $targetDate, $nextDay);
                    break;
                    
                case 'order_new':
                case 'order_product':
                    $results = $this->_processRetroactiveOrders($autoresponder, $targetDate, $nextDay);
                    break;
                    
                case 'order_status':
                    $results = $this->_processRetroactiveOrderStatus($autoresponder, $targetDate, $nextDay);
                    break;
                    
                default:
                    return array('success' => false, 'message' => 'Event type "' . $eventType . '" is not supported for retroactive processing');
            }

        } catch (Exception $e) {
            Mage::logException($e);
            $results['success'] = false;
            $results['message'] = $e->getMessage();
        }

        return $results;
    }

    protected function _processRetroactiveShipments($autoresponder, $targetDate, $nextDay)
    {
        $results = array('items_found' => 0, 'emails_queued' => 0, 'skipped' => 0, 'errors' => 0, 'success' => true);
        
        $shipments = Mage::getModel('sales/order_shipment')->getCollection()
            ->addAttributeToFilter('created_at', array(
                'from' => $targetDate . ' 00:00:00',
                'to' => $nextDay . ' 00:00:00'
            ));

        $results['items_found'] = $shipments->getSize();

        foreach ($shipments as $shipment) {
            try {
                $order = $shipment->getOrder();
                if (!$order || !$order->getId()) {
                    $results['skipped']++;
                    continue;
                }

                // Check if email already sent
                if ($this->_checkIfAlreadyProcessed($autoresponder->getId(), $shipment->getId())) {
                    $results['skipped']++;
                    continue;
                }

                $customer = new Varien_Object;
                $customer->setName($order->getCustomerName())
                    ->setEmail($order->getCustomerEmail())
                    ->setId($order->getCustomerId());

                // Calculate send date from original shipment date
                $sendAt = $this->_calculateRetroactiveSendDate($autoresponder, $shipment->getCreatedAt());
                
                $this->_insertData($autoresponder, null, $order->getStoreId(), $customer, $shipment->getId(), $sendAt);
                $results['emails_queued']++;
            } catch (Exception $e) {
                Mage::logException($e);
                $results['errors']++;
            }
        }
        
        return $results;
    }

    protected function _processRetroactiveInvoices($autoresponder, $targetDate, $nextDay)
    {
        $results = array('items_found' => 0, 'emails_queued' => 0, 'skipped' => 0, 'errors' => 0, 'success' => true);
        
        $invoices = Mage::getModel('sales/order_invoice')->getCollection()
            ->addAttributeToFilter('created_at', array(
                'from' => $targetDate . ' 00:00:00',
                'to' => $nextDay . ' 00:00:00'
            ));

        $results['items_found'] = $invoices->getSize();

        foreach ($invoices as $invoice) {
            try {
                $order = $invoice->getOrder();
                if (!$order || !$order->getId()) {
                    $results['skipped']++;
                    continue;
                }

                if ($this->_checkIfAlreadyProcessed($autoresponder->getId(), $invoice->getId())) {
                    $results['skipped']++;
                    continue;
                }

                $customer = new Varien_Object;
                $customer->setName($order->getCustomerName())
                    ->setEmail($order->getCustomerEmail())
                    ->setId($order->getCustomerId());

                // Calculate send date from original invoice date
                $sendAt = $this->_calculateRetroactiveSendDate($autoresponder, $invoice->getCreatedAt());
                
                $this->_insertData($autoresponder, null, $order->getStoreId(), $customer, $invoice->getId(), $sendAt);
                $results['emails_queued']++;
            } catch (Exception $e) {
                Mage::logException($e);
                $results['errors']++;
            }
        }
        
        return $results;
    }

    protected function _processRetroactiveCreditmemos($autoresponder, $targetDate, $nextDay)
    {
        $results = array('items_found' => 0, 'emails_queued' => 0, 'skipped' => 0, 'errors' => 0, 'success' => true);
        
        $creditmemos = Mage::getModel('sales/order_creditmemo')->getCollection()
            ->addAttributeToFilter('created_at', array(
                'from' => $targetDate . ' 00:00:00',
                'to' => $nextDay . ' 00:00:00'
            ));

        $results['items_found'] = $creditmemos->getSize();

        foreach ($creditmemos as $creditmemo) {
            try {
                $order = $creditmemo->getOrder();
                if (!$order || !$order->getId()) {
                    $results['skipped']++; 
                    continue;
                }

                if ($this->_checkIfAlreadyProcessed($autoresponder->getId(), $creditmemo->getId())) {
                    $results['skipped']++;
                    continue;
                }

                $customer = new Varien_Object;
                $customer->setName($order->getCustomerName())
                    ->setEmail($order->getCustomerEmail())
                    ->setId($order->getCustomerId());

                // Calculate send date from original creditmemo date
                $sendAt = $this->_calculateRetroactiveSendDate($autoresponder, $creditmemo->getCreatedAt());
                
                $this->_insertData($autoresponder, null, $order->getStoreId(), $customer, $creditmemo->getId(), $sendAt);
                $results['emails_queued']++;
            } catch (Exception $e) {
                Mage::logException($e);
                $results['errors']++;
            }
        }
        
        return $results;
    }

    protected function _processRetroactiveOrders($autoresponder, $targetDate, $nextDay)
    {
        $results = array('items_found' => 0, 'emails_queued' => 0, 'skipped' => 0, 'errors' => 0, 'success' => true);
        
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('created_at', array(
                'from' => $targetDate . ' 00:00:00',
                'to' => $nextDay . ' 00:00:00'
            ));

        $results['items_found'] = $orders->getSize();

        foreach ($orders as $order) {
            try {
                // For order_product, check if order contains the specific product
                if ($autoresponder->getEvent() == 'order_product' && $autoresponder->getProduct()) {
                    $items = $order->getAllItems();
                    $hasProduct = false;
                    foreach ($items as $item) {
                        if ($item->getProductId() == $autoresponder->getProduct()) {
                            $hasProduct = true;
                            break;
                        }
                    }
                    if (!$hasProduct) {
                        $results['skipped']++;
                        continue;
                    }
                }

                if ($this->_checkIfAlreadyProcessed($autoresponder->getId(), $order->getId())) {
                    $results['skipped']++;
                    continue;
                }

                $customer = new Varien_Object;
                $customer->setName($order->getCustomerName())
                    ->setEmail($order->getCustomerEmail())
                    ->setId($order->getCustomerId());

                // Calculate send date from original order date
                $sendAt = $this->_calculateRetroactiveSendDate($autoresponder, $order->getCreatedAt());
                
                $this->_insertData($autoresponder, null, $order->getStoreId(), $customer, $order->getId(), $sendAt);
                $results['emails_queued']++;
            } catch (Exception $e) {
                Mage::logException($e);
                $results['errors']++;
            }
        }
        
        return $results;
    }

    protected function _processRetroactiveOrderStatus($autoresponder, $targetDate, $nextDay)
    {
        $results = array('items_found' => 0, 'emails_queued' => 0, 'skipped' => 0, 'errors' => 0, 'success' => true);
        
        $targetStatus = $autoresponder->getOrderStatus();
        if (!$targetStatus) {
            return array('success' => false, 'message' => 'No order status specified in autoresponder');
        }
        
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('status', $targetStatus)
            ->addAttributeToFilter('updated_at', array(
                'from' => $targetDate . ' 00:00:00',
                'to' => $nextDay . ' 00:00:00'
            ));

        $results['items_found'] = $orders->getSize();

        foreach ($orders as $order) {
            try {
                if ($this->_checkIfAlreadyProcessed($autoresponder->getId(), $order->getId())) {
                    $results['skipped']++;
                    continue;
                }

                $customer = new Varien_Object;
                $customer->setName($order->getCustomerName())
                    ->setEmail($order->getCustomerEmail())
                    ->setId($order->getCustomerId());

                // Calculate send date from order status change date (updated_at)
                $sendAt = $this->_calculateRetroactiveSendDate($autoresponder, $order->getUpdatedAt());
                
                $this->_insertData($autoresponder, null, $order->getStoreId(), $customer, $order->getId(), $sendAt);
                $results['emails_queued']++;
            } catch (Exception $e) {
                Mage::logException($e);
                $results['errors']++;
            }
        }
        
        return $results;
    }

    protected function _checkIfAlreadyProcessed($autoresponder_id, $dataObjectId)
    {
        $existingEvent = Mage::getModel('followup/events')->getCollection()
            ->addFieldToFilter('autoresponder_id', $autoresponder_id)
            ->addFieldToFilter('data_object_id', $dataObjectId)
            ->getFirstItem();

        return $existingEvent->getId() ? true : false;
    }

}
