<?php
namespace Omatech\Models;

class AppModel {

    public $conn;
    protected $debug;
	protected $show_inmediate_debug = false;
    public $debug_messages;

    function __construct($conn = null)
    {
        if (is_array($conn)) {
            $config = new \Doctrine\DBAL\Configuration();
            //..
            $connectionParams = array(
                'dbname' => $conn['dbname'],
                'user' => $conn['dbuser'],
                'password' => $conn['dbpass'],
                'host' => $conn['dbhost'],
                'driver' => 'pdo_mysql',
                'charset' => 'utf8'
            );
            $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        }
        $this->conn = $conn;
    }

	public function debug($str) {
		$add = '';
		if ($this->debug) {
			if (is_array($str)) {
				$add .= print_r($str, true);
			} else {// cas normal, es un string
				$add .= $str;
			}

			$this->debug_messages .= $add;
			if ($this->show_inmediate_debug)
				echo $add;
		}
	}
	
	public function deleteTable($table)
	{
		$this->conn->executeQuery("delete from $table");
	}
	
	public function bulkImportTable($table, $rows, $rows_to_process = 1000, $delete_first = false) {
		if ($delete_first) $this->deleteTable($table);

		$fields_array = array();
		if (!$rows) return;
		foreach ($rows[0] as $key => $val) {
			$fields_array[] = $key;
		}
		$fields = implode(',', $fields_array);

		$i = 1;
		$initial_sql = "insert into $table ($fields) values ";
		$sql = $initial_sql;
		$num_values=0;
		foreach ($rows as $row) {
			$sql .= '(';
			foreach ($row as $val) {
				if ((isset($val) && trim($val)!='' && !is_numeric($val) && $val!='now()') 
				|| substr($val,0,1)=='0'// Añadido para que no cargue los codigos 0654 por ejemplo como 654 en la base de datos
				|| stripos($val, 'E')!==false // Añadido para que codigos tipo 8E0411318 no me los interprete como numericos
				) 
				{
					$val=mb_convert_encoding($val, "UTF-8", mb_detect_encoding($val, "UTF-8, ISO-8859-1, ISO-8859-15", true));
					$val=$this->conn->quote(trim($val));
				}
				if (trim($val)=='') $val='null';
				
				$sql .= "$val,";
			}
			$sql = substr($sql, 0, -1);
			$sql .= "),";


			if ($i % $rows_to_process == 0) {// ja portem 1000, executem
				$sql = substr($sql, 0, -1); // eliminem la ultima ,
				//echo "***$i - $sql!!!\n";
				$this->conn->query($sql);
				$sql = $initial_sql;
				$num_values=0;
				echo $i;
			} else {
				$num_values++;
				echo '.';
			}
			$i++;

		}
		if ($num_values>0)
		{
			$sql = substr($sql, 0, -1); // eliminem la ultima ,
			//echo "***$sql!!!\n";
			//$this->last_massive_sql = $sql;
			$this->conn->query($sql);
		}
		echo "\n* DONE $i records\n";
		return $i;
	}

	function execute_query($sql)
	{
		return $this->conn->executeQuery($sql);
	}

	function start_transaction() {
		$sql = "start transaction";
		$this->conn->executeQuery($sql);
	}

	function commit() {
		$sql = "commit";
		$this->conn->executeQuery($sql);
	}

	function rollback() {
		$sql = "rollback";
		$this->conn->executeQuery($sql);
	}
	
function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}	


function load_data_into_table ($company_id, $file, $table, $mapping_function, $bulk=1000, $ignore_lines=1)
{
    if (!file_exists($file)) {
        echo ("File $file not found\n");
    }
    else {
    //The file exists
      $this->debug("Processing file $file for company_id=$company_id in table $table\n");
    
      $i = 0;
      $load_array = array();
      if (($handle = fopen($file, "r")) !== FALSE) 
      {
          while (($data = fgetcsv($handle, 10000, ";")) !== FALSE) 
          {
              if ($i >= $ignore_lines) {
				  
                  if ($i % 100 == 0)
                      $this->debug(".");
                  if ($i % $bulk == 0) {
                      $this->bulkImportTable($table, $load_array, $bulk);
                      $this->debug($i);
                      $load_array = [];
                  }
    
				  $one_line=$mapping_function($company_id, $data, $this);
				  if ($one_line) $load_array[]=$one_line;
              }
              $i++;
          }
          fclose($handle);
      }
      $this->bulkImportTable($table, $load_array, $bulk);
      $this->debug("\n$i records loaded in table $table\n");
    }
    return $i;    
}

function drop_table($table)
{
	$this->debug("Deleting table $table\n");
	$sql="DROP TABLE IF EXISTS `$table`;";
	$this->conn->query($sql);
	$this->debug("DONE deleting table $table\n");
}

function regenerate_table ($table, $definition)
{

	$this->drop_table($table);

	$this->debug("Create table\n");
	$sql=$definition;
	$this->conn->query($sql);
	$this->debug("DONE creating table\n");
}

function cacheCodeTable ($table)
{
	$sql="select id, code from $table group by id, code";
	$rows = $this->conn->fetchAll($sql);
	$res=array();
	foreach ($rows as $row)
	{
		$res[$row['code']]=$row['id'];
	}
	return $res;
}

function cacheQuery ($sql)
{// sql must return 2 columns, first column named code and second named value
	$rows = $this->conn->fetchAll($sql);
	$res=array();
	foreach ($rows as $row)
	{
		$res[$row['code']]=$row['value'];
	}
	return $res;
}


function yyyymmddToMySqlDate ($yyyymmdd)
{
	if (!isset($yyyymmdd)) return null;
	if ($yyyymmdd==0) return null;
	if (empty($yyyymmdd)) return null;
	
	$year=substr($yyyymmdd, 0, 4);
	$month=substr($yyyymmdd, 4, 2);
	$day=substr($yyyymmdd, 6, 2);
	return ("$year-$month-$day");
}

function ddmmyyyySlashesToMySqlDate ($ddmmyyyy)
{
	if (!isset($ddmmyyyy)) return null;
	if ($ddmmyyyy==0) return null;
	if (empty($ddmmyyyy)) return null;
	
	$year=substr($ddmmyyyy, 6, 4);
	$month=substr($ddmmyyyy, 3, 2);
	$day=substr($ddmmyyyy, 0, 2);
	//echo "Input $ddmmyyyy year=$year month=$month day=$day\n";
	return ("$year-$month-$day");
}	


function cleanNumber($number) {
	//$original_number=$number;
	$number = str_replace('.', '', $number);
	$number = str_replace(',', '.', $number);
	//echo "$original_number -> $number\n";
	if (!empty($number) && $number != '' && is_numeric($number)) {
		return $number;
	} else {
		return 0;
	}
}

}