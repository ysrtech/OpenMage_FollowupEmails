<?php

class YSRTech_Followup_Model_Mysql4_Events_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('followup/events');
    }

}
