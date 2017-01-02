<?php

namespace CrossKnowledge\SubConverterBundle;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use CrossKnowledge\SubConverterBundle\Providers\SubtitlesFactory;
use CrossKnowledge\SubConverterBundle\Services\ConverterService;
use Symfony\Component\Finder\Finder;

class SubConverterTest extends WebTestCase
{
    /**
     * @var ConverterService $converter
     */
    private $converter = null;

    /**
     * @var Finder $finder
     */
    private $finder = null;

    public function setUp()
    {
        $this->finder = new Finder();
    }

    /**
     * @dataProvider getDataToConvert
     */
    public function testConvert2AllFormats($format)
    {
        $resourcesPath = __DIR__ . '/resources/';
        $files = $this->finder->files()->in($resourcesPath);
        $originalFilename = $resourcesPath . 'lorem_subtitle.' . $format;

        $converter = new ConverterService();

        // For each file (same subtitles in each format), we have to convert again and compare expected vs result value
        foreach ($files as $file) {
            if (file_exists($originalFilename)) {
                $outputFilePath = sys_get_temp_dir().'/'.md5(uniqid('unit_tests_'));
                error_log($outputFilePath);
                $converter->convert($file, $outputFilePath, $format);

                // The conversion of the files produces \n\n in the end
                // We trim that to be able to check with original files correctly
                $outputFileContent = trim(file_get_contents($outputFilePath));
                $originalFileContent = trim(file_get_contents($originalFilename));

                $this->assertEquals(
                    $originalFileContent,
                    $outputFileContent,
                    'File conversion failed from '.$originalFilename.' to '.strtoupper($format)
                );
            }
            else {
                $this->assertTrue(false, $originalFilename . ' file is missing');
            }
        }
    }

    public function getDataToConvert()
    {
        return [
            ['srt'],
            ['webvtt'],
            ['ttaf1'],
            ['txt'],
        ];
    }
}
