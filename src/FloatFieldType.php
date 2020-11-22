<?php

namespace WabLab\MemoryTable;

class FloatFieldType implements FieldType
{
    public function handle($value)
    {
        return (float)$value;
    }
}