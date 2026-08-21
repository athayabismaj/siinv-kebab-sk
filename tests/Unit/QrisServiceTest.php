<?php

namespace Tests\Unit;

use App\Services\QrisService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\QrisTestPayload;

class QrisServiceTest extends TestCase
{
    private QrisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QrisService;
    }

    #[Test]
    public function it_parses_and_validates_a_synthetic_static_qris(): void
    {
        $payload = QrisTestPayload::make();
        $validation = $this->service->validate($payload);
        $elements = collect($this->service->parse($payload))->keyBy('tag');

        $this->assertTrue($validation['valid'], implode(' ', $validation['errors']));
        $this->assertSame('KEBAB SK TEST', $validation['merchant_name']);
        $this->assertSame('KUDUS', $validation['merchant_city']);
        $this->assertSame('static', $validation['method']);
        $this->assertSame('11', $elements->get('01')['value']);
    }

    #[Test]
    public function it_rejects_empty_malformed_and_invalid_crc_payloads(): void
    {
        $this->assertFalse($this->service->validate('')['valid']);
        $this->assertFalse($this->service->validate('not-qris')['valid']);

        $payload = QrisTestPayload::make();
        $corrupted = substr($payload, 0, -4).'0000';
        $result = $this->service->validate($corrupted);

        $this->assertFalse($result['valid']);
        $this->assertContains('Checksum CRC16 QRIS tidak valid.', $result['errors']);
    }

    #[Test]
    public function it_converts_static_qris_to_dynamic_with_database_style_amount_and_new_valid_crc(): void
    {
        $dynamic = $this->service->generateDynamic(QrisTestPayload::make(), '25000.00');
        $elements = collect($this->service->parse($dynamic))->keyBy('tag');
        $validation = $this->service->validate($dynamic);

        $this->assertSame('12', $elements->get('01')['value']);
        $this->assertSame('25000', $elements->get('54')['value']);
        $this->assertTrue($validation['valid'], implode(' ', $validation['errors']));
        $this->assertSame('dynamic', $validation['method']);
        $this->assertMatchesRegularExpression('/6304[A-F0-9]{4}$/', $dynamic);
    }

    #[Test]
    public function it_replaces_an_existing_amount_and_rejects_non_positive_amount(): void
    {
        $first = $this->service->generateDynamic(QrisTestPayload::make(), 10000);
        $second = $this->service->generateDynamic($first, '30000');
        $amountTags = collect($this->service->parse($second))->where('tag', '54');

        $this->assertCount(1, $amountTags);
        $this->assertSame('30000', $amountTags->first()['value']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->generateDynamic(QrisTestPayload::make(), 0);
    }
}
