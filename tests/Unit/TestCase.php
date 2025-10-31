<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Tests\Unit;

use Mailrify\Sdk\Config;
use Mailrify\Sdk\Http\HttpClient;
use Mailrify\Sdk\Tests\Fixtures\FakeTransport;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createHttpClient(FakeTransport $transport): HttpClient
    {
        $config = new Config('test_api_key', 'https://api.test');

        return new HttpClient($config, $transport);
    }
}
