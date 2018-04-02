<?php
require_once(__DIR__.'/lib_database.php');
require_once(__DIR__.'/lib_query.php');

class Paginator {
	
	private $qry = null;
	private $maxRec = null;
	private $curPage = null;
	
	private $recOnPage = null;
	private $countField = null;
	function __construct($qry, $recOnPage) {
		$this->qry = $qry;
		$this->recOnPage = $recOnPage;
		$this->CurPage = null;
	}
	function get_maxRec() {
		if (is_null($this->maxRec)) 
			$this->maxRec = $this->queryRecordCount();
		return $this->maxRec; 
	}
	function limitString() {
		$start = ($this->curPage-1) * $this->recOnPage;
		return " limit $tart, $this-recOnPage";
	}
	function maxPage() {
		$this->maxPage = intval(($this->get_maxRec() + $this->recOnPage) / $this->recOnPage);
	}
	function nextPage() {
		if ($this->curPage<$this->maxPage())
			$this->curPage++;
	}
	function prevPage() {
		if ($this->curPage>1)
			$this->curPage--;
	}
	function queryRecordCount($fldname = '*') {
		if (is_null($this->curPage))
			$this->curPage = 1;
		$this->countField = $fldname;
		$sql = strtolower($this->qry->sql);
		$frompos = strpos($sql,'from ');
		if ($frompos === false) {
			throw new Exception('Paginator error: Keyword from not found in query');
		}
		$statement = "select count($fldname) ".substr($this->qry->sql,$frompos);
		$this->maxRec = $this->qry->get_database()->runQuery($statement,$this->qry->get_param());
	}
	function set_curpage($pageNum) {
		$this->curPage = $pageNum;
	}	
}
?>