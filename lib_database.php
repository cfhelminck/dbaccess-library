<?php
define("DB_SERVER","server");
define("DB_DATABASE","database");
define("DB_USER","user");
define("DB_PASSWORD","password");
define("D_MYSQL","mysql");

class Database {
	private $p_currentError = null;
	private $p_lastError = null;
	private $p_rowsAffected = null;
	
	private $connParam = null;
	private $driver = null;
	private $conn = null;

	function __construct($server,$dbname,$uname,$pwd) {
		$this->connParam = array();
		$this->connParam[DB_SERVER] = $server;
		$this->connParam[DB_DATABASE] = $dbname;
		$this->connParam[DB_USER] = $uname;
		$this->connParam[DB_PASSWORD] = $pwd;
	}
	function connected() {
		return !is_null($this->conn);
	}
	function connectMySQL() {
		try {
			$fresult = true;
			$this->p_currentError = '';
			$this->driver = D_MYSQL;
			if (is_null($this->conn)) {
				$this->p_currentError = '';
				$connStr = "mysql:host=".$this->connParam[DB_SERVER].
							  ";dbname=".$this->connParam[DB_DATABASE];
				$this->conn = new PDO($connStr,$this->connParam[DB_USER],$this->connParam[DB_PASSWORD]);
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} else {
				throw new RuntimeException('connectMySQL already connected');
			}
		}
		catch(PDOException $e) {
			$this->p_currentError = $e->getMessage();
			$this->p_lastError = $this->p_currentError;
			$fresult = false;
		}
	    return $fresult;
	}
	function disconnect() {
		$this->conn = null;
		return true;
	}
	function execute($sql,$param = null) {
		try {
			$fresult = true;
			$this->p_currentError = '';
			if (is_array($sql)) {
				$sql = implode(' ',$sql);
			}
			$stmnt = $this->conn->prepare($sql);
			if ($stmnt=== false) {
				throw new Exception($this->conn->errorInfo());				
			}
			if (!$stmnt->execute($param))
				throw new Exception($this->conn->errorInfo());
		}
		catch(Exception $e) {
			$this->p_currentError = $e->getMessage();
			$this->p_lastError = $this->p_currentError;
			$fresult = false;
		}
		return $fresult;
	}
	function runQuery($sql,$params = null,$detectSingleVal =true) {
		try {
			$this->p_currentError = '';
			if (is_array($sql)) {
				$sql = implode(' ',$sql);
			}
			$stmnt = $this->conn->prepare($sql,array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$tst = $this->conn->errorInfo();
			if($tst[0] != 0)
				throw new Exception($tst[2]);

			$stmnt->execute($params);
			$tst = $this->conn->errorInfo();
			if($tst[0] != 0)
				throw new Exception($tst[2]);
			
			$result = $stmnt->fetchAll(PDO::FETCH_ASSOC);
			
			$stmnt->closeCursor();
			
			if ($detectSingleVal) { 
				if ((count($result)==1) && (count($result[0])==1)) {
					$buf = array_values($result[0]);
					return $buf[0];
				} elseif (count($result)==0)
					return null;
			} 
			return $result;
		}
		catch(Exception $e) {
			$this->p_currentError = $e->getMessage();
			$this->p_lastError = $this->p_currentError;
			return false;
		}
	}
	function runScript($script) {
		try {
			$fresult = true;
			$this->p_currentError = '';
			if (is_array($script)) {
				$this->conn->beginTransaction();
				foreach ($script as $sql)
					if (is_array($sql))
						$this->conn->exec(implode(' ',$sql));
					else
						$this->conn->exec($sql);
				$this->conn->commit();
			} else {
					throw new RuntimeException('RunScript expects an array of sql commands');
		    }
		}
		catch(PDOException $e) {
			$this->p_currentError = $e->getMessage();
			$this->p_lastError = $this->p_currentError;
			if (is_array($script))
				$this->conn-rollback();
			$fresult = false;
	    }
		return $fresult;
	}
	function get_servername() {
		return $this->connParam[DB_SERVER];
	}
	function set_servername($newname) {
		if ($this->connected())
			$this->disconnect();
		$this->connParam[DB_SERVER] = $newname;
	}
	function get_databasename() {
		return $this->connParam[DB_DATABASE];
	}
	function set_databasename($newname) {
		if ($this->connected())
			$this->disconnect();
		$this->connParam[DB_DATABASE] = $newname;
	}
	function get_username() {
		return $this->connParam[DB_USER];
	}
	function set_username($newname) {
		if ($this->connected())
			$this->disconnect();
		$this->connParam[DB_USER] = $newname;
	}
	function get_password() {
		return $this->connParam[DB_PASSWORD];
	}
	function set_password($newname) {
		if ($this->connected())
			$this->disconnect();
		$this->connParam[DB_PASSWORD] = $newname;
	}
	function currentError() {
		return $this->p_currentError;
	}
	function lastError() {
		return $this->p_lastError;
	}
}

function libdb_real_escape_string($atext) {
	return strtr($atext, array(
		"\x00" => '\x00',
		"\n" => '\n', 
		"\r" => '\r', 
		'\\' => '\\\\',
		"'" => "\'", 
		'"' => '\"', 
		"\x1a" => '\x1a'));
}
function filterInput($data) {
  return htmlspecialchars(stripslashes(trim($data)));
}
function random_str($length = 32, $keyspace = '56789ABCDEFGHIJKLMnopqrstuvwxyz01234abcdefghijklmNOPQRSTUVWXYZ') {
	if (phpversion()<'7') {
		$cursor = 255;
		$str = '';
		$max = mb_strlen($keyspace);
		$maxbyte = (floor(255/$max) * $max) - 1;
		
		for ($i = 0; $i < $length;$i++) {
			do {
				if ($cursor>254) {
					$cursor = 0;
					$bytes = openssl_random_pseudo_bytes(255, $cstrong);
				} else {
					$cursor++;
				}	
			} while (ord($bytes[$cursor])>$maxbyte);
			
			$index = ord($bytes[$cursor]) % $max;
			$str .= $keyspace[$index];
		}	
    } else {
		$str = '';
		$max = mb_strlen($keyspace, '8bit') - 1;
		for ($i = 0; $i < $length; ++$i) {
			$str .= $keyspace[random_int(0, $max)];
		}
	}
	return $str;
}


?>
