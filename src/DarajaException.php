<?php

namespace Safaricom\Daraja;

use yii\base\Exception;

class DarajaException extends Exception
{
    private $_statusCode;
    private $_responseData;
    private $_endpointKey;

    public function __construct($message = '', $code = 0, $previous = null, $statusCode = null, $responseData = null, $endpointKey = null)
    {
        $this->_statusCode = $statusCode;
        $this->_responseData = $responseData;
        $this->_endpointKey = $endpointKey;

        parent::__construct($message, $code, $previous);
    }

    public static function forHttpResponse($statusCode, $responseData = null, $endpointKey = null)
    {
        $message = is_array($responseData) ? json_encode($responseData) : (string) $responseData;
        if ($message === '') {
            $message = 'No response body was returned.';
        }

        return new self(
            'Safaricom API request failed with HTTP ' . $statusCode . ': ' . $message,
            0,
            null,
            $statusCode,
            $responseData,
            $endpointKey
        );
    }

    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    public function getResponseData()
    {
        return $this->_responseData;
    }

    public function getEndpointKey()
    {
        return $this->_endpointKey;
    }
}
