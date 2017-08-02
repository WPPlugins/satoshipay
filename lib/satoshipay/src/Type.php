<?php
/**
 * This file is part of the SatoshiPay PHP Library.
 *
 * (c) SatoshiPay <hello@satoshipay.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SatoshiPay;

class Type
{
    const TEXT = 1;
    const IMAGE = 2;
    const AUDIO = 3;
    const VIDEO = 4;
    const DOWNLOAD = 5;

    public static function fromMimeType($mimeType)
    {
        $typeParts = explode('/', $mimeType);

        if (count($typeParts) != 2) {
            return false;
        }

        switch ($typeParts[0]) {
            case 'application':
                return self::DOWNLOAD;
            case 'audio':
                return self::AUDIO;
            case 'image':
                return self::IMAGE;
            case 'text':
                return self::TEXT;
            case 'video':
                return self::VIDEO;
        }

        return false;
    }
}
