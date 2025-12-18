<?php

class YSRTech_Followup_Block_Adminhtml_Autoresponders_Retroactive_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id' => 'retroactive_form',
            'action' => $this->getUrl('*/*/processRetroactive'),
            'method' => 'post'
        ));

        $form->setUseContainer(true);

        $fieldset = $form->addFieldset('retroactive_fieldset', array(
            'legend' => Mage::helper('followup')->__('Retroactive Email Configuration'),
            'class' => 'fieldset-wide'
        ));

        $fieldset->addField('note', 'note', array(
            'text' => Mage::helper('followup')->__('Use this tool to send follow-up emails for past events. Select an autoresponder and specify how many days back to process. The system will use all settings (event type, templates, delays, etc.) from the selected autoresponder.')
        ));

        // Get all active autoresponders
        $autoresponders = Mage::getModel('followup/autoresponders')->getCollection()
            ->addFieldToFilter('active', 1)
            ->setOrder('name', 'ASC');

        $autoresponderOptions = array(
            array('value' => '', 'label' => Mage::helper('followup')->__('-- Select Autoresponder --'))
        );

        $eventLabels = array(
            'order_new' => 'New Order',
            'order_product' => 'Bought Specific Product',
            'order_status' => 'Order Status Change',
            'new_invoice' => 'New Invoice',
            'new_shipment' => 'New Shipment',
            'new_creditmemo' => 'New Creditmemo',
        );

        foreach ($autoresponders as $autoresponder) {
            $eventLabel = isset($eventLabels[$autoresponder->getEvent()]) ? $eventLabels[$autoresponder->getEvent()] : $autoresponder->getEvent();
            $autoresponderOptions[] = array(
                'value' => $autoresponder->getId(),
                'label' => $autoresponder->getName() . ' [' . $eventLabel . '] (ID: ' . $autoresponder->getId() . ')'
            );
        }

        $fieldset->addField('autoresponder_id', 'select', array(
            'label' => Mage::helper('followup')->__('Autoresponder'),
            'name' => 'autoresponder_id',
            'required' => true,
            'values' => $autoresponderOptions,
            'note' => Mage::helper('followup')->__('Select the autoresponder to apply retroactively. All settings will be taken from this autoresponder.')
        ));

        $fieldset->addField('days_ago', 'text', array(
            'label' => Mage::helper('followup')->__('Days Ago'),
            'name' => 'days_ago',
            'required' => true,
            'class' => 'validate-greater-than-zero validate-digits',
            'note' => Mage::helper('followup')->__('Whole days back from today. 1 = events from yesterday, 10 = events from exactly 10 days ago (00:00–23:59 UTC). Must be a positive integer.')
        ));

        $fieldset->addField('warning', 'note', array(
            'text' => '<strong style="color: #ff0000;">' . 
                      Mage::helper('followup')->__('Warning: This will queue emails for ALL matching events on that day (per the selected autoresponder) that have not already been queued. Send times use the autoresponder delay; if the time is already past, it will send as soon as cron runs. Review templates, delays, and filters before proceeding.') . 
                      '</strong>'
        ));

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
