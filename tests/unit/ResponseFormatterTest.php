<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Lib/ResponseFormatter.php';

class ResponseFormatterTest extends TestCase
{
    protected function setUp(): void
    {
        ResponseFormatter::setAutoTerminate(false);
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        ResponseFormatter::setAutoTerminate(true);
        http_response_code(200);
    }

    public function testSuccessOutputsExpectedStructure(): void
    {
        ob_start();
        ResponseFormatter::success(['foo' => 'bar'], 'Custom', 201);
        $output = ob_get_clean();

        $payload = json_decode($output, true);

        $this->assertNotNull($payload, 'Payload should be valid JSON');
        $this->assertTrue($payload['success']);
        $this->assertEquals('Custom', $payload['message']);
        $this->assertEquals(['foo' => 'bar'], $payload['data']);
        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertEquals(201, http_response_code());
    }

    public function testErrorWhenStatusProvidedAsSecondArgument(): void
    {
        ob_start();
        ResponseFormatter::error('Not found', 404);
        $output = ob_get_clean();

        $payload = json_decode($output, true);

        $this->assertNotNull($payload);
        $this->assertFalse($payload['success']);
        $this->assertEquals('Not found', $payload['message']);
        $this->assertSame([], $payload['errors']);
        $this->assertEquals(404, http_response_code());
        $this->assertArrayHasKey('timestamp', $payload);
    }

    public function testPaginatedResponseContainsMetadata(): void
    {
        ob_start();
        ResponseFormatter::paginated([['id' => 1]], 10, 1, 5, 'List');
        $output = ob_get_clean();

        $payload = json_decode($output, true);

        $this->assertNotNull($payload);
        $this->assertTrue($payload['success']);
        $this->assertEquals('List', $payload['message']);
        $this->assertSame([['id' => 1]], $payload['data']);
        $this->assertEquals([
            'total' => 10,
            'page' => 1,
            'per_page' => 5,
            'total_pages' => 2,
            'has_next' => true,
            'has_prev' => false,
        ], $payload['pagination']);
        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertEquals(200, http_response_code());
    }
}

