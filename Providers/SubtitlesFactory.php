<?php

namespace CrossKnowledge\SubConverterBundle\Providers;

/**
 * Handle creation of subtitles instances
 */
class SubtitlesFactory
{
    /**
     * Return right converter for a given type
     *
     * @param $type
     * @return bool|SubRipSubtitles|Ttaf1Subtitles|TxtSubtitles|WebVttSubtitles
     */
	public static function getInstance($type)
	{
        switch ($type) {
            case 'SubRipSubtitles':
                return new SubRipSubtitles();
            case 'Ttaf1Subtitles':
                return new Ttaf1Subtitles();
            case 'TxtSubtitles':
                return new TxtSubtitles();
            case 'WebVttSubtitles':
                return new WebVttSubtitles();
            default:
                return false;
        }
	}

	/**
	 * Get a Subtitles instance from the given file.
	 * Returns null if the file could not be recognized.
	 *
	 * @param string $filename
	 * @return Subtitles
	 */
	public static function getInstanceFromFile($filename)
	{
		$implementations = self::getImplementations();

		foreach($implementations as $type)
		{
		    $instance = self::getInstance($type);
			if ($instance->checkFormat($filename))
				return $instance;
		}

		return null;
	}

	/**
	 * Return the list of Subtitles implementations
	 */
	public static function getImplementations()
	{
		return array(
			'srt'    => 'SubRipSubtitles',
			'webvtt' => 'WebVttSubtitles',
			'ttaf1'  => 'Ttaf1Subtitles',
			'txt'    => 'TxtSubtitles',
		);
	}
}

?>
