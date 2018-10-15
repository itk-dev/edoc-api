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
    public $code;
    public $fileExtension;
    public $mimetype;

    protected function build(array $data)
    {
        $this->code = $data['Code'];
        $this->fileExtension = $data['FileExtension'];
        $this->mimetype = $data['Mimetype'];
    }
}
