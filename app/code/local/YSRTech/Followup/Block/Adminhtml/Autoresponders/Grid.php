<?php

class YSRTech_Followup_Block_Adminhtml_Autoresponders_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('campaign_grid');
        $this->setDefaultSort('autoresponder_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('followup/autoresponders')
                ->getResourceCollection();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('autoresponder_id', array(
            'header' => $this->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'autoresponder_id',
        ));


        $this->addColumn('event', array(
            'header' => $this->__('Event'),
            'align' => 'left',
            'index' => 'event',
            'type' => 'options',
            'options' => Mage::getModel('followup/autoresponders')->toOptionArray(),
        ));


        $this->addColumn('name', array(
            'header' => $this->__('Name'),
            'align' => 'left',
            'index' => 'name',
        ));

        // Add email template column
        $templates = Mage::getResourceModel('core/email_template_collection')->toOptionHash();
        $this->addColumn('email_template_id', array(
            'header' => $this->__('Email Template'),
            'align' => 'left',
            'index' => 'email_template_id',
            'type' => 'options',
            'options' => $templates,
            'renderer' => 'YSRTech_Followup_Block_Adminhtml_Autoresponders_Renderer_Template',
        ));

        $this->addColumn('number_subscribers', array(
            'header' => $this->__('Emails Sent'),
            'align' => 'right',
            'type' => 'number',
            'index' => 'number_subscribers',
        ));

        $this->addColumn('active', array(
            'header' => $this->__('Status'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'active',
            'type' => 'options',
            'options' => array('0' => $this->__('Inactive'), '1' => $this->__('Active')),
        ));

        $this->addColumn('from_date', array(
            'header' => $this->__('Active From'),
            'align' => 'left',
            'width' => '120px',
            'type' => 'date',
            'default' => '--',
            'index' => 'from_date',
        ));

        $this->addColumn('to_date', array(
            'header' => $this->__('Active To'),
            'align' => 'left',
            'width' => '120px',
            'type' => 'date',
            'default' => '--',
            'index' => 'to_date',
        ));
        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}
