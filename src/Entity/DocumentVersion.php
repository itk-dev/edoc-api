<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Entity;

class DocumentVersion extends Entity
{
    /** @var int */
    public $DocumentVersionNumber;

    /** @var string */
    public $DocumentVersionIdentifier;

    /** @var int */
    public $ArchiveFormatCode;

    public function getData($includeContents = false)
    {
        return array_filter(parent::getData(), static function ($key) use ($includeContents) {
            return $includeContents || 'DocumentContents' !== $key;
        }, \ARRAY_FILTER_USE_KEY);
    }

    public function getBinaryContents()
    {
        return base64_decode($this->DocumentContents, true);
    }

    protected function build(array $data)
    {
        $this->DocumentVersionNumber = (int) $data['DocumentVersionNumber'];
        $this->DocumentVersionIdentifier = $data['DocumentVersionIdentifier'] ?? null;
        $this->ArchiveFormatCode = $data['ArchiveFormatCode'] ?? null;
    }
}
