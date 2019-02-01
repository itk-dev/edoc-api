<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Util;

class EdocException extends \Exception
{
    /** @var object */
    private $data;

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
