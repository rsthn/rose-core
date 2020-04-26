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

use Rose\Map;
use Rose\Arry;

/*
**	Provides an interface to read data progressively from a database result set.
*/

class Reader
{
	/*
	**	Database driver.
	*/
	private $driver;


	/*
	**	Connection resource.
	*/
	private $conn;

	/*
	**	Result set resource.
	*/
	private $rs;

	/*
	**	Index of the last row retrieved.
	*/
    private $index;

	/*
	**	Constructs the Reader.
	*/
    public function __construct ($driver, $conn, $rs)
    {
        $this->driver = $driver;
        $this->conn = $conn;
		$this->rs = $rs;

		$this->index = -1;
    }

	/*
	**	Returns the number of items available.
	*/
    public function count()
    {
        return $this->driver->getNumRows ($this->rs, $this->conn);
	}

	/*
	**	Returns the number of items remaining.
	*/
    public function remaining()
    {
        return $this->driver->getNumRows ($this->rs, $this->conn) - ($this->index+1);
    }

	/*
	**	Returns the index of the last row retrieved.
	*/
    public function getIndex()
    {
        return $this->index;
	}

	/*
	**	Returns the next item as an assoc array or null if no more items are left on the reader.
	*/
    public function getAssoc ()
    {
		$this->index++;
        return Map::fromNativeArray($this->driver->fetchAssoc($this->rs, $this->conn), false);
    }

	/*
	**	Returns the next item as an array or null if no more items are left on the reader.
	*/
    public function getArray ()
    {
		$this->index++;
        return Arry::fromNativeArray($this->driver->fetchRow($this->rs, $this->conn), false);
    }

	/*
	**	Closes the data reader.
	*/
    public function close ()
    {
		$this->driver->freeResult ($this->rs, $this->conn);
		$this->rs = null;
    }
};
