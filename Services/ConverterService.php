<?php


namespace CrossKnowledge\SubConverterBundle\Services;


use CrossKnowledge\SubConverterBundle\Providers\SubtitlesFactory;

class ConverterService
{
    public function convert($input_file_path, $output_file_path, $output_format, $includeBom = false)
    {
        try {
            if (!file_exists($input_file_path)) {
                throw new \Exception($input_file_path . ' does not exist.');
            }

            if (!is_file($input_file_path)) {
                throw new \Exception($input_file_path . ' is not a file.');
            }

            $st = SubtitlesFactory::getInstanceFromFile($input_file_path);
            if (empty($st)) {
                throw new \Exception('Unknown file type.');
            }

            // Convert to target format
            $stOut = $st->import($input_file_path)->convert($output_format);

            if (!file_put_contents($output_file_path, $stOut->export($includeBom))) {
                throw new \Exception('Could not write file.');
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}