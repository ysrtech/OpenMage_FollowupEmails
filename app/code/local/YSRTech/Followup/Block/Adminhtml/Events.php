<?php

class YSRTech_Followup_Block_Adminhtml_Events extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {
        $this->_controller = 'adminhtml_events';
        $this->_blockGroup = 'followup';
        $this->_headerText = $this->__('Notifications Logs');

        parent::__construct();

        $this->_removeButton('add');
    }

}
