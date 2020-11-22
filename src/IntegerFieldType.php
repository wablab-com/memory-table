<?php

namespace WabLab\MemoryTable;

class IntegerFieldType implements FieldType
{
    public function handle($value)
    {
        return (int)$value;
    }
}