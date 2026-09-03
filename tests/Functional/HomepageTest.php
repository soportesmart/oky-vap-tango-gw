<?php

namespace Tests\Functional;

class HomepageTest extends BaseTestCase
{
    protected $withMiddleware = false;

    public function testGetHealthCheck()
    {
        $response = $this->runApp('GET', '/test');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Slim 3 Test', (string)$response->getBody());
    }
}
