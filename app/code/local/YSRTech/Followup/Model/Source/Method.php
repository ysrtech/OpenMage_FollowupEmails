<?php

class YSRTech_Followup_Model_Source_Method
{

    public function toOptionArray()
    {
        $return = array();
        $return[] = array('value' => 'transactional', 'label' => 'Transactional API');
        $return[] = array('value' => 'campaign', 'label' => 'Campaign API');

        return $return;
    }

}
