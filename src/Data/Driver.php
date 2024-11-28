<?php

namespace Rose\Data;
use Rose\Text;
use Rose\Errors\Error;

/**
 * Generic interface with minimum requirements for a database driver.
 */
abstract class Driver
{
    public $tracing = false;

    public function log_query ($query)
    {
        if ($this->tracing)
            \Rose\trace($query);
        return $query;
    }

	public abstract function open ($server, $port, $user, $password, $name);
	public abstract function close ($conn);

	public abstract function getLastError ($conn);
	public abstract function getLastInsertId ($conn);
	public abstract function getAffectedRows ($conn);
	public abstract function isAlive ($conn);
	public abstract function query ($query, $conn, $params);
	public abstract function reader ($query, $conn, $params);

	public abstract function getNumRows ($result, $conn);
	public abstract function getNumFields ($result, $conn);
	public abstract function getFieldName ($result, $i, $conn);

	public abstract function fetchAssoc ($result, $conn);
	public abstract function fetchRow ($result, $conn);
	public abstract function freeResult ($result, $conn);

	public abstract function escapeName ($value);
	public abstract function escapeValue ($value);

    public abstract function prepare_param (&$value, &$query_part, &$index, &$extra);

    public function prepare_query ($query, $params, $extra=null)
    {
        $next_arg_num = 1;
        $final_params = [];
        $final_query = '';

        $n = Text::length($query);
        $offs = 0;

        foreach ($params->__nativeArray as $param)
        {
            $i = Text::indexOf($query, '?', $offs);
            if ($i === false) break;

            $type = \Rose\typeOf($param);

            // ?() or ?[] or ?..
            if ($i < $n-2 && (
                ($query[$i+1] == '(' && $query[$i+2] == ')') 
                || ($query[$i+1] == '[' && $query[$i+2] == ']')
                || ($query[$i+1] == '.' && $query[$i+2] == '.')
            ))
            {
                $final_query .= Text::slice($query, $offs, $i);
                $final_query .= $query[$i+1] != '.' ? $query[$i+1] : '';

                if ($type !== 'Rose\Arry')
                    throw new Error('array expected for ?'.$query[$i+1].$query[$i+2]);

                if ($param->length() > 0) {
                    $sub_type = \Rose\typeOf($param->get(0));
                    if ($sub_type !== 'primitive' && $sub_type !== 'Rose\Arry')
                        throw new Error('primitive or sub-array expected for values for ?'.$query[$i+1].$query[$i+2]);
                    $is_nested = $sub_type === 'Rose\Arry';
                }

                $comma = false;
                if ($is_nested)
                {
                    foreach ($param->__nativeArray as $val) {
                        $final_query .= $comma;
                        $comma = ',';

                        $sub_comma = false;
                        $final_query .= '(';

                        foreach ($val->__nativeArray as $sub_val)
                        {
                            $mode = $this->prepare_param($sub_val, $query_part, $next_arg_num, $extra);

                            if ($mode & 1) {
                                $final_query .= $sub_comma;
                                $sub_comma = ',';
                                $final_query .= $query_part;
                            }

                            if ($mode & 2)
                                $final_params[] = $sub_val;
                        }

                        $final_query .= ')';
                    }
                }
                else
                {
                    foreach ($param->__nativeArray as $val)
                    {
                        $mode = $this->prepare_param($val, $query_part, $next_arg_num, $extra);

                        if ($mode & 1) {
                            $final_query .= $comma;
                            $comma = ',';
                            $final_query .= $query_part;
                        }

                        if ($mode & 2)
                            $final_params[] = $val;
                    }
                }

                $final_query .= $query[$i+2] != '.' ? $query[$i+2] : '';
                $offs = $i+3;
                continue;
            }

            $final_query .= Text::slice($query, $offs, $i);

            $mode = $this->prepare_param($param, $query_part, $next_arg_num, $extra);

            if ($mode & 1)
                $final_query .= $query_part;

            if ($mode & 2)
                $final_params[] = $param;

            $offs = $i+1;
        }

        $final_query .= Text::slice($query, $offs);
        return [$final_query, $final_params, $extra];
    }
};
