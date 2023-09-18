<?php

namespace Rose\Data;

/**
 * Generic interface with minimum requirements for a database driver.
 */
abstract class Driver
{
	public abstract function open ($server, $user, $password, $name);
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
};
