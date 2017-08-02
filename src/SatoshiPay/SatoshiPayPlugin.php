<?php
/**
 * This file is part of the SatoshiPay WordPress plugin.
 *
 * (c) SatoshiPay <hello@satoshipay.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SatoshiPay;

require_once __DIR__ . '/Plugin/PluginAbstract.php';
require_once __DIR__ . '/../../lib/satoshipay/src/Receipt.php';
require_once __DIR__ . '/../../lib/satoshipay/src/Http/Response/File.php';
require_once __DIR__ . '/../../lib/satoshipay/src/Type.php';

use SatoshiPay\Plugin\PluginAbstract;
use SatoshiPay\Receipt;
use SatoshiPay\Http\Response\File as FileResponse;
use SatoshiPay\Type as SatoshiPayType;

class SatoshiPayPlugin extends PluginAbstract
{
    const START_TAG = '<!--satoshipay:start-->';

    /**
     * Get singleton instance.
     *
     * @return $this
     */
    public static function getInstance($mainPluginFile)
    {
        if (null === self::$instance) {
            self::$instance = new self($mainPluginFile);
        }

        return self::$instance;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        load_plugin_textdomain($this->name, false, WP_PLUGIN_DIR . '/' . $this->name . '/languages/' );
        add_action('template_redirect', array($this, 'onTemplateRedirect'), 0);
        add_filter('query_vars', array($this, 'onQueryVars'), 0);
    }

    public function onQueryVars($vars)
    {
        $vars[] = 'satoshipay_content_id';
        $vars[] = 'paymentCert';
        $vars[] = 'paymentReceipt';

        return $vars;
    }

    public function onTemplateRedirect()
    {
        global $wp_query;

        if (!$wp_query->get('satoshipay_content_id')) {
            // Add content filter for SatoshiPay placeholders
            add_filter('the_content', array($this, 'onTheContent'), 99);

            return;
        }

        // Handle raw content request (SatoshiPay HTTP endpoint)
        $this->handleContentRequest();
    }

    protected function validateReceipt($receipt, $secret)
    {
        $receipt = new Receipt($receipt, $secret);

        return $receipt->isValid();
    }

    public function handleContentRequest()
    {
        global $wp_query;

        $contentId = (int)$wp_query->get('satoshipay_content_id');
        if (!$contentId) {
            status_header(400);
            die('Error: Content ID missing');
        }

        $cert = $wp_query->get('paymentCert');
        $receipt = $wp_query->get('paymentReceipt');
        if (!$cert && !$receipt) {
            status_header(402);
            die('Error: Payment receipt missing');
        }

        global $post;
        $post = get_post($contentId);
        if ($post === null) {
            status_header(404);
            die('Error: Could not find object');
        }

        $secret = get_post_meta($post->ID, '_satoshipay_secret', true);

        if ($this->validateReceipt($receipt, $secret) || $cert === $secret) {
            return $this->sendContent($post);
        }

        status_header(402);
        die('Error: Invalid payment receipt');
    }

    public function sendContent()
    {
        global $post;

        $type = SatoshiPayType::fromMimeType($post->post_mime_type);

        $attachmentTypes = array(
            SatoshiPayType::AUDIO,
            SatoshiPayType::DOWNLOAD,
            SatoshiPayType::IMAGE,
            SatoshiPayType::VIDEO
        );

        if (in_array($type, $attachmentTypes)) {
            return $this->sendAttachmentContent();
        }

        return $this->sendTextContent();
    }

    public function sendAttachmentContent()
    {
        global $post;

        $response = new FileResponse(
            get_attached_file($post->ID),
            $post->post_mime_type,
            $_SERVER['HTTP_RANGE']
        );
        $response->send();

        exit;
    }

    public function sendTextContent()
    {
        global $post;

        $paidContent = self::getPaidContent($post->post_content);
        $content = apply_filters('the_content', $paidContent);

        die($content);
    }

    /**
     * @param string $content
     * @return string
     */
    public function onTheContent($content)
    {
        global $post;

        if ($post->post_type == 'attachment') {
            return $this->onAttachmentContent($content);
        }

        return $this->onPostContent($content);
    }

    public function onAttachmentContent($content)
    {
        global $post;

        $placeholder = self::getPlaceholder($post);
        if ($placeholder === false) {
            return $content;
        }

        return $placeholder . self::scriptTag();
    }

    public static function getPricingData($post)
    {
        $satoshipayId = get_post_meta($post->ID, '_satoshipay_id', true);
        $pricing = get_post_meta($post->ID, '_satoshipay_pricing', true);

        if (!$satoshipayId || empty($pricing) || !isset($pricing['enabled']) || $pricing['enabled'] !== true) {
            return false;
        }

        $price = '';
        if (isset($pricing['satoshi'])) {
            $price = (int) $pricing['satoshi'];
        }

        return array(
            'price' => $price,
            'satoshipayId' => $satoshipayId
        );
    }

    public static function scriptTag()
    {
        return '<script type="text/javascript" src="' . SATOSHIPAY_CLIENT_URL . '"></script>';
    }

    public static function getPlaceholderById($postId, array $attributes = array())
    {
        $post = get_post($postId);
        if (!$post) {
            return false;
        }

        return self::getPlaceholder($post, $attributes);
    }

    public static function getPlaceholder($post, array $attributes = array())
    {
        if ($post->post_type !== 'attachment') {
            return false;
        }

        $pricing = self::getPricingData($post);
        if ($pricing === false) {
            return false;
        }

        $goodData = self::getAttachmentData($post);

        switch (SatoshiPayType::fromMimeType($post->post_mime_type)) {
            case SatoshiPayType::AUDIO:
                $placeholder = self::placeholderAudio($pricing, $goodData, $attributes);
                break;
            case SatoshiPayType::DOWNLOAD:
                $placeholder = self::placeholderDownload($pricing, $goodData, $attributes);
                break;
            case SatoshiPayType::IMAGE:
                $placeholder = self::placeholderImage($pricing, $goodData, $attributes);
                break;
            case SatoshiPayType::VIDEO:
                $placeholder = self::placeholderVideo($pricing, $goodData, $attributes);
                break;
            default:
                return false;
        }

        return $placeholder;
    }

    public static function getAttachmentData($post)
    {
        $metadata = wp_get_attachment_metadata($post->ID);
        $contentUrl = site_url() . '/?satoshipay_content_id=' . $post->ID;
        $fileSize = filesize(get_attached_file($post->ID));

        return array(
            'contentUrl' => $contentUrl,
            'metadata' => $metadata,
            'mimeType' => $post->post_mime_type,
            'size' => $fileSize,
            'title' => $post->post_title
        );
    }

    public static function placeholderText($pricing, $goodData)
    {
        return str_replace(
            array(
                '{{content_url}}',
                '{{length}}',
                '{{satoshipay_id}}',
                '{{price}}'
            ),
            array(
                $goodData['contentUrl'],
                $goodData['length'],
                $pricing['satoshipayId'],
                $pricing['price']
            ),
            '<div class="satoshipay-placeholder" data-sp-type="text/html" data-sp-src="{{content_url}}" data-sp-id="{{satoshipay_id}}" data-sp-price="{{price}}" data-sp-length="{{length}}"></div>'
        );
    }

    public static function placeholderAudio($pricing, $goodData, array $attributes = array())
    {
        return str_replace(
            array(
                '{{content_url}}',
                '{{mime_type}}',
                '{{satoshipay_id}}',
                '{{price}}',
                '{{size}}',
                '{{title}}',
                '{{autoplay}}',
            ),
            array(
                $goodData['contentUrl'],
                $goodData['mimeType'],
                $pricing['satoshipayId'],
                $pricing['price'],
                $goodData['size'],
                $goodData['title'],
                $attributes['autoplay']
            ),
            '<div class="satoshipay-placeholder-audio" data-sp-type="{{mime_type}}" data-sp-src="{{content_url}}" data-sp-id="{{satoshipay_id}}" data-sp-price="{{price}}" data-sp-length="{{size}}" data-sp-title="{{title}}" data-sp-autoplay="{{autoplay}}"></div>'
        );
    }

    public static function placeholderDownload($pricing, $goodData, array $attributes = array())
    {
        return str_replace(
            array(
                '{{content_url}}',
                '{{mime_type}}',
                '{{satoshipay_id}}',
                '{{price}}',
                '{{size}}',
                '{{title}}'
            ),
            array(
                $goodData['contentUrl'],
                $goodData['mimeType'],
                $pricing['satoshipayId'],
                $pricing['price'],
                $goodData['size'],
                $goodData['title']
            ),
            '<div class="satoshipay-placeholder-download" data-sp-type="{{mime_type}}" data-sp-src="{{content_url}}" data-sp-id="{{satoshipay_id}}" data-sp-price="{{price}}" data-sp-length="{{size}}" data-sp-title="{{title}}"></div>'
        );
    }

    public static function placeholderImage($pricing, $goodData, array $attributes = array())
    {
        // Allow size override
        $goodWidth = $attributes['width'] ? $attributes['width'] : $goodData['metadata']['width'];
        $goodHeight = $attributes['height'] ? $attributes['height'] : $goodData['metadata']['height'];

        return str_replace(
            array(
                '{{content_url}}',
                '{{mime_type}}',
                '{{satoshipay_id}}',
                '{{width}}',
                '{{height}}',
                '{{price}}',
                '{{preview}}',
            ),
            array(
                $goodData['contentUrl'],
                $goodData['mimeType'],
                $pricing['satoshipayId'],
                $goodWidth,
                $goodHeight,
                $pricing['price'],
                $attributes['preview'],
            ),
            '<div class="satoshipay-placeholder-image" data-sp-type="{{mime_type}}" data-sp-src="{{content_url}}" data-sp-id="{{satoshipay_id}}" data-sp-width="{{width}}" data-sp-height="{{height}}" data-sp-price="{{price}}" data-sp-placeholder="{{preview}}"></div>'
        );
    }

    public static function placeholderVideo($pricing, $goodData, array $attributes = array())
    {
        // Allow size override
        $goodWidth = $attributes['width'] ? $attributes['width'] : $goodData['metadata']['width'];
        $goodHeight = $attributes['height'] ? $attributes['height'] : $goodData['metadata']['height'];

        return str_replace(
            array(
                '{{content_url}}',
                '{{mime_type}}',
                '{{satoshipay_id}}',
                '{{width}}',
                '{{height}}',
                '{{price}}',
                '{{autoplay}}',
                '{{preview}}',
            ),
            array(
                $goodData['contentUrl'],
                $goodData['mimeType'],
                $pricing['satoshipayId'],
                $goodWidth,
                $goodHeight,
                $pricing['price'],
                $attributes['autoplay'],
                $attributes['preview'],
            ),
            '<div class="satoshipay-placeholder-video" data-sp-type="{{mime_type}}" data-sp-src="{{content_url}}" data-sp-id="{{satoshipay_id}}" data-sp-width="{{width}}" data-sp-height="{{height}}" data-sp-price="{{price}}" data-sp-autoplay="{{autoplay}}" data-sp-placeholder="{{preview}}"></div>'
        );
    }

    public static function replaceMediaTags($content)
    {
        return preg_replace_callback(
            '/<!--satoshipay:(image|audio|video|download).*attachment-id="(\d+)"(.*height="(\d+)")?(.*width="(\d+)")?(.*autoplay="(true|false)")?(.*preview="(.*)")?-->/',
            function ($matches) {
                $attachmentId = $matches[2];
                $attachmentAttributes = array(
                    'height' => $matches[4],
                    'width' => $matches[6],
                    'autoplay' => $matches[8],
                    'preview' => $matches[10],
                );
                $placeholder = SatoshiPayPlugin::getPlaceholderById($attachmentId, $attachmentAttributes);
                if ($placeholder) {
                    return $placeholder . SatoshiPayPlugin::scriptTag();
                }

                return $matches[0];
            },
            $content
        );
    }

    public static function replaceMediaTagsAboveStartTag($content)
    {
        // split content into two parts at the first occurence of start tag
        $contentArray = explode(self::START_TAG, $content, 2);

        if(count($contentArray) > 1){
            // there was a start tag - we replace only madia tags above start tag
            return self::replaceMediaTags($contentArray[0]) . self::START_TAG . $contentArray[1];
        } else {
            // there was no start tag - we return everything with mediatags replaced
            return self::replaceMediaTags($content);
        }

        return;
    }

    public function onPostContent($content)
    {
        global $post;

        $pricing = self::getPricingData($post);

        $adBlockerBaitCode = '';

        if (SATOSHIPAY_USE_AD_BLOCKER_DETECTION && $pricing === false) {
            $adBlockerDetectionOptions = get_option('satoshipay_ad_blocker_detection');

            if ($this->validateAdBlockerDetectionOption($adBlockerDetectionOptions) === true) {
                $cookieName = 'enabledAdBlocker';
                $adBlockerBaitCode = str_replace('{{cookie_name}}', $cookieName, file_get_contents(__DIR__ . '/../../views/templates/ad_blocker_bait.tpl'));
                $adblockerCookie = ($_COOKIE[$cookieName] === 'true') ? true : false;
                $pricing['price'] = (int)$this->validateAdBlockerDetectionPrice($adBlockerDetectionOptions);
            }
        }

        if ($adblockerCookie === false) {
            return $adBlockerBaitCode . $content;
        }

        if ($pricing === false) {
            // there is no price (hence no start tag handling)
            // we just replace mediaTags
            return $this->replaceMediaTags($content);
        }

        // replace media tags above start tag
        $content = $this->replaceMediaTagsAboveStartTag($content);

        // get free content
        $intro = $this->getIntroContent($content);

        $goodData = array(
            'contentUrl' => site_url() . '/?satoshipay_content_id=' . $post->ID,
            'length' => strlen($content) - strlen($intro)
        );

        $html = $intro . $this->placeholderText($pricing, $goodData) . $this->scriptTag();

        return $adBlockerBaitCode . $html;
    }

    /**
     * Extract the intro part of a page/post content.
     *
     * @param string $content Content containing start tag.
     * @return string Content before start tag.
     */
    public static function getIntroContent($content)
    {
        $introContent = substr($content, 0, strpos($content, self::START_TAG));

        return $introContent;
    }

    /**
     * Extract the paid part of a page/post content.
     *
     * @param string $content Content containing start tag.
     * @return string Content after start tag.
     */
    public static function getPaidContent($content)
    {
        $tagPosition = strpos($content, self::START_TAG);
        $tagLength = strlen(self::START_TAG);
        $offset = 0;

        if ($tagPosition !== false) {
            $offset = $tagPosition + $tagLength;
        }

        return substr($content, $offset);
    }

    /**
     * Validate Ad Blocker Detection option.
     *
     * @param array $input
     * @return boolean|integer
     */
    protected function validateAdBlockerDetectionOption($input)
    {
        $validatedValue = false;

        // Check for existing input field
        if (isset($input['enabled'])) {
            $inputValue = trim($input['enabled']);
            // Check for integer value
            if (ctype_digit($inputValue)) {
                $inputValue = (int)$inputValue;
                // Check for valid value
                if (($inputValue >= 0) && ($inputValue <= 1)) {
                    $validatedValue = $inputValue;
                }
            }
        }

        return $validatedValue;
    }

    /**
     * Validate Ad Blocker Detection price.
     *
     * @param array $input
     * @return boolean|integer
     */
    protected function validateAdBlockerDetectionPrice($input)
    {
        $validatedValue = false;

        // Check for existing input field
        if (isset($input['price'])) {
            $inputValue = trim(strip_tags(stripslashes($input['price'])));
            // Check for integer value
            if (ctype_digit($inputValue)) {
                $inputValue = (int)$inputValue;
                // Check for valid value
                if ($inputValue > 0) {
                    $validatedValue = $inputValue;
                }
            }
        }

        return $validatedValue;
    }
}
