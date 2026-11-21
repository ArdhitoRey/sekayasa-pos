<?php

namespace App\Services;

class QrisService
{
    /**
     * Static QRIS Payload decoded from Merchant QRIS:
     * MUHHAMAD SATRIA RAMADHAN, DIGITAL & KREATIF (NMID: ID1026599048299, GoPay Merchant 936009143836669142)
     */
    public const DEFAULT_STATIC_QRIS = '00020101021126610014COM.GO-JEK.WWW01189360091438366691420210G8366691420303UMI51440014ID.CO.QRIS.WWW0215ID10265990482990303UMI5204899953033605802ID5925MUHHAMAD SATRIA RAMADHAN,6008PEMALANG61055237162070703A0163048F66';

    public const MERCHANT_NAME = 'MUHHAMAD SATRIA RAMADHAN, DIGITAL & KREATIF';
    public const MERCHANT_NMID = 'ID1026599048299';

    /**
     * Convert Static QRIS into Dynamic QRIS with exact locked amount (Tag 54) and valid CRC-16/CCITT-FALSE (Tag 63).
     */
    public static function generateDynamic(int $amount, ?string $staticQris = null): string
    {
        $qris = trim($staticQris ?: self::DEFAULT_STATIC_QRIS);

        // 1. Remove old CRC16 checksum (last 4 chars) & tag '6304' if present
        $crcPos = strrpos($qris, '6304');
        if ($crcPos !== false) {
            $qris = substr($qris, 0, $crcPos);
        }

        // 2. Switch Point of Initiation Method from Static (010211) to Dynamic (010212)
        $qris = preg_replace('/^000201010211/', '000201010212', $qris, 1);

        // 3. Remove any existing Tag 54 if present, then insert Tag 54 (Transaction Amount) before Tag 58 (Country Code 5802ID)
        $amountStr = (string) $amount;
        $amountLen = str_pad((string) strlen($amountStr), 2, '0', STR_PAD_LEFT);
        $tag54 = '54' . $amountLen . $amountStr;

        if (str_contains($qris, '5802ID')) {
            $parts = explode('5802ID', $qris, 2);
            $payloadWithoutCrc = $parts[0] . $tag54 . '5802ID' . $parts[1] . '6304';
        } else {
            $payloadWithoutCrc = $qris . $tag54 . '6304';
        }

        // 4. Calculate EMVCo CRC-16/CCITT-FALSE
        $crc = self::calculateCrc16($payloadWithoutCrc);

        return $payloadWithoutCrc . $crc;
    }

    /**
     * EMVCo CRC-16/CCITT-FALSE (Poly 0x1021, Init 0xFFFF)
     */
    public static function calculateCrc16(string $str): string
    {
        $crc = 0xFFFF;
        $len = strlen($str);

        for ($c = 0; $c < $len; $c++) {
            $crc ^= (ord($str[$c]) << 8);
            for ($i = 0; $i < 8; $i++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Extract integer IDR amount from incoming Android/GoPay notification text or numeric input.
     * Examples:
     *  - "Pembayaran QRIS sebesar Rp 99.347 berhasil diterima" -> 99347
     *  - "Rp199.412 masuk dari BCA" -> 199412
     *  - "99347" -> 99347
     */
    public static function extractAmountFromNotification(string $text): ?int
    {
        // Match Indonesian currency format like Rp 99.347 or Rp99.347,00 or IDR 199,412
        if (preg_match('/(?:rp|idr)\.?\s*([0-9][0-9\.,]*)/i', $text, $matches)) {
            $numStr = preg_replace('/[,\.]00$/', '', $matches[1]);
            $digits = preg_replace('/\D/', '', $numStr);
            if ($digits !== '') {
                return (int) $digits;
            }
        }

        // Fallback: match standalone number >= 1000
        if (preg_match('/\b([0-9]{1,3}(?:[.,][0-9]{3})+|[0-9]{4,})\b/', $text, $matches)) {
            $digits = preg_replace('/\D/', '', $matches[1]);
            if ($digits !== '') {
                return (int) $digits;
            }
        }

        return null;
    }
}
