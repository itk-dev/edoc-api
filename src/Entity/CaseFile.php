<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Entity;

class CaseFile extends Entity
{
    /** @var string */
    public $CaseFileIdentifier;

    protected function build(array $data)
    {
        $this->CaseFileIdentifier = $data['CaseFileIdentifier'];
    }
}
