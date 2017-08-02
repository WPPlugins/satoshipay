<?php
/**
 * This file is part of the SatoshiPay WordPress plugin.
 *
 * (c) SatoshiPay <hello@satoshipay.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SatoshiPay\Plugin;

abstract class PluginAbstract
{
    /**
     * @var string
     */
    protected $mainPluginFile;

    /**
     * @var PluginAbstract
     */
    protected static $instance;

    /**
     * @var string
     */
    protected $name = 'satoshipay';

    /**
     * @var string
     */
    protected $textdomain = 'satoshipay';

    /**
     * @var array
     */
    protected $styles = array();

    /**
     * @var array
     */
    protected $scripts = array();

    /**
     * Constructor.
     */
    final protected function __construct($mainPluginFile)
    {
        $this->mainPluginFile = $mainPluginFile;

        // Register actication / deactivation hooks.
        register_activation_hook($this->mainPluginFile, array($this, 'activate'));
        register_deactivation_hook($this->mainPluginFile, array($this, 'deactivate'));
    }

    /**
     * Initialize the plugin.
     *
     * @return $this
     */
    abstract public function init();

    /**
     * Activate plugin.
     *
     * @return $this
     */
    public function activate()
    {
        return $this;
    }

    /**
     * Deactivate plugin.
     *
     * @return $this
     */
    public function deactivate()
    {
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTextdomain()
    {
        return $this->textdomain;
    }

    /**
     * @return $this
     */
    public function enqueueStyles()
    {
        foreach ($this->styles as $handle => $src) {
            wp_register_style($handle, $src, false, SATOSHIPAY_VERSION);
            wp_enqueue_style($handle);
        }

        return $this;
    }

    /**
     * @param string $scope
     * @return $this
     */
    public function enqueueScripts($scope = '', $filter = false)
    {
        foreach ($this->scripts as $handle => $src) {
          if ($filter && $filter === $handle) {
            wp_register_script($handle, $src);
            wp_enqueue_script($handle);
          }
        }

        return $this;
    }
}
