<?php


class YSRTech_Followup_Block_Adminhtml_System_Config_Form_Field_Test extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {

        $url = $this->getUrl('*/followup_autoresponders/validateEnvironment');

        return '<button  onclick="window.location=\'' . $url . 'number/\'+$F(\'followup_test_number\')" class="scalable" type="button" ><span><span><span>' . Mage::helper('followup')->__('Test Now') . '</span></span></span></button>';
    }

}
