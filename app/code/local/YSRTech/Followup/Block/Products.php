<?php

class YSRTech_Followup_Block_Products extends Mage_Catalog_Block_Product_Abstract implements Mage_Widget_Block_Interface
{

    protected function _toHtml()
    {

        $params = $this->getData('params');

        $this->setTemplate('followup/' . $params['template'] . '.phtml');

        $model = Mage::getModel('followup/products')->getWidget($params);

        $this->setData('product_collection', $model);
        $this->setData('title', $params['title']);

        return parent::_toHtml();
    }

}
