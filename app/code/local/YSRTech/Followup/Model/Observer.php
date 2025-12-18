<?php

class YSRTech_Followup_Model_Observer
{

    // Auto-subscribe feature removed; method left intentionally blank to avoid legacy calls causing errors.
    public function addToAutoList($event)
    {
        return false;
    }

    /**
     * Capture Mailgun email ID after email is saved
     * Triggered by freelunchlabs_mailgun_email_save_after event
     * Only processes emails sent by followup module when Mailgun is active
     *
     * @param Varien_Event_Observer $observer
     */
    public function captureMailgunEmailId($observer)
    {
        try {
            // Only proceed if Mailgun is active
            if (!Mage::helper('followup')->isMailgunActive()) {
                return;
            }
            
            // Check if this is a followup email (registry will be set)
            $followupEventId = Mage::registry('followup_current_event_id');
            if (!$followupEventId) {
                return; // Not a followup email, ignore
            }
            
            $mailgunEmail = $observer->getEvent()->getDataObject();
            
            if ($mailgunEmail && $mailgunEmail->getId()) {
                // Store the Mailgun email ID in registry for the send method to retrieve
                Mage::register('followup_mailgun_email_id_' . $followupEventId, $mailgunEmail->getId());
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

}
