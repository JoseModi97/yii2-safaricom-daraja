<?php

namespace Safaricom\Daraja;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;

class Daraja extends Component
{
    public $environment = 'sandbox';
    public $sandboxBaseUrl = 'https://sandbox.safaricom.co.ke';
    public $productionBaseUrl = 'https://api.safaricom.co.ke';
    public $consumerKey;
    public $consumerSecret;
    public $accessToken;
    public $autoRefreshToken = true;
    public $httpClientConfig = array();
    public $defaultHeaders = array('Content-Type' => 'application/json');
    public $requestFormat = 'json';

    private $_httpClient;
    private $_tokenExpiresAt = 0;

    public function getBaseUrl()
    {
        return $this->environment === 'production' ? $this->productionBaseUrl : $this->sandboxBaseUrl;
    }

    public function getHttpClient()
    {
        if ($this->_httpClient === null) {
            $config = ArrayHelper::merge(array('class' => Client::className(), 'baseUrl' => $this->getBaseUrl()), $this->httpClientConfig);
            $this->_httpClient = Yii::createObject($config);
        }

        return $this->_httpClient;
    }

    public function generateAccessToken()
    {
        if (!$this->consumerKey || !$this->consumerSecret) {
            throw new DarajaException('consumerKey and consumerSecret are required to generate an access token.');
        }

        $endpoint = EndpointCatalog::get(EndpointCatalog::OAUTH_TOKEN);
        $request = $this->getHttpClient()
            ->createRequest()
            ->setMethod($endpoint['method'])
            ->setUrl(array($endpoint['path'], 'grant_type' => 'client_credentials'))
            ->addHeaders(array('Authorization' => 'Basic ' . base64_encode($this->consumerKey . ':' . $this->consumerSecret)));

        $data = $this->send($request);
        if (!isset($data['access_token'])) {
            throw new DarajaException('Safaricom OAuth response did not contain access_token.');
        }

        $this->accessToken = $data['access_token'];
        $ttl = isset($data['expires_in']) ? (int) $data['expires_in'] : 3599;
        $this->_tokenExpiresAt = time() + max(60, $ttl - 60);

        return $data;
    }

    public function request($endpointKey, array $data = array(), array $options = array())
    {
        $endpoint = EndpointCatalog::get($endpointKey);
        if ($endpoint === null) {
            throw new DarajaException('Unknown Daraja endpoint: ' . $endpointKey);
        }

        $query = isset($endpoint['query']) ? $endpoint['query'] : array();
        if (isset($options['query']) && is_array($options['query'])) {
            $query = ArrayHelper::merge($query, $options['query']);
        }

        $headers = ArrayHelper::merge($this->defaultHeaders, isset($options['headers']) ? $options['headers'] : array());
        if (!isset($endpoint['auth']) || $endpoint['auth'] !== 'basic') {
            $headers['Authorization'] = 'Bearer ' . $this->getAccessToken();
        }

        $url = $endpoint['path'];
        if (!empty($query)) {
            $url = array_merge(array($endpoint['path']), $query);
        }

        $request = $this->getHttpClient()
            ->createRequest()
            ->setMethod($endpoint['method'])
            ->setUrl($url)
            ->addHeaders($headers);

        if (strtoupper($endpoint['method']) !== 'GET') {
            $request->setFormat($this->requestFormat)->setData($data);
        }

        return $this->send($request);
    }

    public function getAccessToken()
    {
        if ($this->accessToken && (!$this->autoRefreshToken || $this->_tokenExpiresAt === 0 || $this->_tokenExpiresAt > time())) {
            return $this->accessToken;
        }

        $this->generateAccessToken();
        return $this->accessToken;
    }

    public function stkPush(array $data)
    {
        return $this->request(EndpointCatalog::STK_PUSH, $data);
    }

    public function stkQuery(array $data)
    {
        return $this->request(EndpointCatalog::STK_QUERY, $data);
    }

    public function c2bRegisterUrl(array $data)
    {
        return $this->request(EndpointCatalog::C2B_REGISTER_URL, $data);
    }

    public function c2bSimulate(array $data)
    {
        return $this->request(EndpointCatalog::C2B_SIMULATE, $data);
    }

    public function b2cPayment(array $data)
    {
        return $this->request(EndpointCatalog::B2C_PAYMENT, $data);
    }

    public function b2bPayment(array $data)
    {
        return $this->request(EndpointCatalog::B2B_PAYMENT, $data);
    }

    public function b2PochiPayment(array $data)
    {
        return $this->request(EndpointCatalog::B2POCHI_PAYMENT, $data);
    }

    public function reversal(array $data)
    {
        return $this->request(EndpointCatalog::REVERSAL, $data);
    }

    public function transactionStatus(array $data)
    {
        return $this->request(EndpointCatalog::TRANSACTION_STATUS, $data);
    }

    public function accountBalance(array $data)
    {
        return $this->request(EndpointCatalog::ACCOUNT_BALANCE, $data);
    }

    public function ratibaCreatePaybill(array $data)
    {
        return $this->request(EndpointCatalog::RATIBA_CREATE_PAYBILL, $data);
    }

    public function ratibaCreateBuyGoods(array $data)
    {
        return $this->request(EndpointCatalog::RATIBA_CREATE_BUY_GOODS, $data);
    }

    public function lipaNaBongaRedeemPaybill(array $data)
    {
        return $this->request(EndpointCatalog::LIPA_NA_BONGA_REDEEM_PAYBILL, $data);
    }

    public function lipaNaBongaCalculatePoints(array $data)
    {
        return $this->request(EndpointCatalog::LIPA_NA_BONGA_CALCULATE_POINTS, $data);
    }

    public function imsiCheckAti(array $data)
    {
        return $this->request(EndpointCatalog::IMSI_CHECK_ATI, $data);
    }

    public function swapCheckAti(array $data)
    {
        return $this->request(EndpointCatalog::SWAP_CHECK_ATI, $data);
    }

    public function pullRegister(array $data)
    {
        return $this->request(EndpointCatalog::PULL_REGISTER, $data);
    }

    public function pullQuery(array $data)
    {
        return $this->request(EndpointCatalog::PULL_QUERY, $data);
    }

    public function iot($endpointKey, array $data = array(), array $headers = array(), array $query = array())
    {
        return $this->request($endpointKey, $data, array('headers' => $headers, 'query' => $query));
    }

    public function generateStkPassword($businessShortCode, $passkey, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = date('YmdHis');
        }

        return base64_encode($businessShortCode . $passkey . $timestamp);
    }

    public function generateSecurityCredential($initiatorPassword, $certificatePath)
    {
        if (!is_file($certificatePath)) {
            throw new DarajaException('Certificate file does not exist: ' . $certificatePath);
        }

        $publicKey = openssl_pkey_get_public(file_get_contents($certificatePath));
        if ($publicKey === false) {
            throw new DarajaException('Unable to read Safaricom public certificate.');
        }

        $encrypted = '';
        if (!openssl_public_encrypt($initiatorPassword, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING)) {
            throw new DarajaException('Unable to encrypt initiator password.');
        }

        return base64_encode($encrypted);
    }

    protected function send($request)
    {
        $response = $request->send();
        $data = $response->getData();

        if (!$response->getIsOk()) {
            $message = is_array($data) ? json_encode($data) : (string) $response->content;
            throw new DarajaException('Safaricom API request failed with HTTP ' . $response->statusCode . ': ' . $message);
        }

        return $data;
    }
}
