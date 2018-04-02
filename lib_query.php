<?php
require_once(__DIR__.'/lib_database.php');

class Query {
	private $cursor = null;
	private $database = null;
	private $sql = null;
	private $param = null;
	private $dataset = null;
	private $currentError = null;
	private $lastError = null;

	function __construct($dbase,$qry,$param = null) {
		$this->database = $dbase;
		if (is_array($qry))
			$this->sql = implode(' ',$qry);
		else
			$this->sql = $qry;
		$this->param = $param;
		$this->cursor = -1;
	}
	function recordCount() {
		try {
			if (!is_null($this->dataset))
				return count($this->dataset);
			else
				return null;
		}
		catch(PDOException $e) {
			$this->currentError = $e->getMessage();
			$this->lastError = $this->currentError;
		}
	}
	function open() {
		try {
			if (is_null($this->dataset)) {
				$this->dataset = $this->database->runQuery($this->sql,$this->param,false);
				$this->cursor = 0;
			} else
				throw new PDOException('DataSet already open');
		}
		catch(PDOException $e) {
			$this->currentError = $e->getMessage();
			$this->lastError = $this->currentError;
		}
	}
	function close() {
		try {
			if (!is_null($this->dataset)) {
				unset($this->dataset);
				$this->dataset = null;
			} else
				throw new PDOException('DataSet already closed');
		}
		catch(PDOException $e) {
			$this->currentError = $e->getMessage();
			$this->lastError = $this->currentError;
		}
	}
	function Active() {
		return !is_null($this->dataset);
	}
	function first() {
		try {
			if (!is_null($this->dataset)) {
				$this->cursor = 0;
			} else
				throw new PDOException('DataSet not open');

		}
		catch(PDOException $e) {
			$this->currentError = $e->getMessage();
			$this->lastError = $this->currentError;
		}
	}
	function last() {
		try {
			if (!is_null($this->dataset)) {
				$this->cursor = $this->recordCount -1;
			} else
				throw new PDOException('DataSet not open');

		}
		catch(PDOException $e) {
			$this->currentError = $e->getMessage();
			$this->lastError = $this->currentError;
		}
	}
	function Next() {
		if ($this->cursor<$this->recordCount())
			$this->cursor++;
		else
			throw new PDOException('No next record');
	}
	function Prev() {
		if ($this->cursor>0)
			$this->cursor--;
		else
			throw new PDOException('No previous record');
	}
	function BOF() {
		return $this->cursor<0;
	}
	function EOF() {
		return $this->cursor>=$this->recordCount();
	}
	function fieldValue($fldname) {
		if (is_array($this->dataset))
			return $this->dataset[$this->cursor][$fldname];
		else
			return $this->dataset;
	}
	function nrOfFields() {
		return count($this->dataset[0]);
	}
	function fieldNames() {
		return array_keys($this->dataset[0]);
	}
	function fieldName($fieldnr) {
		return $this->fieldNames()[$fieldnr];
	}
	function getRow(&$arow) {
		if($this->cursor<$this->recordCount()) {
			$arow = $this->dataset[$this->cursor];
			$this->cursor++;
			return true;
		} else {
			$arow = null;
			return false;
		}
	}
	function get_dataset() {
		return $this->dataset;
	}
	function get_sql() {
		return $this->sql;
	}
	function set_sql($newSQL) {
		if ($this->Active())
			$this->close();
		$this->sql = $newSQL;
	}
	function get_param() {
		return $this->param;
	}
	function set_param($newParam) {
		if ($this->Active())
			$this->close();
		$this->param = $newParam;
	}
	function get_database() {
		return $this->database;
	}
}
?>
