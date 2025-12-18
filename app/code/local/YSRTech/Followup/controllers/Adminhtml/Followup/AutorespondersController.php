<?php

class YSRTech_Followup_Adminhtml_Followup_AutorespondersController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('followup/autoresponders');

        // Removed Follow-up Emails API key check - no longer required for email-only autoresponders

        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('Email Autoresponders'))->_title($this->__('Autoresponders'));

        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('followup/adminhtml_autoresponders'));
        $this->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->_title($this->__('Follow-up Emails'))->_title($this->__('Email Autoresponders'));

        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('followup/autoresponders')->load($id);
        $model->setData('store_ids', explode(',', $model->getStoreIds()));

        if ($model->getId() || $id == 0) {

            $data = $this->_getSession()->getFormData();

            if (!empty($data)) {
                $model->addData($data);
            }
            Mage::register('current_autoresponder', $model);


            $this->_title($model->getId() ? $model->getName() : $this->__('New'));

            $this->loadLayout();
            $this->_setActiveMenu('followup/autoresponders');

            $this->_addContent($this->getLayout()->createBlock('followup/adminhtml_autoresponders_edit'))
                ->_addLeft($this->getLayout()->createBlock('followup/adminhtml_autoresponders_edit_tabs'));
            $this->renderLayout();
        } else {
            $this->_getSession()->addError($this->__('SMS Notification does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function saveAction()
    {

        if ($this->getRequest()->getPost()) {

            $data = $this->getRequest()->getPost();
            $data = $this->_filterDates($data, array('from_date', 'to_date'));

            $id = $this->getRequest()->getParam('id');

            $data['store_ids'] = implode(',', $data['store_ids']);
            
            // Serialize chain steps if present
            if (isset($data['chain_steps']) && is_array($data['chain_steps'])) {
                // Filter out empty steps and reindex array
                $chainSteps = array_values(array_filter($data['chain_steps'], function($step) {
                    return !empty($step['template_id']) || !empty($step['delay_days']) || !empty($step['delay_hours']);
                }));
                $data['chain_steps'] = serialize($chainSteps);
            }

            $model = Mage::getModel('followup/autoresponders');

            try {
                if ($id) {
                    $model->setId($id);
                }

                if (isset($data['product'])) {
                    $data['product'] = trim($data['product']);
                }

                $model->addData($data);

                if ($model->getData('event') == 'order_product') {
                    $product = Mage::getModel('catalog/product')->load($model->getData('product'));

                    if (!$product->getId()) {
                        throw new Mage_Core_Exception('Product Not Found');
                    }
                }

                $model->save();

                $this->_getSession()->setFormData(false);
                $this->_getSession()->addSuccess($this->__('The SMS Notification has been saved.'));

                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_getSession()->setFormData($data);

                if ($this->getRequest()->getParam('id')) {
                    $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                } else {
                    $this->_redirect('*/*/new');
                }

                return;
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('An error occurred while saving the SMS Notification data. Please review the log and try again.'));
                Mage::logException($e);
                $this->_getSession()->setFormData($data);
                $this->_redirect('*/*/new', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {


        if ($id = $this->getRequest()->getParam('id')) {
            try {

                $model = Mage::getModel('followup/autoresponders');
                $model->load($id);
                $model->delete();

                $this->_getSession()->addSuccess($this->__('The SMS Notification has been deleted.'));
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('An error occurred while deleting the SMS Notification. Please review the log and try again.'));
                Mage::logException($e);
                $this->_redirect('*/*/edit', array('id' => $id));
                return;
            }
        }
        $this->_getSession()->addError($this->__('Unable to find a SMS Notification to delete.'));
        $this->_redirect('*/*/');
    }

    public function validateEnvironmentAction()
    {
        $params = $this->getRequest()->getParams();
        $number = $params['number'];

        if (!Mage::getModel('followup/api')->validateNumber($number)) {
            $this->_getSession()->addError($this->__('Please insert a valid Phone Number xxx-xxxxxx'));
            $this->_redirectReferer();
            return;
        }

        $result = Mage::getModel('followup/api')->send($number, 'Test Message from Magento Store');

        if ($result !== true) {
            $this->_getSession()->addError($this->__('ERROR: Check your settings' . $result));
        } else {
            $this->_getSession()->addSuccess($this->__('Message Sent'));
        }

        $this->_redirectReferer();
    }

    /**
     * Retroactive email processing - display form
     */
    public function retroactiveAction()
    {
        $this->_title($this->__('Email Autoresponders'))->_title($this->__('Send Retroactive Emails'));

        $this->loadLayout();
        $this->_setActiveMenu('followup/autoresponders');
        $this->_addContent($this->getLayout()->createBlock('followup/adminhtml_autoresponders_retroactive'));
        $this->renderLayout();
    }

    /**
     * Process retroactive emails
     */
    public function processRetroactiveAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->_redirect('*/*/retroactive');
            return;
        }

        try {
            $daysAgo = (int)$this->getRequest()->getPost('days_ago');
            $autoresponder_id = (int)$this->getRequest()->getPost('autoresponder_id');

            if ($daysAgo <= 0) {
                throw new Mage_Core_Exception('Please specify a valid number of days (greater than 0)');
            }

            if (!$autoresponder_id) {
                throw new Mage_Core_Exception('Please select an autoresponder');
            }

            $results = Mage::getModel('followup/autoresponders')
                ->processRetroactiveEmails($autoresponder_id, $daysAgo);

            if ($results['success']) {
                $message = $this->__(
                    'Processed %d items from %d days ago. Queued %d emails. Skipped %d (already processed). Errors: %d',
                    $results['items_found'],
                    $daysAgo,
                    $results['emails_queued'],
                    $results['skipped'],
                    $results['errors']
                );
                $this->_getSession()->addSuccess($message);
            } else {
                $this->_getSession()->addError($this->__('Error: %s', $results['message']));
            }

        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while processing retroactive emails.'));
            Mage::logException($e);
        }

        $this->_redirect('*/*/retroactive');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('followup/autoresponders');
    }

}
