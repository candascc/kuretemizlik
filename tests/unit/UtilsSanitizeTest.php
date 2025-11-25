<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Utils.php';

final class UtilsSanitizeTest extends TestCase
{
    public function testNormalizePhoneFormatsToInternational(): void
    {
        $this->assertSame('+905551112233', Utils::normalizePhone('0555 111 22 33'));
        $this->assertSame('+905551112233', Utils::normalizePhone('+90 (555) 111 22 33'));
        $this->assertNull(Utils::normalizePhone(''));
    }

    public function testFormatPhoneProducesReadableOutput(): void
    {
        $this->assertSame('+90 555 111 22 33', Utils::formatPhone('0555 111 22 33'));
        $this->assertSame('', Utils::formatPhone(null));
    }

    public function testTruncateUtf8PreservesCharacters(): void
    {
        $text = 'Şişli Belediyesi toplantısı hakkında bilgilendirme yapılacaktır.';
        $truncated = Utils::truncateUtf8($text, 20);
        $this->assertSame('Şişli Belediyesi top…', $truncated);
    }

    public function testNormalizeMoneyHandlesDifferentFormats(): void
    {
        $this->assertSame(1250.5, Utils::normalizeMoney('1.250,50'));
        $this->assertSame(9876543.21, Utils::normalizeMoney('9 876 543,21'));
        $this->assertSame(0.0, Utils::normalizeMoney('abc'));
    }
}

