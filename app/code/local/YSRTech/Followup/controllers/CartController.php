<?php

class YSRTech_Followup_CartController extends Mage_Core_Controller_Front_Action
{
    /**
     * Recover abandoned cart from email link
     */
    public function recoverAction()
    {
        $quoteId = $this->getRequest()->getParam('quote');
        $token = $this->getRequest()->getParam('token');

        if (!$quoteId || !$token) {
            Mage::getSingleton('core/session')->addError($this->__('Invalid recovery link.'));
            $this->_redirect('checkout/cart');
            return;
        }

        try {
            $quote = Mage::getModel('sales/quote')->load($quoteId);

            if (!$quote->getId() || !$quote->getIsActive()) {
                Mage::getSingleton('core/session')->addError($this->__('This cart is no longer available.'));
                $this->_redirect('checkout/cart');
                return;
            }

            // Verify token
            $expectedToken = md5($quote->getId() . $quote->getCustomerEmail() . $quote->getData('recovery_token'));
            if ($token !== $quote->getData('recovery_token')) {
                Mage::getSingleton('core/session')->addError($this->__('Invalid recovery link.'));
                $this->_redirect('checkout/cart');
                return;
            }

            // Restore quote to session
            $checkout = Mage::getSingleton('checkout/session');
            $checkout->replaceQuote($quote);
            
            Mage::getSingleton('core/session')->addSuccess(
                $this->__('Your cart has been restored! Complete your purchase now.')
            );

            $this->_redirect('checkout/cart');
            
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('core/session')->addError($this->__('Unable to recover your cart. Please try again.'));
            $this->_redirect('checkout/cart');
        }
    }
}
