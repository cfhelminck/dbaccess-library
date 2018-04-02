********************
      USAGE SAMPLE
********************
<?php
require_once('lib_database.php');
require_once('lib_query.php');

$dbserver = '127.0.0.1';
$dbname   = '';
$dbuser   = 'root';
$dbpasswd = '';

$myDB = new Database($dbserver,$dbname,$dbuser,$dbpasswd);
if (!$myDB->connectMySQL())
    die("Fout: ". $myDB->currentError());

// Sample error handling
$result = $myDB->runQuery('selectt id from albums');
if ($result===false) {
    echo "Fout bij uitvoeren van runQuery:\n",$myDB->currentError()."\n";
} else { // toon gegevensset
        var_dump($result);
}
$qry = new Query($myDB,'SELECT table_name, table_type, engine, TABLE_COMMENT ' .
                       'FROM information_schema.tables ' .
                       'WHERE TABLE_SCHEMA = ?',array('mysql')); 

# parse result set
echo "\nMethod 1: EOF (End of File)\n";

$qry->open();
while (!$qry->EOF()) {
    echo $qry->fieldValue('table_name'),': ',$qry->fieldValue('engine'),"\n";
    $qry->next();
}

echo "\Method 2: getRow\n";

$qry->first();
while ($qry->getRow($row))  {
    echo $row["table_name"].': '.$row['table_type']."\n";
}

echo "\nNr Of Tables          : ",
         $myDB->runQuery('select count(*) from information_schema.tables'),"\n";
echo "\nNr of fields in query : ",
         $qry->nrOfFields(),"\n";
echo "\nFieldNames in qry     : ",
         implode(", ",$qry->fieldNames()),"\n";
?>
