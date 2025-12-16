<?php

class YSRTech_Followup_Block_Adminhtml_Subscribers extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {

        parent::__construct();
        $this->_controller = 'adminhtml_subscribers';
        $this->_blockGroup = 'followup';
        $this->_headerText = $this->__('Subscribers');
        $this->_addButtonLabel = $this->__('Add Subscriber');

    }

}