<?php

namespace CrossKnowledge\SubConverterBundle\Providers;

/**
 * Web VTT subtitles class
 */
class WebVttSubtitles extends Subtitles
{
    /**
     * Return true if the provided file is in the current format
     * @param string $filename
     * @return boolean
     */
    public function checkFormat($filename)
    {
        $contents = str_replace("\r", "", self::removeBom(file_get_contents($filename)));

        return preg_match("/^WEBVTT\n+/mU", $contents, $matches);
    }

    /**
     * Import the provided file
     * @param string $filename
     * @return Subtitles
     * @throws \Exception
     */
    public function import($filename)
    {
        if (!$this->checkFormat($filename)) {
            throw new \Exception("Invalid WebVTT file: ".basename($filename));
        }

        $contents = str_replace("\r", "", self::forceUtf8(file_get_contents($filename)));

        preg_match_all(
            "/([0-9]+)[[:space:]]*\n".
            "((?P<startHours>[0-9]{2}):)?(?P<startMinutes>[0-9]{2}):(?P<startSeconds>[0-9]{2})\.(?P<startMilliseconds>[0-9]{3}) --> ((?P<endHours>[0-9]{2}):)?(?P<endMinutes>[0-9]{2}):(?P<endSeconds>[0-9]{2})\.(?P<endMilliSeconds>[0-9]{3})[[:space:]]*\n".
            "(?P<subtitle>.+\n\n|.+\Z)/smU",
            $contents,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            throw new \Exception("Invalid WebVTT file: ".basename($filename));
        }

        $this->subtitles = [];


        foreach ($matches as $aMatch) {

            $timeFromHour = empty($aMatch['startHours']) ? 0 : $aMatch['startHours'];
            $timeEndHour  = empty($aMatch['endHours']) ? 0 : $aMatch['endHours'];

            $timeFrom = 3600 * $timeFromHour + 60 * $aMatch['startMinutes'] + $aMatch['startSeconds'] + (float)('0.'.$aMatch['startMilliseconds']);
            $timeTo   = 3600 * $timeEndHour + 60 * $aMatch['endMinutes'] + $aMatch['endSeconds'] + (float)('0.'.$aMatch['endMilliSeconds']);
            $text     = trim($aMatch['subtitle'], " \t\r\n");

            $this->subtitles[] = [
                'from' => $timeFrom,
                'to'   => $timeTo,
                'text' => self::textToHtml($text),
            ];
        }

        return $this;
    }

    /**
     * Export the subtitles in the current format
     * @param boolean $bom Add UTF-8 BOM
     * @return string
     */
    public function export($bom = false)
    {
        $webvtt = "WEBVTT\n\n";

        $i = 1;
        foreach ($this->subtitles as $row) {
            $webvtt .= "$i\n";
            $webvtt .= self::formatSeconds($row['from'], '.', 3).' --> '.self::formatSeconds($row['to'], '.', 3)."\n";
            $webvtt .= self::htmlToText($row['text'])."\n\n";

            $i++;
        }

        if ($bom) {
            $webvtt = self::addUtf8Bom($webvtt);
        }

        return $webvtt;
    }

    /**
     * Return file extension for the current format
     * @return string
     */
    public function getFileExt()
    {
        return 'vtt';
    }
}

?>

