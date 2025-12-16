<?php

class YSRTech_Followup_Adminhtml_Followup_EventsController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu('followup/events');

        return $this;
    }

    public function indexAction() {

        $this->_title($this->__('Email Autoresponders'))->_title($this->__('Autoresponders Log'));

        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('followup/adminhtml_events'));
        $this->renderLayout();
    }

    public function gridAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function massDeleteAction() {
        $changes = $this->getRequest()->getParam('events');
        if (!is_array($changes)) {
            $this->_getSession()->addError($this->__('Please select event(s).'));
        } else {
            try {
                foreach ($changes as $record) {
                    Mage::getModel('followup/events')->load($record)->delete();
                }
                $this->_getSession()->addSuccess($this->__('Total of %d record(s) were deleted.', count($changes)));
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirectReferer();
    }

    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction() {
        $fileName = 'events.csv';
        $content = $this->getLayout()->createBlock('followup/adminhtml_events_grid')
                ->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export customer grid to XML format
     */
    public function exportXmlAction() {
        $fileName = 'events.xml';
        $content = $this->getLayout()->createBlock('followup/adminhtml_events_grid')
                ->getExcelFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('followup/events');
    }

}
