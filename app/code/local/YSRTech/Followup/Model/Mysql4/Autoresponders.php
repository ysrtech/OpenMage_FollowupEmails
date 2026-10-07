<?php

class YSRTech_Followup_Model_Mysql4_Autoresponders extends Mage_Core_Model_Resource_Db_Abstract {

    public function _construct() {
        $this->_init('followup/autoresponders', 'autoresponder_id');
    }

}
