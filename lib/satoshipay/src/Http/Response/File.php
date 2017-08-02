<?php
/**
 * This file is part of the SatoshiPay PHP Library.
 *
 * (c) SatoshiPay <hello@satoshipay.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SatoshiPay\Http\Response;

class File
{
    /**
     * @var int
     */
    private $size;

    /**
     * @var string
     */
    private $mimeType;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $range;

    /**
     * Constructor.
     *
     * @param string $path Full file system path, example: '/path/to/image.jpg'
     * @param string $mimeType MIME type of file, example: 'image/jpeg'
     * @param string $rangeHeader (optional) Range for partial HTTP content, example: 'bytes=0-499'
     */
    public function __construct($path, $mimeType, $rangeHeader = '')
    {
        $this->path = (string) $path;
        $this->mimeType = (string) $mimeType;

        if (!file_exists($this->path)) {
            throw new \Exception('File does not exist or is not accesible');
        }

        $this->size = filesize($this->path);
        $this->range = $this->parseRange($rangeHeader);
    }

    /**
     * Send headers and request body (file content) to browser.
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendBody();
    }

    /**
     * Parses content of HTTP header 'Range' for partial content.
     *
     * @param string $rangeHeader Range for partial HTTP content, example: 'bytes=0-499'
     *
     * @return array|bool Start, end and size of range, false if range is invalid.
     */
    public function parseRange($rangeHeader)
    {
        if ($rangeHeader == '') {
            return false;
        }

        list($rangeUnit, $rangeValue) = explode('=', $rangeHeader, 2);

        if (strtolower($rangeUnit) != 'bytes' || $rangeValue == '') {
            return false;
        }

        $rangeParts = explode('-', $rangeValue, 3);

        if (count($rangeParts) != 2) {
            // Range header does not contain '-', or more than once
            false;
        }

        if ($rangeParts[0] != '' && $rangeParts[1] == '') {
            // Range has no end, e.g. '500-'
            $start = intval($rangeParts[0]);
            $end = $this->size - 1;
        } else if ($rangeParts[0] == '' && $rangeParts[1] != '') {
            // Range has no start, e.g. '-1000' (last 1000 bytes requested)
            $start = $this->size - intval($rangeParts[1]);
            $end = $this->size - 1;
        } else if ($rangeParts[0] != '' && $rangeParts[1] != '') {
            // Range has start and end, e.g. '500-1000'
            $start = intval($rangeParts[0]);
            $end = intval($rangeParts[1]);
        }

        if ($start > $end || $start >= $this->size || $end >= $this->size) {
            // Range is, well, out of range
            return false;
        }

        $length = $end - $start + 1;

        if ($length < 1 || $length > $this->size) {
            return false;
        }

        return array(
            'start' => $start,
            'end' => $end,
            'length' => $length
        );
    }

    /**
     * Sends part of file that is requested via Range header.
     */
    private function sendPart()
    {
        $file = @fopen($this->path, 'rb');

        fseek($file, $this->range['start']);

        $buffer = 1024 * 8;

        while(!feof($file) && ($pointer = ftell($file)) <= $this->range['end']) {
            if ($pointer + $buffer > $this->range['end']) {
                $buffer = $this->range['end'] - $pointer + 1;
            }
            set_time_limit(0);
            echo fread($file, $buffer);
            flush();
        }

        fclose($file);
    }

    /**
     * Send HTTP body to browser, either full or partial file content.
     */
    public function sendBody()
    {
        if ($this->range) {
            $this->sendPart();
        } else {
            readfile($this->path);
        }
    }

    /**
     * Send headers to browser, differentiate between full and partial request.
     */
    public function sendHeaders()
    {
        header('Accept-Ranges: bytes');
        header('Content-Type: ' . $this->mimeType);

        if ($this->range) {
            header('HTTP/1.1 206 Partial Content');
            header('Content-Length: ' . $this->range['length']);
            header('Content-Range: bytes ' . $this->range['start'] . '-' . $this->range['end'] . '/' . $this->size);
        } else {
            header('HTTP/1.1 200 OK');
            header('Content-Length: ' . $this->size);
        }
    }
}
