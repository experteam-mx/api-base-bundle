<?php

namespace Experteam\ApiBaseBundle\Service\XmlReader;

use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMXPath;

class XmlReader implements XmlReaderInterface
{
    public function __construct(
        private readonly DOMDocument $document = new DOMDocument(),
        private ?DOMXPath            $xpath = null
    )
    {
    }

    public function load(string $xml): void
    {
        if ($this->document->loadXML($xml)) {
            $this->xpath = new DOMXPath($this->document);
        }
    }

    public function query(string $xpath, ?DOMNode $node = null): DOMNodeList|false
    {
        if (is_null($this->xpath)) {
            return false;
        }

        if (is_null($node)) {
            $node = $this->document;
        }

        return $this->xpath->query($xpath, $node);
    }

    public function getElement(string $tagName, ?DOMNode $node = null): ?DOMNode
    {
        return $this->getElements($tagName, $node)->item(0);
    }

    public function getElements(string $tagName, ?DOMNode $node = null): DOMNodeList
    {
        if (is_null($node)) {
            $node = $this->document;
        }

        return $node->getElementsByTagName($tagName);
    }
}
