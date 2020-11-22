<?php

namespace WabLab\MemoryTable;

interface FieldType
{
    public function handle($value);
}
