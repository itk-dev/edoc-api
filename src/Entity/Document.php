<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Entity;

class Document extends Entity
{
    /** @var string */
    public $DocumentIdentifier;

    /** @var string */
    public $DocumentVersionIdentifier;

    protected function build(array $data)
    {
        $this->DocumentIdentifier = $data['DocumentIdentifier'];
        $this->DocumentVersionIdentifier = $data['DocumentVersionIdentifier'] ?? null;
    }
}
