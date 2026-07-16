<?php

use PHPUnit\Framework\TestCase;
use Safaricom\Daraja\EndpointCatalog;

class EndpointCatalogTest extends TestCase
{
    public function testCollectionEndpointsAreRegistered()
    {
        $endpoints = EndpointCatalog::all();

        $this->assertArrayHasKey(EndpointCatalog::OAUTH_TOKEN, $endpoints);
        $this->assertArrayHasKey(EndpointCatalog::STK_PUSH, $endpoints);
        $this->assertArrayHasKey(EndpointCatalog::C2B_REGISTER_URL, $endpoints);
        $this->assertArrayHasKey(EndpointCatalog::PULL_QUERY, $endpoints);
        $this->assertArrayHasKey(EndpointCatalog::IOT_SUSPEND_UNSUSPEND_SUB, $endpoints);
        $this->assertSame('/simportal/v1/suspend_unsuspend_sub', $endpoints[EndpointCatalog::IOT_SUSPEND_UNSUSPEND_SUB]['path']);
    }
}
