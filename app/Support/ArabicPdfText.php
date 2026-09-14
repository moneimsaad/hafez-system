<?php

namespace App\Support;

/**
 * Dompdf does not shape Arabic glyphs. This small presentation helper converts
 * the certificate's Arabic runs to their Unicode presentation forms and visual
 * order before the PDF renderer receives them.
 */
final class ArabicPdfText
{
    private const FORMS = [
        'ا' => ['\u{FE8D}', '\u{FE8E}'], 'أ' => ['\u{FE83}', '\u{FE84}'], 'إ' => ['\u{FE87}', '\u{FE88}'], 'آ' => ['\u{FE81}', '\u{FE82}'],
        'ب' => ['\u{FE8F}', '\u{FE90}', '\u{FE91}', '\u{FE92}'], 'ة' => ['\u{FE93}', '\u{FE94}'], 'ت' => ['\u{FE95}', '\u{FE96}', '\u{FE97}', '\u{FE98}'], 'ث' => ['\u{FE99}', '\u{FE9A}', '\u{FE9B}', '\u{FE9C}'],
        'ج' => ['\u{FE9D}', '\u{FE9E}', '\u{FE9F}', '\u{FEA0}'], 'ح' => ['\u{FEA1}', '\u{FEA2}', '\u{FEA3}', '\u{FEA4}'], 'خ' => ['\u{FEA5}', '\u{FEA6}', '\u{FEA7}', '\u{FEA8}'], 'د' => ['\u{FEA9}', '\u{FEAA}'],
        'ذ' => ['\u{FEAB}', '\u{FEAC}'], 'ر' => ['\u{FEAD}', '\u{FEAE}'], 'ز' => ['\u{FEAF}', '\u{FEB0}'], 'س' => ['\u{FEB1}', '\u{FEB2}', '\u{FEB3}', '\u{FEB4}'],
        'ش' => ['\u{FEB5}', '\u{FEB6}', '\u{FEB7}', '\u{FEB8}'], 'ص' => ['\u{FEB9}', '\u{FEBA}', '\u{FEBB}', '\u{FEBC}'], 'ض' => ['\u{FEBD}', '\u{FEBE}', '\u{FEBF}', '\u{FEC0}'], 'ط' => ['\u{FEC1}', '\u{FEC2}', '\u{FEC3}', '\u{FEC4}'],
        'ظ' => ['\u{FEC5}', '\u{FEC6}', '\u{FEC7}', '\u{FEC8}'], 'ع' => ['\u{FEC9}', '\u{FECA}', '\u{FECB}', '\u{FECC}'], 'غ' => ['\u{FECD}', '\u{FECE}', '\u{FECF}', '\u{FED0}'], 'ف' => ['\u{FED1}', '\u{FED2}', '\u{FED3}', '\u{FED4}'],
        'ق' => ['\u{FED5}', '\u{FED6}', '\u{FED7}', '\u{FED8}'], 'ك' => ['\u{FED9}', '\u{FEDA}', '\u{FEDB}', '\u{FEDC}'], 'ل' => ['\u{FEDD}', '\u{FEDE}', '\u{FEDF}', '\u{FEE0}'], 'م' => ['\u{FEE1}', '\u{FEE2}', '\u{FEE3}', '\u{FEE4}'],
        'ن' => ['\u{FEE5}', '\u{FEE6}', '\u{FEE7}', '\u{FEE8}'], 'ه' => ['\u{FEE9}', '\u{FEEA}', '\u{FEEB}', '\u{FEEC}'], 'و' => ['\u{FEED}', '\u{FEEE}'], 'ى' => ['\u{FEEF}', '\u{FEF0}'], 'ي' => ['\u{FEF1}', '\u{FEF2}', '\u{FEF3}', '\u{FEF4}'],
    ];

    public static function visual(?string $text): string
    {
        $tokens = preg_split('/(\s+)/u', trim((string) $text), -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        return implode('', array_map(
            static fn (string $token): string => preg_match('/^\s+$/u', $token) ? $token : self::shapeToken($token),
            array_reverse($tokens),
        ));
    }

    private static function shapeToken(string $token): string
    {
        $characters = preg_split('//u', preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $token) ?? '', -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (! array_filter($characters, static fn (string $character): bool => isset(self::FORMS[$character]))) {
            return $token;
        }
        $shaped = [];

        foreach ($characters as $index => $character) {
            if (! isset(self::FORMS[$character])) {
                $shaped[] = $character;
                continue;
            }

            $previous = $characters[$index - 1] ?? null;
            $next = $characters[$index + 1] ?? null;
            $joinsPrevious = isset(self::FORMS[$previous]) && count(self::FORMS[$previous]) === 4;
            $joinsNext = count(self::FORMS[$character]) === 4 && isset(self::FORMS[$next]);
            $forms = self::FORMS[$character];
            $shaped[] = self::unicode($forms[$joinsPrevious && $joinsNext ? 3 : ($joinsPrevious ? 1 : ($joinsNext ? 2 : 0))]);
        }

        return implode('', array_reverse($shaped));
    }

    private static function unicode(string $value): string
    {
        return preg_replace_callback(
            '/\\\\u\\{([0-9A-F]+)\\}/',
            static fn (array $match): string => mb_chr(hexdec($match[1]), 'UTF-8'),
            $value,
        ) ?? $value;
    }
}
