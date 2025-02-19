<?php

namespace Experteam\ApiBaseBundle\Service\XmlReader;

use DOMNode;
use DOMNodeList;

interface XmlReaderInterface
{
    public function load(string $xml): void;

    public function query(string $xpath, ?DOMNode $node = null): DOMNodeList|false;

    public function getElement(string $tagName, ?DOMNode $node = null): ?DOMNode;

    public function getElements(string $tagName, ?DOMNode $node = null): DOMNodeList;
}
