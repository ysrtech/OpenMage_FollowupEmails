<?php

class YSRTech_Followup_Block_Adminhtml_Autoresponders_Renderer_Template 
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $templateId = $row->getData($this->getColumn()->getIndex());
        
        if (!$templateId) {
            return '<span style="color: #999;">Not Set</span>';
        }
        
        $template = Mage::getModel('core/email_template')->load($templateId);
        
        if ($template->getId()) {
            return $this->escapeHtml($template->getTemplateCode());
        }
        
        return '<span style="color: #c00;">Template Missing (ID: ' . $templateId . ')</span>';
    }
}
