<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Http;

use Mailrify\Sdk\Config;
use Mailrify\Sdk\Exceptions\ValidationException;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface as SymfonyHttpClientInterface;

final class HttpClientFactory
{
    public static function createTransport(
        Config $config,
        ?SymfonyHttpClientInterface $symfonyClient = null,
        ?Psr18ClientInterface $psrClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ): TransportInterface {
        if ($psrClient !== null) {
            if ($requestFactory === null || $streamFactory === null) {
                throw new ValidationException('PSR-18 usage requires request and stream factories.');
            }

            return new Psr18Transport($psrClient, $requestFactory, $streamFactory);
        }

        $symfonyClient ??= SymfonyHttpClient::create([
            'timeout' => $config->getTimeout(),
        ]);

        return new SymfonyTransport($symfonyClient);
    }
}
