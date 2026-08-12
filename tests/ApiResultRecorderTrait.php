<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;

/**
 * Trait que, además de dejar el mensaje de fallo normal en consola, registra
 * en memoria toda la información recibida (status, headers, body, comparaciones
 * de campos) y al final de la corrida completa la vuelca en:
 *
 *   test-results.json  (en la raíz del proyecto, junto a bootstrap.php)
 */
trait ApiResultRecorderTrait
{
    /** @var array<int, array<string, mixed>> */
    private static array $capturedResults = [];

    private static bool $shutdownHandlerRegistered = false;

    /**
     * Registra una llamada HTTP completa (status, headers, body) y hace la
     * aserción del status code esperado vs obtenido.
     */
    protected function recordHttpCall(
        string $method,
        string $endpoint,
        ResponseInterface $response,
        int $expectedStatus,
        string $importancia,
        string $contexto = ''
    ): void {
        $this->ensureShutdownHandlerRegistered();

        $actualStatus = $response->getStatusCode();
        $bodyRaw = (string) $response->getBody();
        $bodyDecoded = json_decode($bodyRaw, true);

        self::$capturedResults[] = [
            'tipo' => 'http_call',
            'test' => static::class . '::' . $this->currentTestName(),
            'timestamp' => date('c'),
            'metodo' => $method,
            'endpoint' => $endpoint,
            'importancia' => $importancia,
            'contexto' => $contexto,
            'http_esperado' => $expectedStatus,
            'http_obtenido' => $actualStatus,
            'resultado' => $expectedStatus === $actualStatus ? 'PASS' : 'FAIL',
            'headers_recibidos' => $response->getHeaders(),
            'body_recibido' => $bodyDecoded !== null ? $bodyDecoded : $bodyRaw,
        ];

        $this->assertSame(
            $expectedStatus,
            $actualStatus,
            $this->httpStatusMessage($expectedStatus, $actualStatus, $importancia, $contexto)
        );
    }

    /**
     * Registra la comparación de un campo individual del payload (esperado vs
     * obtenido) y hace la aserción correspondiente.
     */
    protected function recordFieldCheck(
        string $campo,
        mixed $expected,
        mixed $actual,
        string $importancia = 'Menor'
    ): void {
        $this->ensureShutdownHandlerRegistered();

        self::$capturedResults[] = [
            'tipo' => 'field_check',
            'test' => static::class . '::' . $this->currentTestName(),
            'timestamp' => date('c'),
            'campo' => $campo,
            'importancia' => $importancia,
            'esperado' => $expected,
            'obtenido' => $actual,
            'resultado' => $expected === $actual ? 'PASS' : 'FAIL',
        ];

        $this->assertSame(
            $expected,
            $actual,
            $this->fieldMismatchMessage($campo, $expected, $actual, $importancia)
        );
    }

    private function currentTestName(): string
    {
        $nameMethod = method_exists($this, 'name') ? 'name' : 'getName';

        return (string) $this->$nameMethod();
    }

    private function ensureShutdownHandlerRegistered(): void
    {
        if (self::$shutdownHandlerRegistered) {
            return;
        }

        self::$shutdownHandlerRegistered = true;

        register_shutdown_function(static function (): void {
            $outputFile = dirname(__DIR__) . '/test-results.json';

            file_put_contents(
                $outputFile,
                json_encode(
                    self::$capturedResults,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
        });
    }
}