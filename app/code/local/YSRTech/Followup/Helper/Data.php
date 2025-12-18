<?php

class YSRTech_Followup_Helper_Data extends Mage_Core_Helper_Abstract
{

    const XML_PATH_ACTIVE = 'followup/config/active';

    /**
     * Check if Mailgun module is installed and enabled
     *
     * @param mixed $store
     * @return bool
     */
    public function isMailgunActive($store = null)
    {
        // Check if module is installed
        if (!Mage::getConfig()->getModuleConfig('FreeLunchLabs_MailGun')->is('active', 'true')) {
            return false;
        }
        
        // Check if Mailgun is enabled in configuration
        return Mage::getStoreConfigFlag('mailgun/general/active', $store);
    }
    /**
     *
     * @param mixed $store
     *
     * @return bool
     */
    public function isFollowupAvailable($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ACTIVE, $store);
    }


   
    /**
     * Encrypt event ID for tracking URLs
     *
     * @param int $eventId
     * @return string
     */
    public function encryptEventId($eventId)
    {
        return base64_encode(Mage::helper('core')->encrypt($eventId));
    }

    /**
     * Decrypt event ID from tracking URLs
     *
     * @param string $encrypted
     * @return int
     */
    public function decryptEventId($encrypted)
    {
        return Mage::helper('core')->decrypt(base64_decode($encrypted));
    }

    /**
     * Inject tracking pixel and wrap links in email HTML
     *
     * @param string $html Email HTML content
     * @param int $eventId Event ID for tracking
     * @param int $storeId Store ID for URL generation
     * @return string Modified HTML with tracking
     */
    public function injectEmailTracking($html, $eventId, $storeId)
    {
        // Skip tracking if Mailgun module is active (it handles tracking)
        if ($this->isMailgunActive($storeId)) {
            return $html;
        }

        // Check if tracking is enabled
        if (!Mage::getStoreConfigFlag('followup/config/enable_tracking', $storeId)) {
            return $html;
        }

        $encryptedId = $this->encryptEventId($eventId);

        // 1. Inject tracking pixel before closing </body> tag
        $trackingPixelUrl = Mage::getUrl('track/callback/track', array(
            'id' => $encryptedId,
            '_store' => $storeId,
            '_nosid' => true
        ));
        
        $trackingPixel = '<img src="' . $trackingPixelUrl . '" width="1" height="1" alt="" style="display:none;" />';
        
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $trackingPixel . '</body>', $html);
        } else {
            $html .= $trackingPixel;
        }

        // 2. Wrap all links with click tracking
        $html = preg_replace_callback(
            '/<a\s+([^>]*?)href=["\']([^"\']+)["\']([^>]*?)>/i',
            function($matches) use ($encryptedId, $storeId) {
                $beforeHref = $matches[1];
                $originalUrl = $matches[2];
                $afterHref = $matches[3];

                // Skip if it's a mailto: or tel: link
                if (preg_match('/^(mailto|tel|#):/i', $originalUrl)) {
                    return $matches[0];
                }

                // Skip if it's an unsubscribe or view-in-browser link
                if (stripos($originalUrl, 'unsubscribe') !== false || 
                    stripos($originalUrl, 'view') !== false) {
                    return $matches[0];
                }

                // Create tracking URL
                $trackingUrl = Mage::getUrl('track/callback/click', array(
                    'id' => $encryptedId,
                    'url' => base64_encode($originalUrl),
                    '_store' => $storeId,
                    '_nosid' => true
                ));

                return '<a ' . $beforeHref . 'href="' . $trackingUrl . '"' . $afterHref . '>';
            },
            $html
        );

        return $html;
    }

}
