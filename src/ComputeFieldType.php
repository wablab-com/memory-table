<?php

namespace WabLab\MemoryTable;

class ComputeFieldType implements FieldType
{
    /**
     * @var \Closure
     */
    private $handler;

    public function __construct(\Closure $handler)
    {
        $this->handler = $handler;
    }

    public function handle($args) {
        return ($this->handler)($args);
    }
}