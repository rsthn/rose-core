<?php

class _DataTableRenderer
{
    private static $__classAttributes = null;
    private $dataTable;
    private $cellFilterer;


    public static function classAttributes ()
    {
        return _DataTableRenderer::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function __construct ($dataTable, $cellFilterer=null)
    {
        _DataTableRenderer::__instanceInit ($this);
        if ((($this->cellFilterer=$cellFilterer)==null))
        {
            $this->cellFilterer=$this;
        }
        $this->dataTable=$dataTable;
    }

    public function filterCell ($data)
    {
        return $data->cellValue;
    }

    public function render ($options)
    {
        $rowObj=null;;
        $tableObj=alpha (new _Html ('table'));
        if (($options->showHeader=='true'))
        {
            $tableObj->append($rowObj=alpha (new _Html ('tr')));
            $options->isHeader=true;
            foreach($this->dataTable->fields->__nativeArray as $field)
            {
                $options->cellValue=$field;
                $field=$this->cellFilterer->filterCell($options);
                if (($field!==null))
                {
                    $rowObj->append(alpha (new _Html ('th'))->text($field));
                }
            }
        }
        $options->isHeader=false;
        foreach($this->dataTable->rows->__nativeArray as $row)
        {
            $tableObj->append($rowObj=alpha (new _Html ('tr')));
            $index=0;
            $options->currentRow=$row;
            foreach($row->__nativeArray as $value)
            {
                $options->fieldName=$this->dataTable->fields->arrayGetElement ($index++);
                $options->cellValue=$value;
                $value=$this->cellFilterer->filterCell($options);
                if (($value!==null))
                {
                    $rowObj->append(alpha (new _Html ('td'))->text($value));
                }
            }
        }
        return $tableObj;
    }

    public function __get ($gsprn)
    {
        switch ($gsprn)
        {
        }

        if (method_exists (get_parent_class (), '__get')) return parent::__get ($gsprn);
        throw new _UndefinedProperty ($gsprn);
    }

    public function __set ($gsprn, $sprv)
    {
        switch ($gsprn)
        {
        }
        if (method_exists (get_parent_class (), '__set')) parent::__set ($gsprn, $sprv);
    }

    public function __toString ()
    {
        return $this->__typeCast('String');
    }

}