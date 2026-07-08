<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    private Client $client;
    private string $token;

    protected function setUp(): void
    {
        $baseUrl = getenv('API_BASE_URL') ?: 'https://restful-booker.herokuapp.com/';

        $this->client = new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            ],
        ]);

        $this->token = $this->authenticateUser();
    }

    private function authenticateUser(): string
    {
        $username = getenv('API_USERNAME') ?: 'admin';
        $password = getenv('API_PASSWORD') ?: 'password123';

        $response = $this->client->post('auth', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'username' => $username,
                'password' => $password,
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('token', $payload);
        $this->assertNotEmpty($payload['token']);

        return (string) $payload['token'];
    }

    public function authHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Cookie' => 'token=' . $this->token,
        ];
    }

    protected function client(): Client
    {
        return $this->client;
    }

    public function testAuthenticateUser(): void
    {
        $this->assertNotEmpty($this->token);
    }
}
