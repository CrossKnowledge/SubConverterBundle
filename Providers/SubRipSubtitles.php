<?php

namespace CrossKnowledge\SubConverterBundle\Providers;

use Exception;

/**
 * Sub Rip subtitles class
 */
class SubRipSubtitles extends Subtitles
{
    /**
     * @inheritDoc
     */
    public function checkFormat(string $filename): bool
    {
        $contents = str_replace("\r", "", self::removeBom(file_get_contents($filename)));

        return preg_match(
            "/^([0-9]+[[:space:]]*\n" .
            "[0-9]{2}:[0-9]{2}:[0-9]{2},[0-9]{3} --> [0-9]{2}:[0-9]{2}:[0-9]{2},[0-9]{3}[[:space:]]*\n" .
            "(.+\n\n|.+\Z))+/smU",
            $contents
        );
    }

    /**
     * @inheritDoc
     */
    public function import(string $filename): Subtitles
    {
        if (!$this->checkFormat($filename)) {
            throw new Exception('Invalid SubRip file: ' . basename($filename));
        }

        $contents = str_replace("\r", '', self::forceUtf8(file_get_contents($filename)));

        preg_match_all(
            "/^([0-9]+)[[:space:]]*\n" .
            "([0-9]{2}):([0-9]{2}):([0-9]{2}),([0-9]{3}) --> ([0-9]{2}):([0-9]{2}):([0-9]{2}),([0-9]{3})[[:space:]]*\n" .
            "(.+\n\n|.+\Z)/smU",
            $contents,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            throw new Exception('Invalid SubRip file: ' . basename($filename));
        }

        $this->subtitles = [];

        foreach ($matches as $aMatch) {
            $timeFrom = 3600 * $aMatch[2] + 60 * $aMatch[3] + $aMatch[4] + ('0.' . $aMatch[5]);
            $timeTo = 3600 * $aMatch[6] + 60 * $aMatch[7] + $aMatch[8] + ('0.' . $aMatch[9]);
            $text = trim($aMatch[10], " \t\r\n");

            $this->subtitles[] = [
                'from' => $timeFrom,
                'to' => $timeTo,
                'text' => self::textToHtml($text),
            ];
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function export(bool $bom = false): string
    {
        $srt = '';

        $i = 1;
        foreach ($this->subtitles as $row) {
            $srt .= "$i\n";
            $srt .= self::formatSeconds($row['from'], ',', 3) . ' --> ';
            $srt .= self::formatSeconds($row['to'], ',', 3) . "\n";
            $srt .= self::htmlToText($row['text']) . "\n\n";

            $i++;
        }

        if ($bom) {
            $srt = self::addUtf8Bom($srt);
        }

        return $srt;
    }

    /**
     * @inheritDoc
     */
    public function getFileExt(): string
    {
        return 'srt';
    }
}
