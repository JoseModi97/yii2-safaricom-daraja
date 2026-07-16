# Yii2 Safaricom Daraja Extension

Yii2 Composer extension generated from `Safaricom APIs Copy.postman_collection.json`.

It supports PHP `>=5.6` and newer PHP versions by avoiding scalar type declarations and return types. It uses `yiisoft/yii2-httpclient` for HTTP transport.

## Install

After publishing to Packagist:

```bash
composer require josemodi97/yii2-safaricom-daraja
```

If the package is kept in a local folder, add a path repository to the consuming Yii app:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../yii2-safaricom-daraja"
    }
  ]
}
```

## Configure

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

## Examples

```php
$timestamp = date('YmdHis');
$password = Yii::$app->daraja->generateStkPassword($shortCode, $passkey, $timestamp);

$response = Yii::$app->daraja->stkPush(array(
    'BusinessShortCode' => $shortCode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => 1,
    'PartyA' => '254700000000',
    'PartyB' => $shortCode,
    'PhoneNumber' => '254700000000',
    'CallBackURL' => 'https://example.com/daraja/callback',
    'AccountReference' => 'INV-1001',
    'TransactionDesc' => 'Invoice payment',
));
```

```php
$response = Yii::$app->daraja->c2bRegisterUrl(array(
    'ShortCode' => '600000',
    'ResponseType' => 'Completed',
    'ConfirmationURL' => 'https://example.com/confirmation',
    'ValidationURL' => 'https://example.com/validation',
));
```

Generic request access is available for every endpoint:

```php
use Safaricom\Daraja\EndpointCatalog;

$response = Yii::$app->daraja->request(EndpointCatalog::ACCOUNT_BALANCE, array(
    'Initiator' => '',
    'SecurityCredential' => '',
    'CommandID' => 'AccountBalance',
    'PartyA' => '',
    'IdentifierType' => '4',
    'Remarks' => '',
    'QueueTimeOutURL' => '',
    'ResultURL' => '',
));
```

## Collection Endpoints Included

- OAuth access token
- Ratiba standing order external create for Paybill and Buy Goods
- B2B payment request
- B2C payment request
- B2Pochi payment request
- C2B URL registration and C2B simulation
- STK Push process request and STK Push query
- Transaction reversal
- Transaction status query
- Account balance query
- Lipa na Bonga redeem paybill and calculate points
- IMSI CheckATI and SWAP CheckATI
- Pull Transactions register and query
- IoT SIM portal endpoints from the collection through `Daraja::iot()`

## Notes

The extension does not hard-code credentials from the Postman collection. Put credentials in environment variables or Yii application params.
