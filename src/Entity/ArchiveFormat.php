<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Entity;

class ArchiveFormat extends Entity
{
    public const PDF = 12;
    public const ZIP = 18;

    public $Code;
    public $FileExtension;
    public $Mimetype;

    protected function build(array $data)
    {
        $this->Code = $data['Code'];
        $this->FileExtension = $data['FileExtension'];
        $this->Mimetype = $data['Mimetype'];
    }
}
