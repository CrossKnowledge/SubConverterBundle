<?php

namespace CrossKnowledge\SubConverterBundle\Providers;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Exception;

/**
 * TTAF1 subtitles class
 */
class Ttaf1Subtitles extends Subtitles
{
    /**
     * Path to XML template for export
     */
    public ?string $template = null;

    /**
     * Subtitle set title
     */
    public ?string $title = null;

    /**
     * Copyright info
     */
    public ?string $copyright = null;

    /**
     * @inheritDoc
     */
    public function checkFormat(string $filename): bool
    {
        $xml = self::loadXml($filename);
        if (empty($xml)) {
            return false;
        }

        if ($xml->getElementsByTagName('tt')->length != 1) {
            return false;
        }

        $xPath = new DOMXPath($xml);
        $xPath->registerNamespace('x', $xml->lookupNamespaceUri($xml->namespaceURI));

        $bodyNodes = $xPath->query('/x:tt/x:body');
        if ($bodyNodes->length != 1) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function import(string $filename): Subtitles
    {
        if (!$this->checkFormat($filename)) {
            throw new Exception("Invalid TTAF1 file: ".basename($filename));
        }

        $xml = self::loadXml($filename);

        $xPath = new DOMXPath($xml);
        $xPath->registerNamespace('x', $xml->lookupNamespaceUri($xml->namespaceURI));

        $ttNode = $xml->getElementsByTagName('tt')->item(0);

        $fps = (int)$ttNode->getAttribute('frameRate');
        if (!empty($fps)) {
            $this->framerate = $fps;
        }

        $this->language = $ttNode->getAttribute('xml:lang');

        $metadataNodes = $xPath->query('/x:tt/x:head/x:metadata');
        $metadataNode = null;
        if ($metadataNodes->length == 1) {
            $metadataNode = $metadataNodes->item(0);
        }

        $bodyNodes = $xPath->query('/x:tt/x:body');
        $bodyNode = $bodyNodes->item(0);

        // Get title
        $titleNodes = $metadataNode->getElementsByTagName('title');
        if ($titleNodes->length == 1) {
            $this->title = $titleNodes->item(0)->textContent;
        }

        // Get copyright
        $titleNodes = $metadataNode->getElementsByTagName('copyright');
        if ($titleNodes->length == 1) {
            $this->copyright = $titleNodes->item(0)->textContent;
        }

        if (empty($this->framerate)) {
            // Default Framerate
            $fps = 25;
        } else {
            $fps = $this->framerate;
        }

        // Get subtitles
        $this->subtitles = [];
        $pNodes = $bodyNode->getElementsByTagName('p');
        foreach ($pNodes as $aPNode) {
            if (preg_match('/^([0-9]+)f/i', $aPNode->getAttribute('begin'), $matches)) {
                $from = $matches[1] / $fps;
            } else {
                throw new Exception('Invalid begin value for slide ' . $aPNode->getAttribute('xml:id'));
            }

            if (preg_match('/^([0-9]+)f/i', $aPNode->getAttribute('end'), $matches)) {
                $to = $matches[1] / $fps;
            } else {
                throw new Exception('Invalid begin value for slide ' . $aPNode->getAttribute('xml:id'));
            }

            $text = '';
            foreach ($aPNode->childNodes as $aTextNode) {
                $text .= $xml->saveXml($aTextNode);
            }

            $this->subtitles[] = [
                'from' => $from,
                'to' => $to,
                'text' => trim(self::htmlToText($text), " \t\r\n"),
            ];
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function export(bool $bom = false): string
    {
        $templateDir = __DIR__ .'/../Resources/ttaf1_templates/';

        // Use template file
        if (!empty($this->template)) {
            // Predefined template ?
            $predefinedTemplateFile = $templateDir . strtolower($this->template) . '.xml';
            if (file_exists($predefinedTemplateFile) && is_file($predefinedTemplateFile)) {
                $templateFile = $predefinedTemplateFile;
            } else {
                $templateFile = $this->template;
            }

            // Check if file exists
            if (!file_exists($templateFile) || !is_file($templateFile)) {
                throw new Exception("The template file \"" . basename($templateFile) . "\" could not be found.");
            }

            // Check format
            if (!$this->checkFormat($templateFile)) {
                throw new Exception("The template file \"" . basename($templateFile) . "\" is not a valid TTAF1 file.");
            }
        } else {
            $templateFile = $templateDir . 'default.xml';
        }

        // Load template
        $xml = self::loadXml($templateFile);

        $xPath = new DOMXPath($xml);
        $xPath->registerNamespace('x', $xml->lookupNamespaceUri($xml->namespaceURI));

        // Get framerate
        $fps = 25; // Assuming 25 FPS by default
        $ttNode = $xml->getElementsByTagName('tt')->item(0);

        $templateFps = (int)$ttNode->getAttribute('ttp:frameRate');
        if (!empty($templateFps)) {
            $fps = $templateFps;
        } else {
            if (!empty($this->framerate)) {
                $fps = $this->framerate;
            } else {
                file_put_contents("php://stderr", "Warning: No framerate specified for export, assuming 25 FPS.\n");
            }
        }

        // Set head
        $headNodes = $ttNode->getElementsByTagName('head');
        if ($headNodes->length > 0) {
            $headNode = $headNodes->item(0);
        } else {
            $headNode = $ttNode->appendChild(new DOMElement('head'));
        }

        // Set metadata
        $metadataNodes = $headNode->getElementsByTagName('metadata');
        if ($metadataNodes->length > 0) {
            $metadataNode = $metadataNodes->item(0);
        } else {
            $metadataNode = $headNode->appendChild(new DOMElement('metadata'));
            $metadataNode->setAttributeNS(
                'http://www.w3.org/2000/xmlns/',
                'xmlns:ttm',
                'http://www.w3.org/2006/10/ttaf1#metadata'
            );
        }

        // Set framerate
        $ttNode->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:ttp',
            'http://www.w3.org/2006/10/ttaf1parameter'
        );
        $ttNode->setAttribute('ttp:frameRate', $fps);

        // Set language
        if (!empty($this->language)) {
            $ttNode->setAttribute('xml:lang', htmlspecialchars($this->language));
        }

        // Set title
        if (!empty($this->title)) {
            self::replaceNodeValue(
                $metadataNode,
                'ttm:title',
                htmlspecialchars($this->title),
                'http://www.w3.org/2006/10/ttaf1#metadata'
            );
        }

        // Set copyright
        if (!empty($this->copyright)) {
            self::replaceNodeValue(
                $metadataNode,
                'ttm:copyright',
                htmlspecialchars($this->copyright),
                'http://www.w3.org/2006/10/ttaf1#metadata'
            );
        }

        // Clear body
        $bodyNode = $xPath->query('/x:tt/x:body')->item(0);
        for ($i = $bodyNode->childNodes->length - 1; $i >= 0; $i--) {
            $bodyNode->removeChild($bodyNode->childNodes->item($i));
        }

        // Create subtitles container
        $containerNode = new DOMElement('div');
        $bodyNode->appendChild($containerNode);

        // Add subtitles
        $i = 1;
        foreach ($this->subtitles as $row) {
            $subtitleXml = $xml->createDocumentFragment();
            $subtitleXml->appendXML(
                '<p xml:id="subtitle' . $i . '" begin="' . round($row['from'] * $fps) . 'f" end="' . round(
                    $row['to'] * $fps
                ) . 'f">' . htmlspecialchars($row['text']) . '</p>'
            );
            $containerNode->appendChild($subtitleXml->firstChild);
            $i++;
        }

        // Return final XML
        if ($bom) {
            return self::addUtf8Bom($xml->saveXml());
        } else {
            return $xml->saveXml();
        }
    }

    /**
     * @inheritDoc
     */
    public function getFileExt(): string
    {
        return 'xml';
    }

    /**
     * Replace the value of the given node
     */
    protected static function replaceNodeValue(
        DOMNode $node,
        string $tagname,
        string $value,
        string $namespaceUri = null
    ) {
        if ($namespaceUri) {
            $newNode = $node->ownerDocument->createElementNS($namespaceUri, $tagname, $value);
            $nodes = $node->getElementsByTagNameNS($namespaceUri, preg_replace('/^.+\\:/', '', $tagname));
        } else {
            $newNode = $node->ownerDocument->createElement($tagname, $value);
            $nodes = $node->getElementsByTagName($tagname);
        }

        if ($nodes->length > 0) {
            $node->replaceChild($newNode, $nodes->item(0));
        } else {
            $node->appendChild($newNode);
        }
    }

    /**
     * Safely load an UTF-8 XML file, handling missing header.
     */
    protected static function loadXml($filename): DOMDocument
    {
        $strXml = trim(self::forceUtf8(file_get_contents($filename)));
        if (!preg_match('/^[^<]*<\?xml /i', $strXml)) // Take care of this damn UTF-8 BOM
        {
            $strXml = '<?xml version="1.0" encoding="utf-8"?>' . $strXml;
        }

        $xml = new DOMDocument('1.0', 'UTF-8');
        @$xml->loadXML($strXml);

        return $xml;
    }
}
