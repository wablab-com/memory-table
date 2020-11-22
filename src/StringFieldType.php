<?php

namespace WabLab\MemoryTable;

class StringFieldType implements FieldType
{
    public function handle($value)
    {
        return (string)$value;
    }
}