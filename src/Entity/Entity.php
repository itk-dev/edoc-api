<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Entity;

abstract class Entity
{
    /**
     * The data.
     *
     * @var array
     */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->build($data);
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        throw new \Exception('Undefined property: '.$name);
    }

    protected function build(array $data)
    {
    }
}
