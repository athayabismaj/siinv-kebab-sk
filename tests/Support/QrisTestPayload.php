<?php

namespace Tests\Support;

final class QrisTestPayload
{
    public static function make(
        string $merchantName = 'KEBAB SK TEST',
        string $merchantCity = 'KUDUS',
        string $merchantId = 'TESTMERCHANT001',
    ): string {
        $merchantAccount = self::tlv('00', 'A000000677010111')
            .self::tlv('01', $merchantId);

        $withoutCrc = self::tlv('00', '01')
            .self::tlv('01', '11')
            .self::tlv('26', $merchantAccount)
            .self::tlv('52', '5812')
            .self::tlv('53', '360')
            .self::tlv('58', 'ID')
            .self::tlv('59', $merchantName)
            .self::tlv('60', $merchantCity)
            .'6304';

        return $withoutCrc.self::crc($withoutCrc);
    }

    private static function tlv(string $tag, string $value): string
    {
        return $tag.str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT).$value;
    }

    private static function crc(string $payload): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($payload); $i++) {
            $crc ^= ord($payload[$i]) << 8;
            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) !== 0
                    ? (($crc << 1) ^ 0x1021) & 0xFFFF
                    : ($crc << 1) & 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
