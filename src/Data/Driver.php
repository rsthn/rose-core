<?php
/*
**	Rose\Data\Driver
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

/**
**	Generic interface with minimum requirements for a database driver.
*/

abstract class Driver
{
	public abstract function open ($server, $user, $password, $name);
	public abstract function close ($conn);

	public abstract function getLastError ($conn);
	public abstract function getLastInsertId ($conn);
	public abstract function getAffectedRows ($conn);
	public abstract function isAlive ($conn);
	public abstract function query ($query, $conn);

	public abstract function getNumRows ($result, $conn);
	public abstract function getNumFields ($result, $conn);
	public abstract function getFieldName ($result, $i, $conn);

	public abstract function fetchAssoc ($result, $conn);
	public abstract function fetchRow ($result, $conn);
	public abstract function freeResult ($result, $conn);

	public abstract function escapeName ($value);
	public abstract function escapeValue ($value);
};
