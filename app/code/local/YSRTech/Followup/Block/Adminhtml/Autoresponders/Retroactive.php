<?php

class YSRTech_Followup_Block_Adminhtml_Autoresponders_Retroactive extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        
        $this->_objectId = 'id';
        $this->_blockGroup = 'followup';
        $this->_controller = 'adminhtml_autoresponders';
        $this->_mode = 'retroactive';
        
        $this->_updateButton('save', 'label', Mage::helper('followup')->__('Process Retroactive Emails'));
        // Use the form DOM id directly; no global variable needed
        $this->_updateButton('save', 'onclick', "document.getElementById('retroactive_form').submit();");
        $this->_removeButton('delete');
        $this->_removeButton('back');
        $this->_removeButton('reset');
    }

    public function getHeaderText()
    {
        return Mage::helper('followup')->__('Send Retroactive Follow-up Emails');
    }

    public function getFormActionUrl()
    {
        return $this->getUrl('*/*/processRetroactive');
    }
}
