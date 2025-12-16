<?php

class YSRTech_Followup_Model_Email extends Mage_Core_Model_Email
{


    public function send()
    {
        $storeId = Mage::app()->getStore()->getId();

        if (!Mage::getStoreConfigFlag('followup/transactional/enable', $storeId)) {
            return parent::send();
        }

        if (Mage::getStoreConfigFlag('system/smtp/disable')) {
            return $this;
        }

        $mail = new Zend_Mail();

        if (strtolower($this->getType()) == 'html') {
            $mail->setBodyHtml($this->getBody());
        } else {
            $mail->setBodyText($this->getBody());
        }

        $mail->setFrom($this->getFromEmail(), $this->getFromName())
            ->addTo($this->getToEmail(), $this->getToName())
            ->setSubject($this->getSubject())
            ->setReplyTo($this->getSenderEmail(), $this->getSenderName());

        // Use Magento's default transport
        $mail->send();

        return $this;
    }

}
