<?php

namespace WabLab\MemoryTable;

use WabLab\MemoryTable\Exception\AtLeastOneKeyMustBeAdded;
use WabLab\MemoryTable\Exception\ComputeFieldCannotBeUsedAsKey;
use WabLab\MemoryTable\Exception\FieldNameAlreadyDeclared;

class Index
{

    /**
     * @var Field[]
     */
    protected array $fields = [];

    /**
     * @var array
     */
    public array $row = [];

    /**
     * @var string
     */
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addField(Field $field)
    {
        if($field->getType() instanceof ComputeFieldType) {
            throw new ComputeFieldCannotBeUsedAsKey();
        }

        if( !isset($this->fields[$field->getName()]) ) {
            $this->fields[$field->getName()] = $field;
        } else {
            throw new FieldNameAlreadyDeclared("Field name [{$field->getName()}]");
        }
    }

    public function linkToKey(string $keyHash, string $pk, array &$data)
    {
        $this->row[$keyHash][$pk] = &$data;
    }

    public function deleteLink(string $keyHash, string $pk) {
        if(isset($this->row[$keyHash][$pk])) {
            unset($this->row[$keyHash][$pk]);
            if(!count($this->row[$keyHash])) {
                unset($this->row[$keyHash]);
            }
        }
    }

    public function count(string $keyHash) {
        if(isset($this->row[$keyHash])) {
            return (count($this->row[$keyHash]));
        }
        return 0;
    }

    public function getName() {
        return $this->name;
    }

    /**
     * @return Field[]
     */
    public function getFields() : array {
        return $this->fields;
    }

    public function getRows(string $keyHash) {
        if(isset($this->row[$keyHash])) {
            return $this->row[$keyHash];
        }
        return [];
    }

    public function generateKey(array &$row) {
        if(count($this->fields)) {
            $key = [];
            foreach($this->fields as &$field) {
                $key[] = $field->getType()->handle($row[$field->getName()] ?? '');
            }
            return $this->hash(implode(',',$key));
        } else {
            throw new AtLeastOneKeyMustBeAdded("Index name [{$this->name}]");
        }
    }

    protected function hash(string $str) {
        if(strlen($str) >= 40) {
            return sha1($str);
        }
        return $str;
    }

}