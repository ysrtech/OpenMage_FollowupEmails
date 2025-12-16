<?php

class YSRTech_Followup_Block_Adminhtml_Account extends Mage_Adminhtml_Block_Template
{

    public function __construct()
    {
        parent::__construct();

        $result = Mage::getModel('followup/account')->getAccount();
        
        $this->setTitle($this->__('Account Details'));
        $this->setCompany($result);

        $this->setTemplate('followup/account/account.phtml');
    }

}
