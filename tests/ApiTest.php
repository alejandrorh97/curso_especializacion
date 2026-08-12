<?php

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/ApiResultRecorderTrait.php';

class ApiTest extends TestCase
{
    use ApiResultRecorderTrait;

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

        $this->recordHttpCall(
            method: 'POST',
            endpoint: 'auth',
            response: $response,
            expectedStatus: 200,
            importancia: 'Bloqueante',
            contexto: 'POST /auth - autenticación de usuario'
        );

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

    #[TestDox('POST /auth debe autenticar y devolver un token válido [Importancia: Bloqueante]')]
    public function testAuthenticateUser(): void
    {
        $this->assertNotEmpty($this->token);
    }

    /**
     * Construye un mensaje de fallo enriquecido para aserciones de status code HTTP.
     * Aparece en la salida de PHPUnit (normal o --testdox) solo cuando la aserción falla.
     */
    protected function httpStatusMessage(
        int $expected,
        int $actual,
        string $importancia = 'Mayor',
        string $contexto = ''
    ): string {
        return sprintf(
            '%s HTTP esperado: %s | HTTP obtenido: %s%s',
            $this->importanciaTag($importancia),
            $this->colorize((string) $expected, 'green'),
            $this->colorize((string) $actual, 'red'),
            $contexto !== '' ? " | Contexto: {$contexto}" : ''
        );
    }

    /**
     * Igual que httpStatusMessage(), pero para comparar cualquier campo del payload
     * (no solo status codes), documentando qué se esperaba vs qué llegó.
     */
    protected function fieldMismatchMessage(
        string $campo,
        mixed $expected,
        mixed $actual,
        string $importancia = 'Menor'
    ): string {
        return sprintf(
            '%s Campo "%s" - esperado: %s | obtenido: %s',
            $this->importanciaTag($importancia),
            $campo,
            $this->colorize($this->stringifyValue($expected), 'green'),
            $this->colorize($this->stringifyValue($actual), 'red')
        );
    }

    /**
     * Genera la etiqueta "[Importancia: X]" coloreada según severidad:
     * Bloqueante/Crítica -> rojo, Mayor -> amarillo, Menor -> cian.
     */
    private function importanciaTag(string $importancia): string
    {
        $color = match (strtolower($importancia)) {
            'bloqueante', 'crítica', 'critica' => 'red',
            'mayor' => 'yellow',
            'menor' => 'cyan',
            default => 'default',
        };

        return $this->colorize("[Importancia: {$importancia}]", $color, bold: true);
    }

    /**
     * Envuelve un texto en códigos ANSI si la terminal los soporta.
     * Respeta la variable de entorno NO_COLOR y detecta si STDOUT es un TTY.
     */
    private function colorize(string $text, string $color, bool $bold = false): string
    {
        if (getenv('NO_COLOR') !== false || !$this->terminalSupportsColor()) {
            return $text;
        }

        $codes = [
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'cyan' => '36',
            'default' => '39',
        ];

        $code = $codes[$color] ?? $codes['default'];
        $prefix = $bold ? "\033[1;{$code}m" : "\033[{$code}m";

        return $prefix . $text . "\033[0m";
    }

    private function terminalSupportsColor(): bool
    {
        if (function_exists('posix_isatty') && defined('STDOUT')) {
            return posix_isatty(STDOUT);
        }

        // Fallback si posix no está disponible: asume que sí, PHPUnit --colors=never lo puede forzar externamente.
        return true;
    }

    private function stringifyValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $value === null ? 'null' : (string) $value;
    }
}