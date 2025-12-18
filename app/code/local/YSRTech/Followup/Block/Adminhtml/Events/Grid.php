<?php

class YSRTech_Followup_Block_Adminhtml_Events_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('events_grid');
        $this->setDefaultSort('event_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('followup/events')
            ->getResourceCollection();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('event_id', array(
            'header' => $this->__('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'event_id',
        ));
        $this->addColumn('event', array(
            'header'  => $this->__('Event'),
            'index'   => 'event',
            'type'    => 'options',
            'options' => Mage::getModel('followup/autoresponders')->toOptionArray(),
        ));

        $this->addColumn('autoresponder_id', array(
            'header'  => $this->__('Notification'),
            'index'   => 'autoresponder_id',
            'type'    => 'options',
            'options' => Mage::getModel('followup/autoresponders')->toFormValues(),
        ));

        $this->addColumn('customer_id', array(
            'header'         => $this->__('Customer ID'),
            'index'          => 'customer_id',
            'frame_callback' => array($this, 'customerResult'),
        ));

        $this->addColumn('customer_name', array(
            'header' => $this->__('Customer Name'),
            'index'  => 'customer_name',
        ));

        $this->addColumn('customer_email', array(
            'header' => $this->__('Customer Email'),
            'index'  => 'customer_email',
        ));

        $this->addColumn('created_at', array(
            'header' => $this->__('Created At'),
            'index'  => 'created_at',
            'type'   => 'datetime',
            'width'  => '170px',
        ));

        $this->addColumn('send_at', array(
            'header' => $this->__('Send At'),
            'index'  => 'send_at',
            'type'   => 'datetime',
            'width'  => '170px',
        ));

        $this->addColumn('sent_at', array(
            'header' => $this->__('Sent At'),
            'index'  => 'sent_at',
            'type'   => 'datetime',
            'width'  => '170px',
        ));

        $this->addColumn('sent', array(
            'header'  => $this->__('Sent?'),
            'align'   => 'left',
            'width'   => '80px',
            'index'   => 'sent',
            'type'    => 'options',
            'options' => array('0' => $this->__('No'), '1' => $this->__('Yes')),
        ));

        $this->addColumn('cancelled', array(
            'header'  => $this->__('Cancelled?'),
            'align'   => 'left',
            'width'   => '80px',
            'index'   => 'cancelled',
            'type'    => 'options',
            'options' => array('0' => $this->__('No'), '1' => $this->__('Yes')),
        ));

        // Only show tracking columns if Mailgun is NOT active
        if (!Mage::helper('followup')->isMailgunActive()) {
            $this->addColumn('opened', array(
                'header'  => $this->__('Opened?'),
                'align'   => 'left',
                'width'   => '80px',
                'index'   => 'opened',
                'type'    => 'options',
                'options' => array('0' => $this->__('No'), '1' => $this->__('Yes')),
            ));

            $this->addColumn('clicked', array(
                'header'  => $this->__('Clicked?'),
                'align'   => 'left',
                'width'   => '80px',
                'index'   => 'clicked',
                'type'    => 'options',
                'options' => array('0' => $this->__('No'), '1' => $this->__('Yes')),
            ));
        } else {
            // Show Mailgun tracking link column if Mailgun is active
            $this->addColumn('mailgun_tracking', array(
                'header'         => $this->__('Mailgun Tracking'),
                'align'          => 'center',
                'width'          => '100px',
                'filter'         => false,
                'sortable'       => false,
                'frame_callback' => array($this, 'renderMailgunLink'),
            ));
        }

        $this->addExportType('*/*/exportCsv', $this->__('CSV'));
        $this->addExportType('*/*/exportXml', $this->__('Excel XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('event_id');
        $this->getMassactionBlock()->setFormFieldName('events');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => $this->__('Delete'),
            'url'     => $this->getUrl('*/followup_events/massDelete'),
            'confirm' => Mage::helper('customer')->__('Are you sure?'),
        ));

        return $this;
    }

    public function customerResult($value)
    {

        if ((int)$value > 0) {
            $url = $this->getUrl('/customer/edit', array('id' => $value));
            return '<a href="' . $url . '">' . $this->__('Yes') . '</a>';
        }

        return $this->__('No');
    }

    /**
     * Render Mailgun tracking link
     *
     * @param Varien_Object $row
     * @return string
     */
    public function renderMailgunLink($value, $row)
    {
        // Only show link if email was sent
        if (!$row->getSent()) {
            return $this->__('Not Sent');
        }

        // Check if we have a saved Mailgun email ID
        $mailgunEmailId = $row->getMailgunEmailId();
        
        if ($mailgunEmailId) {
            $url = Mage::helper('adminhtml')->getUrl('adminhtml/emailtracking/emaildetail', array(
                'id' => $mailgunEmailId
            ));
            return '<a href="' . $url . '" target="_blank">' . $this->__('View Tracking') . '</a>';
        }

        return $this->__('N/A');
    }

}
