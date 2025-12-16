<?php

class YSRTech_Followup_Block_Adminhtml_Autoresponders_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {

    public function __construct() {
        parent::__construct();
        $this->setId("followup_tabs");
        $this->setDestElementId("edit_form");
        $this->setTitle($this->__("Email Autoresponders Information"));
    }

    protected function _beforeToHtml() {

        $current = Mage::registry('current_autoresponder');


        $this->addTab("main_section", array(
            "label" => $this->__("Settings"),
            "title" => $this->__("Settings"),
            "content" => $this->getLayout()->createBlock("followup/adminhtml_autoresponders_edit_tab_main")->toHtml(),
        ));

        if (($this->getRequest()->getparam('send_moment') || $current->getId())) {
            $this->addTab("data_section", array(
                "label" => $this->__("Information"),
                "title" => $this->__("Information"),
                "content" => $this->getLayout()->createBlock("followup/adminhtml_autoresponders_edit_tab_data")->toHtml(),
            ));
            
            $this->addTab("chain_section", array(
                "label" => $this->__("Email Chain"),
                "title" => $this->__("Email Chain"),
                "content" => $this->getLayout()->createBlock("followup/adminhtml_autoresponders_edit_tab_chain")->toHtml(),
            ));

        }

        return parent::_beforeToHtml();
    }

}
