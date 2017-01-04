<?php

namespace CrossKnowledge\SubConverterBundle\Providers;

/**
 * Plain text subtitles class
 */
class TxtSubtitles extends Subtitles
{
    /**
     * Return true if the provided file is in the current format
     * @param string $filename
     * @return boolean
     */
    public function checkFormat($filename)
    {
        $contents = str_replace("\r", "", self::removeBom(file_get_contents($filename)));

        return preg_match(
            "/([0-9]+\)[[:space:]]*[0-9]{2}:[0-9]{2}:[0-9]{2}:[0-9]{2}[[:space:]]+[0-9]{2}:[0-9]{2}:[0-9]{2}:[0-9]{2}.*\n" .
            "(.+\n\n|.+\Z))+/mU",
            $contents
        );
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
            throw new \Exception("Invalid text file: ".basename($filename));
        }

        $contents = str_replace("\r", "", self::forceUtf8(file_get_contents($filename)));

        preg_match_all(
            "/([0-9]+\)[[:space:]]*([0-9]{2}):([0-9]{2}):([0-9]{2}):([0-9]{2})[[:space:]]+([0-9]{2}):([0-9]{2}):([0-9]{2}):([0-9]{2}).*\n" .
            "(.+\n\n|.+\Z))+/smU",
            $contents,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            throw new \Exception("Invalid text file: ".basename($filename));
        }

        if (empty($this->framerate)) {
            $fps = 25;
            file_put_contents("php://stderr", "Warning: No framerate specified for import, assuming 25 FPS.\n");
        } else {
            $fps = $this->framerate;
        }

        $this->subtitles = array();

        foreach ($matches as $aMatch) {
            $timeFrom = 3600 * $aMatch[2] + 60 * $aMatch[3] + $aMatch[4] + $aMatch[5] / $fps;
            $timeTo = 3600 * $aMatch[6] + 60 * $aMatch[7] + $aMatch[8] + $aMatch[9] / $fps;
            $text = trim($aMatch[10], " \t\r\n");

            $this->subtitles[] = array(
                'from' => $timeFrom,
                'to' => $timeTo,
                'text' => self::textToHtml($text),
            );
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
        $txt = '';

        if (empty($this->framerate)) {
            $fps = 25;
            file_put_contents("php://stderr", "Warning: No framerate specified for export, assuming 25 FPS.\n");
        } else {
            $fps = $this->framerate;
        }

        $i = 1;
        foreach ($this->subtitles as $row) {
            $txt .= "$i) ";
            $txt .= self::formatTimecode($row['from'], $fps, ':').' '.self::formatTimecode($row['to'], $fps, ':')."\n";
            $txt .= self::htmlToText($row['text'])."\n\n";

            $i++;
        }

        if ($bom) {
            $txt = self::addUtf8Bom($txt);
        }

        return $txt;
    }

    /**
     * Return file extension for the current format
     * @return string
     */
    public function getFileExt()
    {
        return 'txt';
    }
}

?>
