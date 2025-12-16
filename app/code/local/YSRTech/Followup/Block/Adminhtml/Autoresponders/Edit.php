<?php

class YSRTech_Followup_Block_Adminhtml_Autoresponders_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {

    public function __construct() {
        parent::__construct();
        $this->_objectId = "autoresponder_id";
        $this->_blockGroup = "followup";
        $this->_controller = "adminhtml_autoresponders";

        $this->_addButton("saveandcontinuebarcode", array(
            "label" => $this->__("Save and Continue Edit"),
            "onclick" => "saveAndContinueEdit()",
            "class" => "save",
                ), -100);

        $this->_formScripts[] = " function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }";
    }

    public function getHeaderText() {

        if (Mage::registry("current_autoresponder") && Mage::registry("current_autoresponder")->getId()) {

            return $this->__("Edit Notification");
        } else {

            return $this->__("Add Notification");
        }
    }

}
