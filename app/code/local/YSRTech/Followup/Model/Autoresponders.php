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
        $abandonedHours = Mage::getStoreConfig('followup/config/abandoned_cart_hours');
        if (!$abandonedHours) {
            $abandonedHours = 1; // Default 1 hour
        }

        $from = new Zend_Date();
        $from->subHour($abandonedHours + 24); // Check carts from last 24 hours + threshold

        $to = new Zend_Date();
        $to->subHour($abandonedHours); // Carts older than threshold

        // Get quotes that are abandoned
        $quotes = Mage::getResourceModel('sales/quote_collection')
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('items_count', array('gt' => 0))
            ->addFieldToFilter('customer_email', array('notnull' => true))
            ->addFieldToFilter('customer_email', array('neq' => ''))
            ->addFieldToFilter('updated_at', array(
                'from' => $from->toString('yyyy-MM-dd HH:mm:ss'),
                'to' => $to->toString('yyyy-MM-dd HH:mm:ss')
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
        if ($autoresponder->getSendMoment() == 'occurs') {
            $date = Mage::app()->getLocale()->date()
                ->get(self::MYSQL_DATETIME);
        }

        if ($autoresponder->getSendMoment() == 'after') {
            $date = Mage::app()->getLocale()->date();

            if ($autoresponder->getAfterHours() > 0) {
                $date->addHour($autoresponder->getAfterHours());
            }
            if ($autoresponder->getAfterDays() > 0) {
                $date->addDay($autoresponder->getAfterDays());
            }
            $date->get(self::MYSQL_DATETIME);
        }

        return $date;
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
        $date = Mage::app()->getLocale()->date();

        if ($autoresponder->getSendMoment() == 'now') {
            // Even if "now", apply the step delay
            if (!empty($step['delay_hours'])) {
                $date->addHour($step['delay_hours']);
            }
            if (!empty($step['delay_days'])) {
                $date->addDay($step['delay_days']);
            }
        }

        if ($autoresponder->getSendMoment() == 'after') {
            // Add base delay from autoresponder
            if ($autoresponder->getAfterHours() > 0) {
                $date->addHour($autoresponder->getAfterHours());
            }
            if ($autoresponder->getAfterDays() > 0) {
                $date->addDay($autoresponder->getAfterDays());
            }
            // Add step-specific delay
            if (!empty($step['delay_hours'])) {
                $date->addHour($step['delay_hours']);
            }
            if (!empty($step['delay_days'])) {
                $date->addDay($step['delay_days']);
            }
        }

        return $date->get(self::MYSQL_DATETIME);
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
        $date = Mage::app()->getLocale()->date()->get(self::MYSQL_DATETIME);

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

            // Send via Magento's native email system
            $result = $this->_sendEmail($cron, $autoresponder);

            if ($result === true) {
                $cron->setSent(1)->setSentAt($date)->save();
            }
        }
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
            
            // Send email using Magento's transactional email
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);

            $emailTemplate = Mage::getModel('core/email_template')
                ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId));
            
            // Load template to get HTML content for tracking injection
            $emailTemplate->loadDefault($templateId);
            if (!$emailTemplate->getId()) {
                $emailTemplate->load($templateId);
            }
            
            // Process template with variables
            $emailTemplate->setTemplateFilter(Mage::getModel('core/email_template_filter'));
            $processedTemplate = $emailTemplate->getProcessedTemplate($vars);
            $processedSubject = $emailTemplate->getProcessedTemplateSubject($vars);
            
            // Inject tracking into HTML (pixel + link wrapping)
            $processedTemplate = Mage::helper('followup')->injectEmailTracking(
                $processedTemplate, 
                $cron->getId(), 
                $storeId
            );
            
            // Send the email
            $mail = $emailTemplate->getMail();
            $mail->addTo($recipientEmail, $recipientName);
            $mail->setSubject($processedSubject);
            $mail->setFrom($sender['email'], $sender['name']);
            
            if ($emailTemplate->isPlain()) {
                $mail->setBodyText($processedTemplate);
            } else {
                $mail->setBodyHtml($processedTemplate);
            }
            
            $mail->send();

            $translate->setTranslateInline(true);

            return true;

        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }
    }

    protected function _insertData($autoresponder, $number, $storeId, $customer, $dataObjectId = null)
    {

        $storeIds = explode(',', $autoresponder->getStoreIds());

        if (!in_array($storeId, $storeIds) && !in_array(0,$storeIds)) {
            return false;
        }

        $data = array();
        $data['send_at'] = $this->calculateSendDate($autoresponder);
        $data['autoresponder_id'] = $autoresponder->getId();
        $data['cellphone'] = $number;
        $data['customer_id'] = $customer->getId();
        $data['customer_name'] = $customer->getName();
        $data['customer_email'] = $customer->getEmail();
        $data['event'] = $autoresponder->getEvent();
        $data['created_at'] = new Zend_Db_Expr('NOW()');
        $data['sent'] = 0;
        $data['data_object_id'] = $dataObjectId;

        Mage::getModel('followup/events')->setData($data)->save();
        $autoresponder->setData('number_subscribers', $autoresponder->getData('number_subscribers') + 1)->save();
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

        $date = Mage::app()->getLocale()->date()->get(self::MYSQL_DATE);
        //Version Compatability
        $return = $this->getCollection()
            ->addFieldToFilter('active', 1);

        $return->getSelect()
            ->where(" FIND_IN_SET('0', store_ids) OR FIND_IN_SET(?, store_ids)", $storeId)
            ->where(" from_date <=? or from_date IS NULL ", $date)
            ->where(" to_date >=? or to_date IS NULL ", $date);


        return $return;
    }

}
