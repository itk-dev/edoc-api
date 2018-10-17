<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Entity;

class ArchiveFormat extends Entity
{
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
