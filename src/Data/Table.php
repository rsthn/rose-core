<?php
/*
**	Rose\Data\Connection
**
**	Copyright (c) 2018-2020, RedStar Technologies, All rights reserved.
**	https://rsthn.com/
**
**	THIS LIBRARY IS PROVIDED BY REDSTAR TECHNOLOGIES "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
**	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A 
**	PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL REDSTAR TECHNOLOGIES BE LIABLE FOR ANY
**	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
**	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
**	OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
**	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
**	USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace Rose\Data;

use Rose\Arry;
use Rose\Text;

/*
**	Provides an interface to read and manipulate a data table. Note that data tables have all the result set
**	rows in memory at once.
*/

class Table
{
	/*
	**	Names of the fields in the table.
	*/
	public $fields;

	/*
	**	Rows of the table, each row is an array containing the field values.
	*/
    public $rows;

	/*
	**	Constructs an empty table.
	*/
    public function __construct ()
    {
        $this->fields = new Arry();
        $this->rows = new Arry();
    }

	/*
	**	Returns a row matching the given index. If the index is out of bounds an error will be thrown.
	*/
    public function get ($index)
    {
        return $this->rows->get ($index);
    }

	/*
	**	Changes one row of the data table. If the provided index is out of bounds, an error will be thrown.
	*/
    public function set ($index, $value)
    {
        $this->rows->set ($index, $value);
    }

	/*
	**	Collects all the values of an specific column number and returns an array.
	*/
    public function selectColumn ($name)
    {
		$index = $this->fields->indexOf($name);
        if ($index === null) return null;

		$column = new Arry();

		foreach ($this->rows->__nativeArray as $row)
			$column->push($row->get($name));

        return $column;
    }

	/*
	**	Adds a new column to the table, each row will be updated with the given value which will be formatted first.
	*/
    public function addColumn ($name, $value)
    {
		$this->fields->push ($name);

		foreach($this->rows->__nativeArray as $row)
			$row->set($name, Text::format($value, $row));

        return $this;
    }

	/*
	**	Removes a column from the table. All rows will be updated.
	*/
    public function removeColumn ($name)
    {
        $index = $this->fields->indexOf($name);
        if ($index === null) return $this;

		foreach ($this->rows->__nativeArray as $row)
			$row->remove($name);

        $this->fields->remove($index);
        return $this;
    }

	/*
	**	Returns an array composed of string elements formed by formatting each row in the data table.
	*/
    public function filter ($formatString)
    {
		$result = new Arry();

        foreach ($this->rows->__nativeArray as $row)
            $result->push (Text::format ($formatString, $row));

        return $result;
    }

	/*
	**	Returns an array composed of string elements formed by imploding the columns of each row of the data table.
	*/
    public function implode ($delimiter=',')
    {
		$result = new Arry();

		foreach ($this->rows->__nativeArray as $row)
			$result->push ($row->values()->implode($delimiter));

        return $result;
	}
	
	/*
	**	Returns an HTML representation of the table.
	*/
	public function toHTML ()
	{
		$s = '';

		foreach ($this->fields->__nativeArray as $name)
		{
			$s .= "<th>$name</th>";
		}

		$s = "<tr>$s</tr>";

		foreach ($this->rows->__nativeArray as $row)
		{
			$i = '';

			foreach ($row->__nativeArray as $col)
				$i .= "<td>$col</td>";

			$s .= "<tr>$i</tr>";
		}

		return "<table style='font-family: monospace;' border='1'>$s</table>";
	}

	/*
	**	Executes the specified function for each of the rows in the table.
	*/
    public function forEach ($function)
    {
		$this->rows->forEach($function);
        return $this;
    }

	/*
	**	Returns the string representation of the table.
	*/
	public function __toString()
	{
		return $this->toHTML();
	}
};
