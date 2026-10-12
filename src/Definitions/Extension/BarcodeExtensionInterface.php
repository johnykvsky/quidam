<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface BarcodeExtensionInterface extends ExtensionInterface
{
    /**
     * EAN-13 / GTIN-13 product code with GS1 country prefix, e.g. "5901234123457"
     */
    public function ean13(): string;

    /**
     * EAN-8 / GTIN-8 product code for small packages, e.g. "59012341"
     */
    public function ean8(): string;

    /**
     * UPC-A product code used in US and Canada, e.g. "036000291452"
     */
    public function upcA(): string;

    /**
     * ISBN-13 with valid prefix and registration group, e.g. "9788375066254"
     */
    public function isbn13(): string;

    /**
     * ISBN-10 with valid registration group, check digit can be "X", e.g. "837506625X"
     */
    public function isbn10(): string;

    /**
     * ISMN (printed music) in 13-digit form, e.g. "9790260000438"
     */
    public function ismn(): string;
}
