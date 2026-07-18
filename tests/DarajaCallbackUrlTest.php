<?php

require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

use Safaricom\Daraja\Daraja;
use Safaricom\Daraja\DarajaException;

if (class_exists('PHPUnit\Framework\TestCase')) {
    abstract class DarajaCallbackUrlTestCase extends PHPUnit\Framework\TestCase
    {
    }
} else {
    abstract class DarajaCallbackUrlTestCase extends PHPUnit_Framework_TestCase
    {
    }
}

class DarajaCallbackUrlTest extends DarajaCallbackUrlTestCase
{
    public function testBuildCallbackUrlUsesConfiguredBaseUrl()
    {
        $daraja = new Daraja(array('callbackBaseUrl' => 'https://housing.example.com/'));

        $this->assertSame('https://housing.example.com/daraja/stk-callback', $daraja->buildCallbackUrl('/daraja/stk-callback'));
    }

    public function testBuildCallbackUrlCanUseProvidedBaseUrl()
    {
        $daraja = new Daraja();

        $this->assertSame('https://tunnel.example.com/daraja/result', $daraja->buildCallbackUrl('daraja/result', 'https://tunnel.example.com/'));
    }

    public function testBuildCallbackUrlRequiresAResolvableBaseUrl()
    {
        $daraja = new Daraja();

        try {
            $daraja->buildCallbackUrl('/daraja/result');
            $this->fail('Expected DarajaException was not thrown.');
        } catch (DarajaException $exception) {
            $this->assertTrue(strpos($exception->getMessage(), 'callbackBaseUrl') !== false);
        }
    }
}
