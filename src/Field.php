<?php

namespace WabLab\MemoryTable;


class Field
{
    private $name;
    /**
     * @var FieldType
     */
    private $fieldType;

    public function __construct($name, FieldType $fieldType)
    {
        $this->name = $name;
        $this->fieldType = $fieldType;
    }

    public function getName() {
        return $this->name;
    }

    public function getType(): FieldType {
        return $this->fieldType;
    }
}