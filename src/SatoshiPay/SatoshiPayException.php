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

class Exception extends \Exception
{
    /**
     * Constructor.
     */
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct('SatoshiPay Error: ' . $message, $code, $previous);
    }
}
