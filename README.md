[![Build Status](https://api.travis-ci.org/CrossKnowledge/SubConverterBundle.svg?branch=master)](https://travis-ci.org/CrossKnowledge/SubConverterBundle)
[![Code Climate](https://codeclimate.com/github/CrossKnowledge/SubConverterBundle.svg)](https://codeclimate.com/github/CrossKnowledge/SubConverterBundle)
[![Total Downloads](https://poser.pugx.org/crossknowledge/subconverter-bundle/downloads.svg)](https://packagist.org/packages/crossknowledge/subconverter-bundle)

CrossKnowledge SubConverter Bundle
===============================

The CrossKnowledge/SubConverterBundle aims to convert subtitles files from and to different formats.

Formats:

- SRT
- WebVTT
- TXT
- TTAF1

Installation
------------

Add the bundle to your project:
```bash
composer require crossknowledge/subconverter-bundle
```
Enable bundle in your kernel:
```php
class AppKernel	extends Kernel
{
  public function registerBundles()
  {
	  $bundles = array(
      ...
      new \CrossKnowledge\SubConverterBundle\CrossKnowledgeSubConverterBundle(),
		);
    ...
```

Now, to convert a subtitles file to a specific format, use can use the following service in your controller:
```php
  $this->get('crossknowledge.subconverterbundle.converter')->convert($inputFilePath, $outputFilePath, $outputFormat, $includeBom);
```

Example
-------

```php
$inputFilePath // "/tmp/my_subtitle.srt"
$outputFilePath // "/tmp/my_subtitle.webvtt"
$outputFormat // ['srt'|'webvtt'|'ttaf1'|'txt']
$includeBom // [true|false]
```

License
-------

This bundle is under the MIT license. See the complete license in the bundle:

    Resources/meta/LICENSE

About
-----

CrossKnowledgeSubConverterBundle is a [CrossKnowledge](https://crossknowledge.com) initiative.
See also the list of [contributors](https://github.com/CrossKnowledge/SubConverterBundle/contributors).
A couple of "distribution" (travis,readme.md, etc..) files are inspired from FriendsOfSymfony/FOSUserBundle's.

Contributions
-------------

Contributions are more than welcome.
We will try to integrate them. As long as there is no BC, anything can be suggested.


Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/CrossKnowledge/SubConverterBundle/issues).

When reporting a bug, it may be a good idea to reproduce it in a basic project
built using the [Symfony Standard Edition](https://github.com/symfony/symfony-standard)
to allow developers of the bundle to reproduce the issue by simply cloning it
and following some steps.
