<?php

class YSRTech_Followup_Model_Observer
{

    public function addToAutoList($event)
    {

        $order = $event->getEvent()->getOrder();

        try {

            if (!Mage::getStoreConfig('followup/config/auto')) {
                return false;
            }

            Mage::getModel('newsletter/subscriber')->subscribe($order->getCustomerEmail());

        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

}
