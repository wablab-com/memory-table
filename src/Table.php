<?php

namespace WabLab\MemoryTable;


use WabLab\MemoryTable\Exception\AtLeastOneKeyMustBeAdded;
use WabLab\MemoryTable\Exception\ComputeFieldCannotBeUsedAsKey;
use WabLab\MemoryTable\Exception\FieldNameAlreadyDeclared;
use WabLab\MemoryTable\Exception\IndexAlreadyAssigned;
use WabLab\MemoryTable\Exception\IndexFieldDoesNotMatchWithTableFields;
use WabLab\MemoryTable\Exception\NoIndexAssigned;
use WabLab\MemoryTable\Exception\NoMatchedRecordsCouldBeFound;
use WabLab\MemoryTable\Exception\PrimaryKeyAlreadyExists;

class Table
{
    /**
     * @var Field[]
     */
    protected array $primaryKeyFields = [];

    /**
     * @var Field[]
     */
    protected array $fields = [];

    /**
     * @var array
     */
    protected array $fieldNames = [];

    /**
     * @var array
     */
    public array $row = [];

    /**
     * @var Index[]
     */
    public array $index = [];

    /**
     * @var string
     */
    private string $name;

    /**
     * @var int
     */
    protected int $rowsCount = 0;

    /**
     * Table constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param Field $field
     * @throws FieldNameAlreadyDeclared
     */
    public function addField(Field $field) {
        if( !isset($this->fields[$field->getName()]) ) {
            $this->fields[$field->getName()] = $field;
            $this->fieldNames[] = $field->getName();
        } else {
            throw new FieldNameAlreadyDeclared("Field name [{$field->getName()}]");
        }
    }

    /**
     * @param Index $index
     * @throws AtLeastOneKeyMustBeAdded
     * @throws IndexAlreadyAssigned
     * @throws IndexFieldDoesNotMatchWithTableFields
     */
    public function addIndex(Index $index) {
        if(!isset($this->index[$index->getName()])) {
            $indexFields = $index->getFields();
            if(count($indexFields)) {
                foreach($indexFields as $indexField) {
                    if(!isset($this->fields[$indexField->getName()])) {
                        throw new IndexFieldDoesNotMatchWithTableFields("Index [{$index->getName()}] - Field [{$indexField->getName()}]");
                    }
                }
                $this->index[$index->getName()] = $index;
            } else {
                throw new AtLeastOneKeyMustBeAdded("Index name [{$index->getName()}]");
            }
        } else {
            throw new IndexAlreadyAssigned("Index name [{$index->getName()}]");
        }
    }

    /**
     * @param Field $field
     * @throws ComputeFieldCannotBeUsedAsKey
     * @throws FieldNameAlreadyDeclared
     */
    public function addToPrimaryKey(Field $field) {
        if($field->getType() instanceof ComputeFieldType) {
            throw new ComputeFieldCannotBeUsedAsKey();
        }

        if( !isset($this->fields[$field->getName()]) ) {
            $this->addField($field);
        }
        $this->primaryKeyFields[$field->getName()] = $field;
    }

    /**
     * @param $pk
     * @param array $rawData
     * @throws AtLeastOneKeyMustBeAdded
     */
    protected function linkWithIndexKeys($pk, array $rawData) {
        foreach($this->index as $index) {
            $indexKey = $index->generateKey($rawData);
            $index->linkToKey($indexKey, $pk, $this->row[$pk]);
        }
    }

    protected function unlinkFromIndexKeys($pk, array $rawData) {
        foreach($this->index as $index) {
            $indexKey = $index->generateKey($rawData);
            $index->deleteLink($indexKey, $pk);
        }
    }

    /**
     * @param array $rawData
     * @throws AtLeastOneKeyMustBeAdded
     * @throws PrimaryKeyAlreadyExists
     */
    public function insertRow(array $rawData, bool $ignoreIfExists = false, bool $ignoreCompute = false)
    {
        $pk = $this->generatePrimaryKey($rawData);
        $data = $this->prepareRow($rawData, $ignoreCompute);

        if(!isset($this->row[$pk])) {
            $this->row[$pk] = $data;
            $this->rowsCount = $this->rowsCount + 1;
            $this->linkWithIndexKeys($pk, $rawData);
        } else {
            if(!$ignoreIfExists) {
                throw new PrimaryKeyAlreadyExists(json_encode($data));
            }
        }
    }

    /**
     * @param array $keys
     * @param array $rawData
     * @throws AtLeastOneKeyMustBeAdded
     * @throws NoMatchedRecordsCouldBeFound
     */
    public function updateRow(array $keys,array $rawData, bool $ignoreIfNotExists = true, bool $ignoreCompute = false) {
        $pk = $this->generatePrimaryKey($keys);

        if(!isset($this->row[$pk])) {
            if(!$ignoreIfNotExists) {
                throw new NoMatchedRecordsCouldBeFound('Key: '.json_encode($keys));
            }
        } else {
            $rowBeforeChange = $this->row[$pk];
            foreach($this->fieldNames as $inx => $fieldName) {
                if(isset($rawData[$fieldName]) ) {
                    $fieldTypeObj = $this->fields[$fieldName]->getType();
                    if($ignoreCompute && $fieldTypeObj instanceof ComputeFieldType) {
                        $this->row[$pk][$inx] = $rawData[$fieldName];
                    } else {
                        $this->row[$pk][$inx] = $fieldTypeObj->handle($rawData[$fieldName]);
                    }
                }
            }

            $newPk = $this->generatePrimaryKey($this->combineDataWithFieldNames($this->row[$pk]));
            if($newPk != $pk) {
                $this->row[$newPk] = &$this->row[$pk];
                unset($this->row[$pk]);
            }

            $this->unlinkFromIndexKeys($pk, $this->combineDataWithFieldNames($rowBeforeChange));
            $this->linkWithIndexKeys($newPk, $this->combineDataWithFieldNames($this->row[$newPk]));
        }
    }

    /**
     * @param array $rawData
     * @throws AtLeastOneKeyMustBeAdded
     */
    public function replaceRow(array $rawData, bool $ignoreCompute = false) {
        $pk = $this->generatePrimaryKey($rawData);
        $data = $this->prepareRow($rawData, $ignoreCompute);

        if(!isset($this->row[$pk])) {
            $this->rowsCount = $this->rowsCount + 1;
        } else {
            $this->unlinkFromIndexKeys($pk, $this->combineDataWithFieldNames($this->row[$pk]));
        }
        $this->row[$pk] = $data;
        $this->linkWithIndexKeys($pk, $rawData);
    }


    public function deleteRow(array $pk, bool $ignoreIfNotExists = true) {
        $pkHash = $this->generatePrimaryKey($pk);

        if(isset($this->row[$pkHash])) {
            $this->unlinkFromIndexKeys($pkHash, $this->combineDataWithFieldNames($this->row[$pkHash]));
            unset($this->row[$pkHash]);
            $this->rowsCount--;
        } else {
            if(!$ignoreIfNotExists) {
                throw new NoMatchedRecordsCouldBeFound("Table name [{$this->getName()}]");
            }
        }
    }

    public function getByIndex(string $indexName, array $keyParams) {
        $indexObj = $this->index[$indexName] ?? null;
        if($indexObj) {
            $indexKeyHash = $indexObj->generateKey($keyParams);
            $rows = $indexObj->getRows($indexKeyHash);
            if($rows) {
                $toReturn = [];
                foreach($rows as $row) {
                    $toReturn[] = $this->combineDataWithFieldNames($row);
                }
                return $toReturn;
            }
        } else {
            throw new NoIndexAssigned("Table [{$this->getName()}] - Index [{$indexName}]");
        }
        return [];
    }

    public function countByIndex(string $indexName, array $keyParams) {
        $indexObj = $this->index[$indexName] ?? null;
        if($indexObj) {
            $indexKeyHash = $indexObj->generateKey($keyParams);
            return $indexObj->count($indexKeyHash);
        } else {
            throw new NoIndexAssigned("Table [{$this->getName()}] - Index [{$indexName}]");
        }
    }

    /**
     * Get row by primary key
     *
     * @param array $condition
     */
    public function find(array $pk) {
        $pk = $this->generatePrimaryKey($pk);
        $row = $this->row[$pk] ?? null;
        if($row) {
            return $this->combineDataWithFieldNames( $row );
        }
        return null;
    }

    /**
     * @return int
     */
    public function count() {
        return $this->rowsCount;
    }

    /**
     * @return \Generator
     */
    public function yieldAll() {
        foreach($this->row as $row) {
            yield $this->combineDataWithFieldNames( $row );
        }
    }

    /**
     * @return array
     */
    public function all() {
        $toReturn = [];
        foreach($this->row as $row) {
            $toReturn[] = $this->combineDataWithFieldNames( $row );
        }
        return $toReturn;
    }

    /**
     * @return string
     */
    public function toJson():string {
        return json_encode($this->all());
    }

    /**
     * @param string $json
     */
    public function fromJson(string $json, $ignoreIfExists = false) {
        $jsonRows = json_decode($json, true);
        foreach($jsonRows as $row) {
            $this->insertRow($row, $ignoreIfExists, true);
        }
    }

    /**
     * @param resource $stream
     */
    public function toJsonStream($stream) {
        fwrite($stream, '[');
        $counter = 0;
        foreach ($this->row as $row) {
            $counter++;
            fwrite($stream, json_encode($this->combineDataWithFieldNames($row)) );
            if($counter < $this->rowsCount) {
                fwrite($stream, ',' );
            }
        }
        fwrite($stream, ']');
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return Field[]
     */
    public function getFields() : array {
        return $this->fields;
    }

    public function getField(string $name): ?Field {
        if(isset($this->fields[$name])) {
            return $this->fields[$name];
        }
        return null;
    }

    /**
     * @return Field[]
     */
    public function getPrimaryKeys() : array {
        return $this->primaryKeyFields;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function combineDataWithFieldNames(array $data) {
        $toReturn = [];
        $index = 0;
        foreach ($this->fieldNames as $fieldName) {
            $toReturn[$fieldName] = $data[$index] ?? null;
            $index++;
        }
        return $toReturn;
    }

    /**
     * @param array $rawData
     * @return array
     */
    protected function &prepareRow(array &$rawData, $ignoreCompute = false) {
        $toReturn = [];
        // prepare
        foreach($this->fields as $fieldName => $field) {
            if($ignoreCompute && $field->getType() instanceof ComputeFieldType) {
                $toReturn[] = $rawData[$fieldName] ?? null;
            } elseif($field->getType() instanceof ComputeFieldType && empty($rawData[$fieldName]) ) {
                $toReturn[] = $field->getType()->handle($rawData);
            } else {
                $toReturn[] = $field->getType()->handle($rawData[$fieldName] ?? null);
            }
        }
        return $toReturn;
    }

    /**
     * @param array $row
     * @return string
     * @throws AtLeastOneKeyMustBeAdded
     */
    protected function generatePrimaryKey(array $row) {
        if(count($this->primaryKeyFields)) {
            $pk = [];
            foreach($this->primaryKeyFields as &$field) {
                $pk[] = $field->getType()->handle($row[$field->getName()] ?? '');
            }
            return $this->hash(implode(',',$pk));
        } else {
            throw new AtLeastOneKeyMustBeAdded("Table name [{$this->name}]");
        }
    }

    /**
     * @param string $str
     * @return string
     */
    protected function hash(string $str) {
        if(strlen($str) >= 40) {
            return sha1($str);
        }
        return $str;
    }

}