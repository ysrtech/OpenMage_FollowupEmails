<?php

class YSRTech_Followup_Model_Mysql4_Events extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('followup/events', 'event_id');
    }

}
