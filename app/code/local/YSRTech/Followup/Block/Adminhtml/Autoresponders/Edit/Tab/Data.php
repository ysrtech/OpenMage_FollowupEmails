<?php

class YSRTech_Followup_Block_Adminhtml_Autoresponders_Edit_Tab_Data extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {

        $current = Mage::registry('current_autoresponder');

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('page_');

        $event = $this->getRequest()->getParam('event');

        if ($current->getId()) {
            $event = $current->getEvent();
        }


        $fieldset2 = $form->addFieldset('content_fieldset', array('legend' => $this->__('Content')));

        $fieldset2->addField('name', 'text', array(
            'name'     => 'name',
            'label'    => $this->__('Name'),
            'title'    => $this->__('Name'),
            "required" => true,
        ));

        // Add email template selector
        $templates = Mage::getResourceModel('core/email_template_collection')
            ->load()
            ->toOptionArray();
        
        array_unshift($templates, array('value' => '', 'label' => $this->__('-- Please Select Template --')));
        
        $fieldset2->addField('email_template_id', 'select', array(
            'name'     => 'email_template_id',
            'label'    => $this->__('Email Template'),
            'title'    => $this->__('Email Template'),
            'values'   => $templates,
            'required' => true,
            'note'     => $this->__('Select the email template to use for this notification. You can create custom templates in System > Transactional Emails'),
        ));

        $extraMsg = '';
        if ($event == 'new_shipment') {
            $extraMsg = $this->__("You can use template variables like {{var tracking_number}} and {{var tracking_title}} for carrier name and tracking number.");
        }

        // Remove the WYSIWYG editor and SMS message field - not needed for email templates
        // The email template handles all content
        if ($extraMsg) {
            $fieldset2->addField('template_note', 'note', array(
                'label' => $this->__('Template Variables'),
                'text'  => $extraMsg,
            ));
        }


        if (!Mage::app()->isSingleStoreMode()) {
            $field = $fieldset2->addField('store_ids', 'multiselect', array(
                'name'     => 'store_ids[]',
                'label'    => Mage::helper('cms')->__('Store View'),
                'title'    => Mage::helper('cms')->__('Store View'),
                'required' => true,
                'values'   => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
            ));
            $renderer = $this->getLayout()->createBlock('adminhtml/store_switcher_form_renderer_fieldset_element');
            $field->setRenderer($renderer);
        } else {
            $fieldset2->addField('store_ids', 'hidden', array(
                'name'  => 'store_ids[]',
                'value' => Mage::app()->getStore(true)->getId(),
            ));
            $current->setStoreIds(Mage::app()->getStore(true)->getId());
        }

        $outputFormatDate = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $fieldset2->addField('from_date', 'date', array(
            'name'   => 'from_date',
            'format' => $outputFormatDate,
            'label'  => $this->__('Active From Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
        ));
        $fieldset2->addField('to_date', 'date', array(
            'name'   => 'to_date',
            'format' => $outputFormatDate,
            'label'  => $this->__('Active To Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
        ));


        $fieldset2->addField('active', "select", array(
            "label"   => $this->__('Status'),
            "options" => array('1' => $this->__('Active'), '0' => $this->__('Inactive')),
            "name"    => 'active',
        ));

        $this->setForm($form);

        if ($current) {
            $form->addValues($current->getData());
        }

        return parent::_prepareForm();
    }

}
