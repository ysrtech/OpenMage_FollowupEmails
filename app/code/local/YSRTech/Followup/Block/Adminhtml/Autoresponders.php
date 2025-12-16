<?php

class YSRTech_Followup_Block_Adminhtml_Autoresponders extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {
        $this->_controller = 'adminhtml_autoresponders';
        $this->_blockGroup = 'followup';
        $this->_headerText = $this->__('Email Autoresponders');
        $this->_addButtonLabel = $this->__('Add New Autoresponder');

        parent::__construct();
    }

}
