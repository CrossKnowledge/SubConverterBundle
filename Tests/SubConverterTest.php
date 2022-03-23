<?php

namespace CrossKnowledge\SubConverterBundle;

use CrossKnowledge\SubConverterBundle\Services\ConverterService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class SubConverterTest extends TestCase
{
    /** @var string */
    const RESOURCES_PATH = __DIR__ . '/resources/';

    /** @var ConverterService */
    private static $converter;

    /** @var Finder */
    private static $files;

    /**
     * Set up the required data for the TestCase.
     */
    public static function setUpBeforeClass(): void
    {
        self::$files = (new Finder())->files()->in(self::RESOURCES_PATH);
        self::$converter = new ConverterService();
    }

    /**
     * Data provider to test the converter.
     *
     * @return string[][]
     */
    public function formatDataProvider(): array
    {
        return [
            ['srt'],
            ['webvtt'],
            ['ttaf1'],
            ['txt'],
        ];
    }

    /**
     * Test to convert all file formats.
     *
     * @dataProvider formatDataProvider
     */
    public function testConvertToAllFormats($format)
    {
        $originalFilename = self::RESOURCES_PATH . 'lorem_subtitle.' . $format;

        // For each file (same subtitles in each format), we have to convert again and compare expected vs result value
        foreach (self::$files as $file) {
            if (file_exists($originalFilename)) {
                $outputFilePath = sys_get_temp_dir().'/'.md5(uniqid('unit_tests_'));
                self::$converter->convert($file, $outputFilePath, $format);

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
}
