<?php

namespace WabLab\MemoryTable\Exception;


use Throwable;

class FieldNameAlreadyDeclared extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
