<?php

class YSRTech_Followup_ProductsController extends Mage_Core_Controller_Front_Action
{
    public function getAction()
    {
        $subscriber = new Varien_Object();
        $email = $this->getRequest()->getParam('email');
        $uid = $this->getRequest()->getParam('uid');

        $params = $this->getRequest()->getParams();

        $paramsDefault = array();
        $paramsDefault['number_products'] = 10;
        $paramsDefault['title'] = '';
        $paramsDefault['sort_results'] = 'price';
        $paramsDefault['segments'] = 'new';
        $paramsDefault['template'] = 'products';
        $paramsDefault['image_size'] = '120';
        $paramsDefault['columns_count'] = 4;

        $params = array_merge($paramsDefault, $params);


        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $subscriber = Mage::getModel('followup/subscribers')->load($email, 'email');
        }

        if (!$subscriber->getId()) {
            $subscriber = Mage::getModel('followup/subscribers')->load($uid, 'uid');
        }

        Mage::register('followup_subscriber', $subscriber);

        $block = $this->getLayout()->createBlock('YSRTech_Followup_Block_Products', 'followup_products');
        $block->setData('params', $params);

        echo $block->toHtml();

        die();
    }

}
