<?php

declare(strict_types=1);

namespace Mailrify\Sdk;

use Mailrify\Sdk\Exceptions\ValidationException;
use Mailrify\Sdk\Http\HttpClient;
use Mailrify\Sdk\Http\HttpClientFactory;
use Mailrify\Sdk\Http\TransportInterface;
use Mailrify\Sdk\Resources\CampaignsApi;
use Mailrify\Sdk\Resources\ContactsApi;
use Mailrify\Sdk\Resources\DomainsApi;
use Mailrify\Sdk\Resources\EmailsApi;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface as SymfonyHttpClientInterface;

final class Client
{
    public const VERSION = '0.1.0';

    private readonly Config $config;

    private readonly HttpClient $httpClient;

    private ?DomainsApi $domainsApi = null;

    private ?EmailsApi $emailsApi = null;

    private ?ContactsApi $contactsApi = null;

    private ?CampaignsApi $campaignsApi = null;

    /**
     * @param array{apiKey?: string, baseUrl?: string, timeout?: float|int, maxRetries?: int, debug?: bool, userAgent?: string|null, psrClient?: Psr18ClientInterface, requestFactory?: RequestFactoryInterface, streamFactory?: StreamFactoryInterface, symfonyClient?: SymfonyHttpClientInterface} $options
     */
    public static function create(array $options = []): self
    {
        $transportOptions = [
            'psrClient' => $options['psrClient'] ?? null,
            'requestFactory' => $options['requestFactory'] ?? null,
            'streamFactory' => $options['streamFactory'] ?? null,
            'symfonyClient' => $options['symfonyClient'] ?? null,
        ];

        /** @var array{apiKey?: string, baseUrl?: string, timeout?: float|int, maxRetries?: int, debug?: bool, userAgent?: string|null} $configOptions */
        $configOptions = [];

        if (array_key_exists('apiKey', $options) && is_string($options['apiKey'])) {
            $configOptions['apiKey'] = $options['apiKey'];
        }

        if (array_key_exists('baseUrl', $options) && is_string($options['baseUrl'])) {
            $configOptions['baseUrl'] = $options['baseUrl'];
        }

        if (array_key_exists('timeout', $options)) {
            $timeout = $options['timeout'];
            if (is_int($timeout) || is_float($timeout)) {
                $configOptions['timeout'] = $timeout;
            } elseif (is_numeric($timeout)) {
                $configOptions['timeout'] = (float) $timeout;
            }
        }

        if (array_key_exists('maxRetries', $options) && is_numeric($options['maxRetries'])) {
            $configOptions['maxRetries'] = (int) $options['maxRetries'];
        }

        if (array_key_exists('debug', $options)) {
            $configOptions['debug'] = (bool) $options['debug'];
        }

        if (array_key_exists('userAgent', $options)) {
            $configOptions['userAgent'] = is_string($options['userAgent']) ? $options['userAgent'] : null;
        }

        $configOptions['userAgent'] = $configOptions['userAgent'] ?? sprintf('mailrify-php/%s', self::VERSION);

        $config = Config::fromArray($configOptions);
        $transport = self::createTransport($config, $transportOptions);
        $httpClient = new HttpClient($config, $transport);

        return new self($config, $httpClient);
    }

    public function __construct(Config $config, ?HttpClient $httpClient = null)
    {
        $this->config = $config;
        $this->httpClient = $httpClient ?? new HttpClient($config);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @param array{query?: array<string, int|float|string|bool|list<string>|null>, json?: array<string, mixed>|list<mixed>|null, headers?: array<string, string>} $options
     */
    public function request(string $method, string $path, array $options = []): mixed
    {
        return $this->httpClient->request($method, $path, $options);
    }

    public function domains(): DomainsApi
    {
        return $this->domainsApi ??= new DomainsApi($this->httpClient);
    }

    public function emails(): EmailsApi
    {
        return $this->emailsApi ??= new EmailsApi($this->httpClient);
    }

    public function contacts(): ContactsApi
    {
        return $this->contactsApi ??= new ContactsApi($this->httpClient);
    }

    public function campaigns(): CampaignsApi
    {
        return $this->campaignsApi ??= new CampaignsApi($this->httpClient);
    }

    /**
     * @param array{psrClient?: Psr18ClientInterface|null, requestFactory?: RequestFactoryInterface|null, streamFactory?: StreamFactoryInterface|null, symfonyClient?: SymfonyHttpClientInterface|null} $options
     */
    private static function createTransport(Config $config, array $options): TransportInterface
    {
        $psrClient = $options['psrClient'] ?? null;
        $requestFactory = $options['requestFactory'] ?? null;
        $streamFactory = $options['streamFactory'] ?? null;
        $symfonyClient = $options['symfonyClient'] ?? null;

        if ($psrClient !== null && ($requestFactory === null || $streamFactory === null)) {
            throw new ValidationException('Using a PSR-18 client requires both requestFactory and streamFactory.');
        }

        return HttpClientFactory::createTransport($config, $symfonyClient, $psrClient, $requestFactory, $streamFactory);
    }
}
