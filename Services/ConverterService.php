<?php

namespace CrossKnowledge\SubConverterBundle\Services;

use CrossKnowledge\SubConverterBundle\Providers\SubtitlesFactory;
use Exception;

class ConverterService
{
    public function convert($inputFilePath, $outputFilePath, $outputFormat, $includeBom = false)
    {
        try {
            if (!file_exists($inputFilePath)) {
                throw new Exception($inputFilePath . ' does not exist.');
            }

            if (!is_file($inputFilePath)) {
                throw new Exception($inputFilePath . ' is not a file.');
            }

            $st = SubtitlesFactory::getInstanceFromFile($inputFilePath);
            if (empty($st)) {
                throw new Exception('Unknown file type.');
            }

            // Convert to target format
            $stOut = $st->import($inputFilePath)->convert($outputFormat);

            if (!file_put_contents($outputFilePath, $stOut->export($includeBom))) {
                throw new Exception('Could not write file.');
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
