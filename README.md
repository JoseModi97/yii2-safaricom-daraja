# Yii2 Safaricom Daraja Extension

Yii2 Composer extension generated from `Safaricom APIs Copy.postman_collection.json`.

This package wraps the Safaricom Daraja and related sandbox APIs in a Yii2 component so you can call them from normal Yii2 MVC code: models/forms validate user input, controllers call `Yii::$app->daraja`, and callback actions receive Safaricom asynchronous responses.

The code supports PHP `>=5.4` through current PHP versions supported by Yii2. It avoids PHP 7+ syntax, scalar type declarations, return types, nullable types, short arrays, and other syntax that breaks older Yii2 projects. HTTP transport is handled by `yiisoft/yii2-httpclient`.

## Compatibility

- PHP `5.4+`
- Yii2 `2.0.6+`
- PHPUnit `4.8+` through `9.x` for the included tests
- Composer package name: `josemodi97/yii2-safaricom-daraja`

## Installation

After publishing to Packagist:

```bash
composer require josemodi97/yii2-safaricom-daraja
```

If the package is kept in a local folder, add a path repository to the consuming Yii2 app:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../yii2-safaricom-daraja"
    }
  ],
  "require": {
    "josemodi97/yii2-safaricom-daraja": "*"
  }
}
```

Then run:

```bash
composer update josemodi97/yii2-safaricom-daraja
```

## Yii2 Configuration

Add the component to `config/web.php` or `config/main.php`.

```php
'components' => array(
    'daraja' => array(
        'class' => 'Safaricom\\Daraja\\Daraja',
        'environment' => 'sandbox',
        'consumerKey' => getenv('DARAJA_CONSUMER_KEY'),
        'consumerSecret' => getenv('DARAJA_CONSUMER_SECRET'),
    ),
),
```

Use `environment => 'production'` for `https://api.safaricom.co.ke`.

Recommended app params:

```php
'params' => array(
    'daraja.shortCode' => getenv('DARAJA_SHORT_CODE'),
    'daraja.passkey' => getenv('DARAJA_PASSKEY'),
    'daraja.initiatorName' => getenv('DARAJA_INITIATOR_NAME'),
    'daraja.initiatorPassword' => getenv('DARAJA_INITIATOR_PASSWORD'),
    'daraja.certificatePath' => '@app/certs/SafaricomSandboxCertificate.cer',
    'daraja.callbackBaseUrl' => getenv('DARAJA_CALLBACK_BASE_URL'),
),
```

Do not hard-code real consumer keys, secrets, passkeys, initiator passwords, or API keys in code. The Postman collection may contain sample values; move all secrets to environment variables.

## Basic Usage

Generate an OAuth access token:

```php
$tokenResponse = Yii::$app->daraja->generateAccessToken();
$accessToken = $tokenResponse['access_token'];
```

Most API calls do not need you to pass the token manually. The component automatically generates and refreshes the bearer token when `consumerKey` and `consumerSecret` are configured.

Use named helper methods where available:

```php
$response = Yii::$app->daraja->stkPush($payload);
$response = Yii::$app->daraja->c2bRegisterUrl($payload);
$response = Yii::$app->daraja->accountBalance($payload);
```

Use the generic endpoint catalog for any endpoint:

```php
use Safaricom\Daraja\EndpointCatalog;

$response = Yii::$app->daraja->request(EndpointCatalog::PULL_QUERY, array(
    'ShortCode' => Yii::$app->params['daraja.shortCode'],
    'StartDate' => '2026-07-01 00:00:00',
    'EndDate' => '2026-07-16 23:59:59',
    'OffSetValue' => '0',
));
```

## Yii2 MVC Pattern

A clean Yii2 integration usually looks like this:

- Model or form: validates phone numbers, amount, account reference, date ranges, and required business fields.
- Controller: receives the user request, builds the Daraja payload, calls the component, and returns a Yii response.
- Callback controller action: receives Safaricom result/confirmation/validation callbacks and stores them.
- Service or ActiveRecord layer: saves payment requests, checkout request IDs, transaction IDs, and callback result codes.

## Example Model: STK Push Form

Create `models/StkPushForm.php`.

```php
<?php

namespace app\models;

use Yii;
use yii\base\Model;

class StkPushForm extends Model
{
    public $phoneNumber;
    public $amount;
    public $accountReference;
    public $transactionDesc;

    public function rules()
    {
        return array(
            array(array('phoneNumber', 'amount', 'accountReference'), 'required'),
            array('amount', 'number', 'min' => 1),
            array(array('accountReference', 'transactionDesc'), 'string', 'max' => 100),
            array('phoneNumber', 'match', 'pattern' => '/^2547[0-9]{8}$/', 'message' => 'Use format 2547XXXXXXXX.'),
        );
    }

    public function send()
    {
        if (!$this->validate()) {
            return false;
        }

        $shortCode = Yii::$app->params['daraja.shortCode'];
        $passkey = Yii::$app->params['daraja.passkey'];
        $timestamp = date('YmdHis');

        return Yii::$app->daraja->stkPush(array(
            'BusinessShortCode' => $shortCode,
            'Password' => Yii::$app->daraja->generateStkPassword($shortCode, $passkey, $timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $this->amount,
            'PartyA' => $this->phoneNumber,
            'PartyB' => $shortCode,
            'PhoneNumber' => $this->phoneNumber,
            'CallBackURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/stk-callback',
            'AccountReference' => $this->accountReference,
            'TransactionDesc' => $this->transactionDesc ? $this->transactionDesc : 'Payment',
        ));
    }
}
```

## Example Controller

Create `controllers/DarajaController.php`.

```php
<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\StkPushForm;

class DarajaController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionStkPush()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new StkPushForm();
        $model->load(Yii::$app->request->post(), '');

        if (!$model->validate()) {
            return array('ok' => false, 'errors' => $model->getErrors());
        }

        try {
            return array('ok' => true, 'data' => $model->send());
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return array('ok' => false, 'message' => $e->getMessage());
        }
    }

    public function actionStkCallback()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $raw = Yii::$app->request->getRawBody();
        $payload = json_decode($raw, true);

        Yii::info($payload, 'daraja.stk.callback');

        /*
         * Save the callback to your database here.
         * Common fields:
         * $payload['Body']['stkCallback']['MerchantRequestID']
         * $payload['Body']['stkCallback']['CheckoutRequestID']
         * $payload['Body']['stkCallback']['ResultCode']
         * $payload['Body']['stkCallback']['ResultDesc']
         */

        return array('ResultCode' => 0, 'ResultDesc' => 'Accepted');
    }
}
```

## STK Push and Query

Start a Lipa na M-Pesa Online payment:

```php
$timestamp = date('YmdHis');
$shortCode = Yii::$app->params['daraja.shortCode'];
$password = Yii::$app->daraja->generateStkPassword($shortCode, Yii::$app->params['daraja.passkey'], $timestamp);

$response = Yii::$app->daraja->stkPush(array(
    'BusinessShortCode' => $shortCode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => 1,
    'PartyA' => '254700000000',
    'PartyB' => $shortCode,
    'PhoneNumber' => '254700000000',
    'CallBackURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/stk-callback',
    'AccountReference' => 'INV-1001',
    'TransactionDesc' => 'Invoice payment',
));
```

Query an STK payment using the `CheckoutRequestID` returned by Safaricom:

```php
$response = Yii::$app->daraja->stkQuery(array(
    'BusinessShortCode' => $shortCode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'CheckoutRequestID' => 'ws_CO_...',
));
```

## C2B URL Registration and Simulation

Register confirmation and validation URLs:

```php
$response = Yii::$app->daraja->c2bRegisterUrl(array(
    'ShortCode' => Yii::$app->params['daraja.shortCode'],
    'ResponseType' => 'Completed',
    'ConfirmationURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/c2b-confirmation',
    'ValidationURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/c2b-validation',
));
```

Sandbox C2B simulation:

```php
$response = Yii::$app->daraja->c2bSimulate(array(
    'ShortCode' => Yii::$app->params['daraja.shortCode'],
    'CommandID' => 'CustomerPayBillOnline',
    'Amount' => '10',
    'Msisdn' => '254700000000',
    'BillRefNumber' => 'INV-1001',
));
```

Callback examples:

```php
public function actionC2bValidation()
{
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::info(json_decode(Yii::$app->request->getRawBody(), true), 'daraja.c2b.validation');

    return array('ResultCode' => 0, 'ResultDesc' => 'Accepted');
}

public function actionC2bConfirmation()
{
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::info(json_decode(Yii::$app->request->getRawBody(), true), 'daraja.c2b.confirmation');

    return array('ResultCode' => 0, 'ResultDesc' => 'Accepted');
}
```

## B2C, B2B, and B2Pochi

Generate a security credential from your initiator password and Safaricom public certificate:

```php
$credential = Yii::$app->daraja->generateSecurityCredential(
    Yii::$app->params['daraja.initiatorPassword'],
    Yii::getAlias(Yii::$app->params['daraja.certificatePath'])
);
```

B2C payment request:

```php
$response = Yii::$app->daraja->b2cPayment(array(
    'InitiatorName' => Yii::$app->params['daraja.initiatorName'],
    'SecurityCredential' => $credential,
    'CommandID' => 'BusinessPayment',
    'Amount' => '100',
    'PartyA' => Yii::$app->params['daraja.shortCode'],
    'PartyB' => '254700000000',
    'Remarks' => 'Payout',
    'QueueTimeOutURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/timeout',
    'ResultURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/result',
    'Occasion' => 'Refund',
));
```

B2B payment request:

```php
$response = Yii::$app->daraja->b2bPayment(array(
    'Initiator' => Yii::$app->params['daraja.initiatorName'],
    'SecurityCredential' => $credential,
    'CommandID' => 'BusinessPayBill',
    'SenderIdentifierType' => '4',
    'RecieverIdentifierType' => '4',
    'Amount' => '100',
    'PartyA' => Yii::$app->params['daraja.shortCode'],
    'PartyB' => '600000',
    'AccountReference' => 'INV-1001',
    'Remarks' => 'Supplier payment',
    'QueueTimeOutURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/timeout',
    'ResultURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/result',
));
```

B2Pochi payment request:

```php
$response = Yii::$app->daraja->b2PochiPayment(array(
    'OriginatorConversationID' => uniqid('b2pochi-', true),
    'InitiatorName' => Yii::$app->params['daraja.initiatorName'],
    'SecurityCredential' => $credential,
    'CommandID' => 'BusinessPayment',
    'Amount' => '100',
    'PartyA' => Yii::$app->params['daraja.shortCode'],
    'PartyB' => '254700000000',
    'Remarks' => 'B2Pochi payment',
    'QueueTimeOutURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/timeout',
    'ResultURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/result',
    'Occasion' => 'Payment',
));
```

## Reversal, Transaction Status, and Account Balance

Reverse a transaction:

```php
$response = Yii::$app->daraja->reversal(array(
    'Initiator' => Yii::$app->params['daraja.initiatorName'],
    'SecurityCredential' => $credential,
    'CommandID' => 'TransactionReversal',
    'TransactionID' => 'ABC123XYZ',
    'Amount' => '100',
    'ReceiverParty' => Yii::$app->params['daraja.shortCode'],
    'RecieverIdentifierType' => '4',
    'ResultURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/result',
    'QueueTimeOutURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/timeout',
    'Remarks' => 'Customer refund',
    'Occasion' => 'Refund',
));
```

Query transaction status:

```php
$response = Yii::$app->daraja->transactionStatus(array(
    'Initiator' => Yii::$app->params['daraja.initiatorName'],
    'SecurityCredential' => $credential,
    'CommandID' => 'TransactionStatusQuery',
    'TransactionID' => 'ABC123XYZ',
    'PartyA' => Yii::$app->params['daraja.shortCode'],
    'IdentifierType' => '4',
    'ResultURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/result',
    'QueueTimeOutURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/timeout',
    'Remarks' => 'Status query',
    'Occasion' => 'Status',
));
```

Query account balance:

```php
$response = Yii::$app->daraja->accountBalance(array(
    'Initiator' => Yii::$app->params['daraja.initiatorName'],
    'SecurityCredential' => $credential,
    'CommandID' => 'AccountBalance',
    'PartyA' => Yii::$app->params['daraja.shortCode'],
    'IdentifierType' => '4',
    'Remarks' => 'Balance query',
    'QueueTimeOutURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/timeout',
    'ResultURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/result',
));
```

## M-Pesa Ratiba Standing Orders

Create a standing order for Paybill:

```php
$response = Yii::$app->daraja->ratibaCreatePaybill(array(
    'StandingOrderName' => 'Monthly fee',
    'BusinessShortCode' => '174379',
    'TransactionType' => 'Standing Order Customer Pay Bill',
    'Amount' => '100',
    'PartyA' => '254700000000',
    'ReceiverPartyIdentifierType' => '4',
    'CallBackURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/ratiba-callback',
    'AccountReference' => 'ACC-1001',
    'TransactionDesc' => 'Monthly payment',
    'Frequency' => '1',
    'StartDate' => '20260716',
    'EndDate' => '20270716',
));
```

Create a standing order for Buy Goods:

```php
$response = Yii::$app->daraja->ratibaCreateBuyGoods(array(
    'StandingOrderName' => 'Merchant subscription',
    'BusinessShortCode' => '300584',
    'TransactionType' => 'Standing Order Customer Pay Merchant',
    'Amount' => '100',
    'PartyA' => '254700000000',
    'ReceiverPartyIdentifierType' => '2',
    'CallBackURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/ratiba-callback',
    'AccountReference' => 'ACC-1001',
    'TransactionDesc' => 'Merchant payment',
    'Frequency' => '1',
    'StartDate' => '20260716',
    'EndDate' => '20270716',
));
```

## Lipa na Bonga

Redeem Bonga points to Paybill:

```php
$response = Yii::$app->daraja->lipaNaBongaRedeemPaybill(array(
    'msisdn' => '254700000000',
    'amount' => 100,
    'bongaPoints' => 500,
    'conversionRate' => 0.2,
    'shortCode' => Yii::$app->params['daraja.shortCode'],
    'accountNumber' => 'ACC-1001',
));
```

Calculate points:

```php
$response = Yii::$app->daraja->lipaNaBongaCalculatePoints(array(
    'points' => '500',
));
```

## IMSI and SWAP CheckATI

```php
$response = Yii::$app->daraja->imsiCheckAti(array(
    'customerNumber' => '254700000000',
));

$response = Yii::$app->daraja->swapCheckAti(array(
    'customerNumber' => '254700000000',
));
```

## Pull Transactions API

Register a callback URL:

```php
$response = Yii::$app->daraja->pullRegister(array(
    'ShortCode' => Yii::$app->params['daraja.shortCode'],
    'RequestType' => 'Pull',
    'NominatedNumber' => '254700000000',
    'CallBackURL' => Yii::$app->params['daraja.callbackBaseUrl'] . '/daraja/pull-callback',
));
```

Query transactions:

```php
$response = Yii::$app->daraja->pullQuery(array(
    'ShortCode' => Yii::$app->params['daraja.shortCode'],
    'StartDate' => '2026-07-01 00:00:00',
    'EndDate' => '2026-07-16 23:59:59',
    'OffSetValue' => '0',
));
```

## IoT SIM Portal APIs

The IoT SIM portal endpoints from the collection use the same `request()` engine, but they commonly need additional headers such as `x-api-key`, `x-source-system`, `X-MSISDN`, `X-App`, and `X-MessageID`. Use `Daraja::iot($endpointKey, $data, $headers, $query)`.

```php
use Safaricom\Daraja\EndpointCatalog;

$headers = array(
    'x-correlation-conversationid' => uniqid('', true),
    'x-source-system' => 'web-portal',
    'x-api-key' => getenv('DARAJA_IOT_API_KEY'),
    'Accept-Language' => 'EN',
    'X-MSISDN' => getenv('DARAJA_IOT_MSISDN'),
    'X-App' => 'web-portal',
    'X-MessageID' => uniqid('msg-', true),
);

$response = Yii::$app->daraja->iot(
    EndpointCatalog::IOT_GET_ALL_MESSAGES,
    array('vpnGroup' => 'MY-GROUP'),
    $headers,
    array('pageNo' => 1, 'pageSize' => 10)
);
```

The package also exposes named IoT helpers such as `iotSearchMessages()`, `iotSendSingleMessage()`, `iotAllSims()`, and `iotSuspendUnsuspendSub()`. These helpers call the same endpoints as `iot()` and accept the same payload/header/query style where paging is needed.

Search messages:

```php
$response = Yii::$app->daraja->iot(
    EndpointCatalog::IOT_SEARCH_MESSAGES,
    array('searchValue' => 'hello', 'vpnGroup' => 'MY-GROUP', 'username' => 'admin'),
    $headers,
    array('pageNo' => 1, 'pageSize' => 5)
);
```

Send one message:

```php
$response = Yii::$app->daraja->iot(
    EndpointCatalog::IOT_SEND_SINGLE_MESSAGE,
    array(
        'msisdn' => '254700000000',
        'message' => 'Test message',
        'vpnGroup' => 'MY-GROUP',
        'username' => 'admin',
    ),
    $headers
);
```

SIM activation:

```php
$response = Yii::$app->daraja->iot(
    EndpointCatalog::IOT_SIM_ACTIVATION,
    array('msisdn' => '254700000000', 'vpnGroup' => 'MY-GROUP', 'username' => 'admin'),
    $headers
);
```

## All Tools and Endpoints from the Collection

Import the constant class where you need generic access:

```php
use Safaricom\Daraja\EndpointCatalog;
```

| Tool / API from Postman | Helper method | Endpoint constant |
| --- | --- | --- |
| OAuth access token | `generateAccessToken()` | `EndpointCatalog::OAUTH_TOKEN` |
| M-Pesa Ratiba Paybill standing order | `ratibaCreatePaybill($data)` | `EndpointCatalog::RATIBA_CREATE_PAYBILL` |
| M-Pesa Ratiba Buy Goods standing order | `ratibaCreateBuyGoods($data)` | `EndpointCatalog::RATIBA_CREATE_BUY_GOODS` |
| B2B payment request | `b2bPayment($data)` | `EndpointCatalog::B2B_PAYMENT` |
| B2C payment request | `b2cPayment($data)` | `EndpointCatalog::B2C_PAYMENT` |
| B2Pochi payment request | `b2PochiPayment($data)` | `EndpointCatalog::B2POCHI_PAYMENT` |
| C2B URL registration | `c2bRegisterUrl($data)` | `EndpointCatalog::C2B_REGISTER_URL` |
| C2B simulation | `c2bSimulate($data)` | `EndpointCatalog::C2B_SIMULATE` |
| STK Push process request | `stkPush($data)` | `EndpointCatalog::STK_PUSH` |
| STK Push query | `stkQuery($data)` | `EndpointCatalog::STK_QUERY` |
| Transaction reversal | `reversal($data)` | `EndpointCatalog::REVERSAL` |
| Transaction status query | `transactionStatus($data)` | `EndpointCatalog::TRANSACTION_STATUS` |
| Account balance query | `accountBalance($data)` | `EndpointCatalog::ACCOUNT_BALANCE` |
| Lipa na Bonga redeem Paybill | `lipaNaBongaRedeemPaybill($data)` | `EndpointCatalog::LIPA_NA_BONGA_REDEEM_PAYBILL` |
| Lipa na Bonga calculate points | `lipaNaBongaCalculatePoints($data)` | `EndpointCatalog::LIPA_NA_BONGA_CALCULATE_POINTS` |
| IMSI CheckATI | `imsiCheckAti($data)` | `EndpointCatalog::IMSI_CHECK_ATI` |
| SWAP CheckATI | `swapCheckAti($data)` | `EndpointCatalog::SWAP_CHECK_ATI` |
| Pull Transactions register URL | `pullRegister($data)` | `EndpointCatalog::PULL_REGISTER` |
| Pull Transactions query | `pullQuery($data)` | `EndpointCatalog::PULL_QUERY` |
| IoT search messages | `iotSearchMessages($data, $headers, $query)` or `iot()` | `EndpointCatalog::IOT_SEARCH_MESSAGES` |
| IoT filter messages | `iotFilterMessages($data, $headers, $query)` or `iot()` | `EndpointCatalog::IOT_FILTER_MESSAGES` |
| IoT delete message thread | `iotDeleteMessageThread($data, $headers)` or `iot()` | `EndpointCatalog::IOT_DELETE_MESSAGE_THREAD` |
| IoT get all messages | `iotGetAllMessages($data, $headers, $query)` or `iot()` | `EndpointCatalog::IOT_GET_ALL_MESSAGES` |
| IoT send single message | `iotSendSingleMessage($data, $headers)` or `iot()` | `EndpointCatalog::IOT_SEND_SINGLE_MESSAGE` |
| IoT delete message | `iotDeleteMessage($data, $headers)` or `iot()` | `EndpointCatalog::IOT_DELETE_MESSAGE` |
| IoT all SIMs | `iotAllSims($data, $headers, $query)` or `iot()` | `EndpointCatalog::IOT_ALL_SIMS` |
| IoT query lifecycle status | `iotQueryLifecycleStatus($data, $headers)` or `iot()` | `EndpointCatalog::IOT_QUERY_LIFECYCLE_STATUS` |
| IoT query customer info | `iotQueryCustomerInfo($data, $headers)` or `iot()` | `EndpointCatalog::IOT_QUERY_CUSTOMER_INFO` |
| IoT SIM activation | `iotSimActivation($data, $headers)` or `iot()` | `EndpointCatalog::IOT_SIM_ACTIVATION` |
| IoT get activation trends | `iotGetActivationTrends($data, $headers)` or `iot()` | `EndpointCatalog::IOT_GET_ACTIVATION_TRENDS` |
| IoT rename asset | `iotRenameAsset($data, $headers)` or `iot()` | `EndpointCatalog::IOT_RENAME_ASSET` |
| IoT get location info | `iotGetLocationInfo($data, $headers)` or `iot()` | `EndpointCatalog::IOT_GET_LOCATION_INFO` |
| IoT suspend / unsuspend subscriber | `iotSuspendUnsuspendSub($data, $headers)` or `iot()` | `EndpointCatalog::IOT_SUSPEND_UNSUSPEND_SUB` |

## Endpoint Paths

All paths use the configured base URL:

- Sandbox: `https://sandbox.safaricom.co.ke`
- Production: `https://api.safaricom.co.ke`

| Constant | Method | Path |
| --- | --- | --- |
| `OAUTH_TOKEN` | GET | `/oauth/v1/generate?grant_type=client_credentials` |
| `RATIBA_CREATE_PAYBILL` | POST | `/standingorder/v1/createStandingOrderExternal` |
| `RATIBA_CREATE_BUY_GOODS` | POST | `/standingorder/v1/createStandingOrderExternal` |
| `B2B_PAYMENT` | POST | `/mpesa/b2b/v1/paymentrequest` |
| `B2C_PAYMENT` | POST | `/mpesa/b2c/v1/paymentrequest` |
| `B2POCHI_PAYMENT` | POST | `/mpesa/b2c/v1/paymentrequest` |
| `C2B_REGISTER_URL` | POST | `/mpesa/c2b/v1/registerurl` |
| `C2B_SIMULATE` | POST | `/mpesa/c2b/v1/simulate` |
| `STK_PUSH` | POST | `/mpesa/stkpush/v1/processrequest` |
| `STK_QUERY` | POST | `/mpesa/stkpushquery/v1/query` |
| `REVERSAL` | POST | `/mpesa/reversal/v1/request` |
| `TRANSACTION_STATUS` | POST | `/mpesa/transactionstatus/v1/query` |
| `ACCOUNT_BALANCE` | POST | `/mpesa/accountbalance/v1/query` |
| `LIPA_NA_BONGA_REDEEM_PAYBILL` | POST | `/v1/lipa/na/bonga/redeem-paybill` |
| `LIPA_NA_BONGA_CALCULATE_POINTS` | POST | `/v1/lipa/na/bonga/calculator-points` |
| `IMSI_CHECK_ATI` | POST | `/imsi/v1/checkATI` |
| `SWAP_CHECK_ATI` | POST | `/imsi/v2/checkATI` |
| `PULL_REGISTER` | POST | `/pulltransactions/v1/register` |
| `PULL_QUERY` | POST | `/pulltransactions/v1/query` |
| `IOT_SEARCH_MESSAGES` | POST | `/simportal/v1/searchmessages` |
| `IOT_FILTER_MESSAGES` | POST | `/simportal/v1/filtermessages` |
| `IOT_DELETE_MESSAGE_THREAD` | POST | `/simportal/v1/deleteMessageThread` |
| `IOT_GET_ALL_MESSAGES` | POST | `/simportal/v1/getallmessages` |
| `IOT_SEND_SINGLE_MESSAGE` | POST | `/simportal/v1/sendsinglemessage` |
| `IOT_DELETE_MESSAGE` | POST | `/simportal/v1/deletemessage` |
| `IOT_ALL_SIMS` | POST | `/simportal/v1/allsims` |
| `IOT_QUERY_LIFECYCLE_STATUS` | POST | `/simportal/v1/queryLifeCycleStatus` |
| `IOT_QUERY_CUSTOMER_INFO` | POST | `/simportal/v1/querycustomerinfo` |
| `IOT_SIM_ACTIVATION` | POST | `/simportal/v1/simactivation` |
| `IOT_GET_ACTIVATION_TRENDS` | POST | `/simportal/v1/getactivationtrends` |
| `IOT_RENAME_ASSET` | POST | `/simportal/v1/renameasset` |
| `IOT_GET_LOCATION_INFO` | POST | `/simportal/v1/getlocationinfo` |
| `IOT_SUSPEND_UNSUSPEND_SUB` | POST | `/simportal/v1/suspend_unsuspend_sub` |

## Callback and Result URL Notes

Safaricom sends many responses asynchronously. Any payload with `ResultURL`, `QueueTimeOutURL`, `CallBackURL`, `ConfirmationURL`, or `ValidationURL` must point to a publicly reachable HTTPS URL.

For local development, expose your Yii2 app through a secure tunnel and set:

```bash
DARAJA_CALLBACK_BASE_URL=https://your-public-url.example
```

Always store the raw callback JSON before transforming it. This makes reconciliation much easier when Safaricom sends unexpected fields.

## Error Handling

Failed HTTP responses throw `Safaricom\Daraja\DarajaException`.

```php
try {
    $response = Yii::$app->daraja->accountBalance($payload);
} catch (\Safaricom\Daraja\DarajaException $e) {
    Yii::error($e->getMessage(), 'daraja');
    throw $e;
}
```

## Testing

Run the package tests:

```bash
vendor/bin/phpunit
```

The included tests check the endpoint catalog and component behavior. Real API calls require valid Safaricom credentials and publicly reachable callback URLs.

## Notes

- The extension does not hard-code credentials from the Postman collection.
- Put credentials in environment variables or Yii application params.
- Keep callback actions CSRF-exempt because Safaricom will not send a Yii CSRF token.
- Keep your production and sandbox credentials separate.
- Store IDs returned by Safaricom, especially `MerchantRequestID`, `CheckoutRequestID`, `ConversationID`, `OriginatorConversationID`, and transaction IDs.
