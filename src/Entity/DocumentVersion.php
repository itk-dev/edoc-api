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

    /**
     * Document content (base64 encoded).
     *
     * @var string
     */
    public $DocumentContents;

    public function getBinaryContents()
    {
        return base64_decode($this->DocumentContents, true);
    }

    protected function build(array $data)
    {
        $this->DocumentVersionNumber = (int) $data['DocumentVersionNumber'];
        $this->DocumentVersionIdentifier = $data['DocumentVersionIdentifier'] ?? null;
        $this->ArchiveFormatCode = $data['ArchiveFormatCode'] ?? null;
        $this->DocumentContents = $data['DocumentContents'];
    }
}
