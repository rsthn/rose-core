<?php

class _SqlTable
{
    private static $__classAttributes = null;
    public $conn;
    public $table;


    public static function classAttributes ()
    {
        return _SqlTable::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->conn=null;
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function __construct ($table, $conn)
    {
        _SqlTable::__instanceInit ($this);
        $this->conn=$conn;
        $this->table=$table;
    }

    public function select ($condition, $extra=null)
    {
        if ((typeOf($condition)=='Map'))
        {
            $condition=$condition->format('{0}{{? {eq {f:hexs:1} {f:hexs:@<NULL>}} {#IS NULL} {#={filter:escape:1}}}}')->implode(' AND ');
        }
        return $this->conn->execAssoc('SELECT * FROM '.$this->table.(($condition?' WHERE '.$condition:null)).' '.$extra.' LIMIT 1 OFFSET 0');
    }

    public function readScalar ($field, $condition=null, $extra=null)
    {
        if ((typeOf($condition)=='Map'))
        {
            $condition=$condition->format('{0}{{? {eq {f:hexs:1} {f:hexs:@<NULL>}} {#IS NULL} {#={filter:escape:1}}}}')->implode(' AND ');
        }
        return $this->conn->execScalar('SELECT '.$field.' FROM '.$this->table.(($condition?' WHERE '.$condition:null)).' '.$extra.' LIMIT 1 OFFSET 0');
    }

    public function selectAll ($condition=null, $extra=null)
    {
        if ((typeOf($condition)=='Map'))
        {
            $condition=$condition->format('{0}{{? {eq {f:hexs:1} {f:hexs:@<NULL>}} {#IS NULL} {#={filter:escape:1}}}}')->implode(' AND ');
        }
        return $this->conn->execQueryA('SELECT * FROM '.$this->table.(($condition?' WHERE '.$condition:null)).' '.$extra,true);
    }

    public function selectAllDT ($condition=null, $extra=null)
    {
        if ((typeOf($condition)=='Map'))
        {
            $condition=$condition->format('{0}{{? {eq {f:hexs:1} {f:hexs:@<NULL>}} {#IS NULL} {#={filter:escape:1}}}}')->implode(' AND ');
        }
        return $this->conn->execQuery('SELECT * FROM '.$this->table.(($condition?' WHERE '.$condition:null)).' '.$extra);
    }

    public function insert ($data)
    {
        if ((typeOf($data)=='Map'))
        {
            return $this->conn->execQuery('INSERT INTO '.$this->table.$data->elements().' VALUES'.$data->values()->format('{filter:escape:0}'));
        }
        if ((typeOf($data)=='Array'))
        {
            return $this->conn->execQuery('INSERT INTO '.$this->table.' VALUES'.$data->values()->format('{filter:escape:0}'));
        }
        if ((typeOf($data)=='DataTable'))
        {
            $spec=$this->table.$data->fields->__toString();
            foreach($data->rows->slices()->__nativeArray as $block)
            {
                $query='INSERT INTO '.$spec.' VALUES';
                foreach($block->__nativeArray as $item)
                {
                    $query.=$item->format('{filter:escape:0}').',';
                }
                if (!$this->conn->execQuery(_Text::substring($query,0,-1)))
                {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public function update ($condition, $data)
    {
        if (!$data->length())
        {
            return true;
        }
        if ((typeOf($condition)=='Map'))
        {
            $condition=$condition->format('{0}{{? {eq {f:hexs:1} {f:hexs:@<NULL>}} {#IS NULL} {#={filter:escape:1}}}}')->implode(' AND ');
        }
        return $this->conn->execQuery('UPDATE '.$this->table.' SET '.$data->format('{0}{{? {eq {f:hexs:1} {f:hexs:@<NULL>}} {#=NULL} {#={filter:escape:1}}}}')->implode(',').' WHERE '.$condition);
    }

    public function delete ($condition)
    {
        if ((typeOf($condition)=='Map'))
        {
            $condition=$condition->format('{0}{{? {eq {f:hexs:1} {f:hexs:@<NULL>}} {#IS NULL} {#={filter:escape:1}}}}')->implode(' AND ');
        }
        return $this->conn->execQuery('DELETE FROM '.$this->table.(($condition?' WHERE '.$condition:null)));
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