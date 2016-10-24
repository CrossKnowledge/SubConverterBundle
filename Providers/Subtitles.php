<?php

namespace CrossKnowledge\SubConverterBundle\Providers;

/**
 * Subtitles class
 */
abstract class Subtitles
{
    /**
     * Language of the subtitles
     * @var string
     */
    public $language = null;

    /**
     * Video framerate
     * @var float
     */
    public $framerate = null;

    /**
     * Subtitles array. Row keys:
     * - from: Appearing time, in seconds
     * - to:   Disappearing time, in seconds
     * - text: Subtitle text, in HTML
     *
     * @var array
     */
    public $subtitles = array();


    /**
     * Return true if the provided file is in the current format
     * @param string $filename
     * @return boolean
     */
    public abstract function checkFormat($filename);

    /**
     * Import the provided file
     * @param string $filename
     * @return Subtitles
     * @throws \Exception
     */
    public abstract function import($filename);

    /**
     * Export the subtitles in the current format
     * @param boolean $bom Add UTF-8 BOM
     * @return string
     */
    public abstract function export($bom = false);

    /**
     * Return file extension for the current format
     * @return string
     */
    public abstract function getFileExt();

    /**
     * Convert the current subtitles instance to the given type.
     * @param string $type
     * @return Subtitles
     */
    public function convert($type)
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
     * @param float $seconds
     * @param string $decimalSeparator
     * @param int $decimals
     * @return string
     */
    public static function formatSeconds($seconds, $decimalSeparator = '.', $decimals = 0)
    {
        $h = floor($seconds / 3600);
        $seconds -= $h * 3600;
        $m = floor($seconds / 60);
        $seconds -= $m * 60;
        $s = floor($seconds);
        $seconds -= $s;
        $ms = str_replace('0.', '', number_format($seconds, $decimals, '.', ''));

        $timestamp =
            str_pad($h, 2, '0', STR_PAD_LEFT).':'.
            str_pad($m, 2, '0', STR_PAD_LEFT).':'.
            str_pad($s, 2, '0', STR_PAD_LEFT);

        if ($ms || $decimals) {
            $timestamp .= $decimalSeparator.str_pad($ms, $decimals, '0', STR_PAD_RIGHT);
        }

        return $timestamp;
    }

    /**
     * Format the amount of seconds into hh:mm:ss.ff format
     * @param float $seconds
     * @param float $fps
     * @param string $framesSeparator
     * @return string
     */
    public static function formatTimecode($seconds, $fps, $framesSeparator = '.')
    {
        $h = floor($seconds / 3600);
        $seconds -= $h * 3600;
        $m = floor($seconds / 60);
        $seconds -= $m * 60;
        $s = floor($seconds);
        $seconds -= $s;
        $f = round($seconds * $fps);

        $timestamp =
            str_pad($h, 2, '0', STR_PAD_LEFT).':'.
            str_pad($m, 2, '0', STR_PAD_LEFT).':'.
            str_pad($s, 2, '0', STR_PAD_LEFT).$framesSeparator.
            str_pad($f, 2, '0', STR_PAD_LEFT);

        return $timestamp;
    }

    /**
     * Convert plain text to HTML
     * @param string $str
     * @return string
     */
    public static function textToHtml($str)
    {
        return str_replace("\n", "", nl2br(htmlspecialchars($str)));
    }

    /**
     * Remove and convert the HTML into plain text
     * @param string $str
     * @return string
     */
    public static function htmlToText($str)
    {
        $str = preg_replace("/[\r\n\t ]+/", " ", $str);
        $str = preg_replace('/<\\/?p[^>]*>/i', "\n", $str);
        $str = preg_replace('/<(br|hr)[^>]*>/i', "\n", $str);
        $str = html_entity_decode(strip_tags($str), null, 'UTF-8');

        return trim($str, " \t\r\n");
    }

    /**
     * Convert the string into UTF8, without BOM
     * @param string $str
     * @return string
     */
    public static function forceUtf8($str)
    {
        $encoding = mb_detect_encoding($str, 'UTF-8, LATIN1, ASCII');
        if ($encoding != 'UTF-8') {
            return iconv($encoding, 'UTF-8', self::removeBom($str));
        }

        return self::removeBom($str);
    }

    /**
     * Remove UTF BOMs from string.
     * @param string $str
     * @return string
     */
    public static function removeBom($str)
    {
        $boms = array(
            // UTF-8
            chr(0xEF).chr(0xBB).chr(0xBF),
            // UTF-32 (BE)
            chr(0x00).chr(0x00).chr(0xFE).chr(0xFF),
            // UTF-32 (LE)
            chr(0xFF).chr(0xFE).chr(0x00).chr(0x00),
            // UTF-16 (BE)
            chr(0xFE).chr(0xFF),
            // UTF-16 (LE)
            chr(0xFF).chr(0xFE),
            // UTF-7
            '+/v8',
            '+/v9',
            '+/v+',
            '+/v/',
            '+/v8-',
            // UTF-1
            chr(0xF7).chr(0x64).chr(0x4C),
            // UTF-EBCDIC
            chr(0xDD).chr(0x73).chr(0x66).chr(0x73),
            // SCSU
            chr(0x0E).chr(0xFE).chr(0xFF),
            // BOCU-1
            chr(0xFB).chr(0xEE).chr(0x28),
            // GB-18030
            chr(0x84).chr(0x31).chr(0x95).chr(0x33),
        );

        return preg_replace(
            '/^('.implode('|', array_map('preg_quote', $boms, array_fill(0, count($boms), '/'))).')/',
            '',
            $str
        );
    }

    /**
     * Add the UTF-8 BOM at the beginning of the string
     * @param string $str
     * @return string
     */
    public static function addUtf8Bom($str)
    {
        return chr(0xEF).chr(0xBB).chr(0xBF).$str;
    }
}

?>
