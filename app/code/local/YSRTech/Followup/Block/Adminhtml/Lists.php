<?php

class YSRTech_Followup_Block_Adminhtml_Lists extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_controller = 'adminhtml_lists';
        $this->_blockGroup = 'followup';
        $this->_headerText = $this->__('Lists');
        $this->_addButtonLabel = $this->__('Add List');
        parent::__construct();

        $this->_removeButton('add');
    }

}