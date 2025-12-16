<?php


class YSRTech_Followup_Block_Adminhtml_System_Config_Form_Field_Smtp extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {

        $url = $this->getUrl('*/followup_account/validateSmtp');

        return '<button  onclick="window.location=\'' . $url . '\'" class="scalable" type="button" ><span><span><span>' . Mage::helper('followup')->__('Test SMTP Settings') . '</span></span></span></button>';
    }

}
