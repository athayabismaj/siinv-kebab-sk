<?php

namespace App\Services;

use InvalidArgumentException;

class QrisService
{
    private const NESTED_TAGS = [
        '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38',
        '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51', '62',
    ];

    /**
     * @return array<int, array{tag:string,length:int,value:string,children?:array}>
     */
    public function parse(string $payload): array
    {
        return $this->parseTlv($this->normalizePayload($payload));
    }

    /**
     * @return array{valid:bool,errors:array<int,string>,merchant_name:?string,merchant_city:?string,method:?string}
     */
    public function validate(string $payload): array
    {
        $errors = [];
        $payload = $this->normalizePayload($payload);

        if ($payload === '') {
            return $this->validationResult(['Payload QRIS kosong.']);
        }

        if (! str_starts_with($payload, '000201')) {
            $errors[] = 'Payload QRIS harus diawali Payload Format Indicator 000201.';
        }

        if (strlen($payload) < 20) {
            $errors[] = 'Payload QRIS terlalu pendek.';
        }

        try {
            $elements = $this->parseTlv($payload);
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();

            return $this->validationResult($errors);
        }

        $byTag = [];
        foreach ($elements as $element) {
            $byTag[$element['tag']] = $element;
        }

        foreach (['00', '01', '52', '53', '58', '59', '60', '63'] as $requiredTag) {
            if (! isset($byTag[$requiredTag])) {
                $errors[] = "Field QRIS wajib tag {$requiredTag} tidak ditemukan.";
            }
        }

        $hasMerchantAccount = false;
        foreach (range(26, 51) as $merchantTag) {
            if (isset($byTag[(string) $merchantTag])) {
                $hasMerchantAccount = true;
                break;
            }
        }
        if (! $hasMerchantAccount) {
            $errors[] = 'Merchant Account Information (tag 26-51) tidak ditemukan.';
        }

        if (($byTag['00']['value'] ?? null) !== '01') {
            $errors[] = 'Payload Format Indicator QRIS tidak valid.';
        }

        $method = $byTag['01']['value'] ?? null;
        if ($method !== null && ! in_array($method, ['11', '12'], true)) {
            $errors[] = 'Point of Initiation Method harus bernilai 11 atau 12.';
        }

        if (isset($byTag['53']) && $byTag['53']['value'] !== '360') {
            $errors[] = 'Mata uang QRIS harus Rupiah (kode 360).';
        }

        if (isset($byTag['58']) && strtoupper($byTag['58']['value']) !== 'ID') {
            $errors[] = 'Country Code QRIS harus ID.';
        }

        $lastElement = end($elements);
        if (! is_array($lastElement) || $lastElement['tag'] !== '63' || $lastElement['length'] !== 4) {
            $errors[] = 'CRC QRIS (tag 63) harus menjadi field terakhir dengan panjang 4.';
        } elseif (strlen($payload) >= 4) {
            $declaredCrc = strtoupper(substr($payload, -4));
            $calculatedCrc = $this->calculateCrc(substr($payload, 0, -4));
            if (! hash_equals($calculatedCrc, $declaredCrc)) {
                $errors[] = 'Checksum CRC16 QRIS tidak valid.';
            }
        }

        $merchantName = trim((string) ($byTag['59']['value'] ?? ''));
        $merchantCity = trim((string) ($byTag['60']['value'] ?? ''));
        if ($merchantName === '') {
            $errors[] = 'Nama merchant QRIS kosong.';
        }
        if ($merchantCity === '') {
            $errors[] = 'Kota merchant QRIS kosong.';
        }

        return [
            'valid' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'merchant_name' => $merchantName !== '' ? $merchantName : null,
            'merchant_city' => $merchantCity !== '' ? $merchantCity : null,
            'method' => $method === '12' ? 'dynamic' : ($method === '11' ? 'static' : null),
        ];
    }

    public function generateDynamic(string $masterPayload, int|string $amount): string
    {
        $validation = $this->validate($masterPayload);
        if (! $validation['valid']) {
            throw new InvalidArgumentException('Konfigurasi QRIS tidak valid.');
        }

        $amount = $this->normalizeAmount($amount);
        $elements = $this->parse($masterPayload);
        $result = [];
        $amountInserted = false;

        foreach ($elements as $element) {
            if (in_array($element['tag'], ['54', '55', '56', '57', '63'], true)) {
                continue;
            }

            if ($element['tag'] === '01') {
                $element['value'] = '12';
                $element['length'] = 2;
            }

            if ($element['tag'] === '58' && ! $amountInserted) {
                $result[] = $this->element('54', $amount);
                $amountInserted = true;
            }

            $result[] = $element;
        }

        if (! $amountInserted) {
            throw new InvalidArgumentException('Country Code QRIS tidak ditemukan.');
        }

        $withoutCrc = $this->buildTlv($result);
        $crcInput = $withoutCrc.'6304';

        return $crcInput.$this->calculateCrc($crcInput);
    }

    private function normalizePayload(string $payload): string
    {
        return trim(str_replace("\xEF\xBB\xBF", '', $payload));
    }

    /**
     * @return array<int, array{tag:string,length:int,value:string,children?:array}>
     */
    private function parseTlv(string $data, int $depth = 0): array
    {
        if ($depth > 4) {
            throw new InvalidArgumentException('Struktur TLV QRIS terlalu dalam.');
        }

        $elements = [];
        $position = 0;
        $dataLength = strlen($data);

        while ($position < $dataLength) {
            if ($position + 4 > $dataLength) {
                throw new InvalidArgumentException('Header TLV QRIS tidak lengkap.');
            }

            $tag = substr($data, $position, 2);
            $lengthText = substr($data, $position + 2, 2);
            if (! ctype_digit($tag) || ! ctype_digit($lengthText)) {
                throw new InvalidArgumentException('Tag atau panjang TLV QRIS tidak valid.');
            }

            $length = (int) $lengthText;
            $valueStart = $position + 4;
            if ($valueStart + $length > $dataLength) {
                throw new InvalidArgumentException("Nilai TLV tag {$tag} tidak lengkap.");
            }

            $value = substr($data, $valueStart, $length);
            $element = [
                'tag' => $tag,
                'length' => $length,
                'value' => $value,
            ];

            if (in_array($tag, self::NESTED_TAGS, true) && $value !== '') {
                $element['children'] = $this->parseTlv($value, $depth + 1);
            }

            $elements[] = $element;
            $position = $valueStart + $length;
        }

        if ($position !== $dataLength) {
            throw new InvalidArgumentException('Struktur TLV QRIS memiliki data sisa yang tidak valid.');
        }

        return $elements;
    }

    /**
     * @param  array<int, array{tag:string,length:int,value:string,children?:array}>  $elements
     */
    private function buildTlv(array $elements): string
    {
        $payload = '';
        foreach ($elements as $element) {
            $value = $element['value'];
            $length = strlen($value);
            if ($length > 99) {
                throw new InvalidArgumentException("Nilai TLV tag {$element['tag']} terlalu panjang.");
            }
            $payload .= $element['tag'].str_pad((string) $length, 2, '0', STR_PAD_LEFT).$value;
        }

        return $payload;
    }

    /**
     * @return array{tag:string,length:int,value:string}
     */
    private function element(string $tag, string $value): array
    {
        return ['tag' => $tag, 'length' => strlen($value), 'value' => $value];
    }

    private function normalizeAmount(int|string $amount): string
    {
        $value = trim((string) $amount);
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Nominal QRIS harus berupa angka positif yang valid.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = rtrim($fraction, '0');
        $normalized = $fraction === '' ? $whole : $whole.'.'.$fraction;

        if ($normalized === '0' || strlen($normalized) > 13) {
            throw new InvalidArgumentException('Nominal QRIS harus lebih dari nol dan berada dalam batas transaksi.');
        }

        return $normalized;
    }

    private function calculateCrc(string $payload): string
    {
        $crc = 0xFFFF;

        for ($i = 0, $length = strlen($payload); $i < $length; $i++) {
            $crc ^= ord($payload[$i]) << 8;
            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) !== 0
                    ? (($crc << 1) ^ 0x1021) & 0xFFFF
                    : ($crc << 1) & 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc & 0xFFFF), 4, '0', STR_PAD_LEFT));
    }

    /**
     * @param  array<int,string>  $errors
     * @return array{valid:bool,errors:array<int,string>,merchant_name:null,merchant_city:null,method:null}
     */
    private function validationResult(array $errors): array
    {
        return [
            'valid' => false,
            'errors' => array_values(array_unique($errors)),
            'merchant_name' => null,
            'merchant_city' => null,
            'method' => null,
        ];
    }
}
