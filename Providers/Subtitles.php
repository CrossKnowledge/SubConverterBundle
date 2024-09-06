<?php

namespace CrossKnowledge\SubConverterBundle\Providers;

abstract class Subtitles
{
    /**
     * Language of the subtitles
     */
    public ?string $language = null;

    /**
     * Video framerate
     */
    public ?float $framerate = null;

    /**
     * Subtitles array. Row keys:
     * - from: Appearing time, in seconds
     * - to:   Disappearing time, in seconds
     * - text: Subtitle text, in HTML
     *
     * @var array
     */
    public array $subtitles = [];

    /**
     * Returns true if the provided file is in the current format.
     */
    public abstract function checkFormat(string $filename): bool;

    /**
     * Import the provided file
     * @throws \Exception
     */
    public abstract function import(string $filename): Subtitles;

    /**
     * Export the subtitles in the current format
     *
     * @param boolean $bom Add UTF-8 BOM
     *
     * @return string
     */
    public abstract function export(bool $bom = false): string;

    /**
     * Return file extension for the current format
     */
    public abstract function getFileExt(): string;

    /**
     * Convert the current subtitles instance to the given type.
     */
    public function convert(string $type): Subtitles
    {
        $types = SubtitlesFactory::getImplementations();
        $target = SubtitlesFactory::getInstance($types[$type]);

        // Merge properties of both objects
        $targetVars = get_object_vars($target);
        foreach (get_object_vars($this) as $k => $v) {
            if (array_key_exists($k, $targetVars)) {
                $target->$k = $v;
            }
        }

        return $target;
    }

    /**
     * Format the amount of seconds into hh:mm:ss.ss format
     */
    public static function formatSeconds(float $seconds, string $decimalSeparator = '.', int $decimals = 0): string
    {
        $h = floor($seconds / 3600);
        $seconds -= $h * 3600;
        $m = floor($seconds / 60);
        $seconds -= $m * 60;
        $s = floor($seconds);
        $seconds -= $s;
        $ms = str_replace('0.', '', number_format($seconds, $decimals, '.', ''));

        $timestamp =
            str_pad($h, 2, '0', STR_PAD_LEFT) . ':' .
            str_pad($m, 2, '0', STR_PAD_LEFT) . ':' .
            str_pad($s, 2, '0', STR_PAD_LEFT);

        if ($ms || $decimals) {
            $timestamp .= $decimalSeparator . str_pad($ms, $decimals, '0', STR_PAD_RIGHT);
        }

        return $timestamp;
    }

    /**
     * Format the amount of seconds into hh:mm:ss.ff format
     */
    public static function formatTimecode(float $seconds, float $fps, string $framesSeparator = '.'): string
    {
        $h = floor($seconds / 3600);
        $seconds -= $h * 3600;
        $m = floor($seconds / 60);
        $seconds -= $m * 60;
        $s = floor($seconds);
        $seconds -= $s;
        $f = round($seconds * $fps);

        return str_pad($h, 2, '0', STR_PAD_LEFT) . ':' .
            str_pad($m, 2, '0', STR_PAD_LEFT) . ':' .
            str_pad($s, 2, '0', STR_PAD_LEFT) . $framesSeparator .
            str_pad($f, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Convert plain text to HTML
     */
    public static function textToHtml(string $str): string
    {
        return str_replace("\n", '', nl2br($str));
    }

    /**
     * Removes and converts the HTML into plain text
     */
    public static function htmlToText(string $str): string
    {
        $str = preg_replace("/[\r\n\t ]+/", " ", $str);
        $str = preg_replace('/<\\/?p[^>]*>/i', "\n", $str);
        $str = preg_replace('/<(br|hr)[^>]*>/i', "\n", $str);
        $str = html_entity_decode(strip_tags($str), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');

        return trim($str, " \t\r\n");
    }

    /**
     * Convert the string into UTF8, without BOM
     */
    public static function forceUtf8(string $str): string
    {
        $encoding = mb_detect_encoding($str, 'UTF-8, LATIN1, ASCII');
        if ($encoding != 'UTF-8') {
            return iconv($encoding, 'UTF-8', self::removeBom($str));
        }

        return self::removeBom($str);
    }

    /**
     * Remove UTF BOMs from string.
     */
    public static function removeBom(string $str): string
    {
        $boms = [
            // UTF-8
            chr(0xEF) . chr(0xBB) . chr(0xBF),
            // UTF-32 (BE)
            chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF),
            // UTF-32 (LE)
            chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00),
            // UTF-16 (BE)
            chr(0xFE) . chr(0xFF),
            // UTF-16 (LE)
            chr(0xFF) . chr(0xFE),
            // UTF-7
            '+/v8',
            '+/v9',
            '+/v+',
            '+/v/',
            '+/v8-',
            // UTF-1
            chr(0xF7) . chr(0x64) . chr(0x4C),
            // UTF-EBCDIC
            chr(0xDD) . chr(0x73) . chr(0x66) . chr(0x73),
            // SCSU
            chr(0x0E) . chr(0xFE) . chr(0xFF),
            // BOCU-1
            chr(0xFB) . chr(0xEE) . chr(0x28),
            // GB-18030
            chr(0x84) . chr(0x31) . chr(0x95) . chr(0x33),
        ];

        return preg_replace(
            '/^('.implode('|', array_map('preg_quote', $boms, array_fill(0, count($boms), '/'))).')/',
            '',
            $str
        );
    }

    /**
     * Add the UTF-8 BOM at the beginning of the string
     */
    public static function addUtf8Bom(string $str): string
    {
        return chr(0xEF).chr(0xBB).chr(0xBF).$str;
    }
}
