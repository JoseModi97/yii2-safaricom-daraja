<?php

use Safaricom\Daraja\EndpointCatalog;
use Safaricom\Daraja\DarajaException;

if (class_exists('PHPUnit\Framework\TestCase')) {
    abstract class EndpointCatalogTestCase extends PHPUnit\Framework\TestCase
    {
    }
} else {
    abstract class EndpointCatalogTestCase extends PHPUnit_Framework_TestCase
    {
    }
}

class EndpointCatalogTest extends EndpointCatalogTestCase
{
    public function testCollectionEndpointsAreRegistered()
    {
        $endpoints = EndpointCatalog::all();

        $expected = array(
            EndpointCatalog::OAUTH_TOKEN,
            EndpointCatalog::RATIBA_CREATE_PAYBILL,
            EndpointCatalog::RATIBA_CREATE_BUY_GOODS,
            EndpointCatalog::B2B_PAYMENT,
            EndpointCatalog::B2C_PAYMENT,
            EndpointCatalog::B2POCHI_PAYMENT,
            EndpointCatalog::C2B_REGISTER_URL,
            EndpointCatalog::C2B_SIMULATE,
            EndpointCatalog::STK_PUSH,
            EndpointCatalog::STK_QUERY,
            EndpointCatalog::REVERSAL,
            EndpointCatalog::TRANSACTION_STATUS,
            EndpointCatalog::ACCOUNT_BALANCE,
            EndpointCatalog::LIPA_NA_BONGA_REDEEM_PAYBILL,
            EndpointCatalog::LIPA_NA_BONGA_CALCULATE_POINTS,
            EndpointCatalog::IMSI_CHECK_ATI,
            EndpointCatalog::SWAP_CHECK_ATI,
            EndpointCatalog::PULL_REGISTER,
            EndpointCatalog::PULL_QUERY,
            EndpointCatalog::IOT_SEARCH_MESSAGES,
            EndpointCatalog::IOT_FILTER_MESSAGES,
            EndpointCatalog::IOT_DELETE_MESSAGE_THREAD,
            EndpointCatalog::IOT_GET_ALL_MESSAGES,
            EndpointCatalog::IOT_SEND_SINGLE_MESSAGE,
            EndpointCatalog::IOT_DELETE_MESSAGE,
            EndpointCatalog::IOT_ALL_SIMS,
            EndpointCatalog::IOT_QUERY_LIFECYCLE_STATUS,
            EndpointCatalog::IOT_QUERY_CUSTOMER_INFO,
            EndpointCatalog::IOT_SIM_ACTIVATION,
            EndpointCatalog::IOT_GET_ACTIVATION_TRENDS,
            EndpointCatalog::IOT_RENAME_ASSET,
            EndpointCatalog::IOT_GET_LOCATION_INFO,
            EndpointCatalog::IOT_SUSPEND_UNSUSPEND_SUB,
        );

        $this->assertCount(33, $endpoints);
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $endpoints);
            $this->assertTrue(EndpointCatalog::has($key));
            $this->assertContains($key, EndpointCatalog::keys());
        }

        $this->assertSame('/simportal/v1/suspend_unsuspend_sub', $endpoints[EndpointCatalog::IOT_SUSPEND_UNSUSPEND_SUB]['path']);
    }

    public function testEndpointMetadataContainsMethodAndPath()
    {
        foreach (EndpointCatalog::all() as $endpoint) {
            $this->assertArrayHasKey('method', $endpoint);
            $this->assertArrayHasKey('path', $endpoint);
            $this->assertContains($endpoint['method'], array('GET', 'POST'));
            $this->assertStringStartsWith('/', $endpoint['path']);
        }
    }

    public function testDarajaExceptionCarriesResponseContext()
    {
        $exception = DarajaException::forHttpResponse(400, array('errorMessage' => 'Bad request'), EndpointCatalog::STK_PUSH);

        $this->assertSame(400, $exception->getStatusCode());
        $this->assertSame(array('errorMessage' => 'Bad request'), $exception->getResponseData());
        $this->assertSame(EndpointCatalog::STK_PUSH, $exception->getEndpointKey());
        $this->assertTrue(strpos($exception->getMessage(), 'HTTP 400') !== false);
    }
}
