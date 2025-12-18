<?php

class YSRTech_Followup_CallbackController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {

        $remove = $this->getRequest()->getPost('removeSubscriber');
        $add = $this->getRequest()->getPost('addSubscriber');
        $data = isset($remove) ? $remove : $add;
        $data = $this->_object2array(simplexml_load_string($data));

        if (!is_array($data)) {
            return;
        }
        $data = array_change_key_case($data);

        foreach ($data as $key => $value) {
            if (is_array($value) && count($value) == 0) {
                unset($data[$key]);
            }
        }

        $data['inCron'] = true;
        $data['inCallback'] = true;

        $newletter = Mage::getModel('followup/subscribers');
        try {
            if ($add) {
                $newletter->setData($data['email'])->save();
            }

            if ($remove) {
                #$newletter->loadByEmail($data['email'])->unsubscribe();
            }
        } catch (Exception $e) {

        }

    }

    protected function _object2array($data)
    {
        if (!is_object($data) && !is_array($data)) {
            return $data;
        }

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        return array_map(array($this, '_object2array'), $data);
    }

    /**
     * Track email open via 1x1 pixel
     */
    public function trackAction()
    {
        $eventId = $this->getRequest()->getParam('id');
        
        if ($eventId) {
            try {
                // Decrypt event ID
                $eventId = Mage::helper('followup')->decryptEventId($eventId);
                
                $event = Mage::getModel('followup/events')->load($eventId);
                
                if ($event->getId() && !$event->getOpenedAt()) {
                    $event->setOpenedAt(gmdate('Y-m-d H:i:s'))
                        ->save();
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        
        // Output 1x1 transparent GIF
        $this->getResponse()
            ->setHeader('Content-Type', 'image/gif')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setBody(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
    }

    /**
     * Track email click and redirect
     */
    public function clickAction()
    {
        $eventId = $this->getRequest()->getParam('id');
        $url = $this->getRequest()->getParam('url');
        
        if ($eventId && $url) {
            try {
                // Decrypt event ID
                $eventId = Mage::helper('followup')->decryptEventId($eventId);
                $url = base64_decode($url);
                
                $event = Mage::getModel('followup/events')->load($eventId);
                
                if ($event->getId()) {
                    if (!$event->getClickedAt()) {
                        $event->setClickedAt(gmdate('Y-m-d H:i:s'));
                    }
                    $event->setClickCount($event->getClickCount() + 1)
                        ->save();
                }
                
                // Redirect to actual URL
                $this->_redirectUrl($url);
                return;
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        
        // Fallback to homepage if something went wrong
        $this->_redirect('/');
    }

}
