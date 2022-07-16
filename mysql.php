<?php

namespace MohammadAzad;

class MySqlMethodErrors extends \Exception {
    public $string,$guide,$method,$code;
    public function __construct($string_error,$guide,$code,$method="None")
    {
        $this->string = $string_error;
        $this->guide = $guide;
        $this->code = $code;
        $this->method = $method;

    }
    public function Message() {
        return "-----Method------".PHP_EOL."
            (".$this->code.") ".$this->string.PHP_EOL."
            Guide: ".$this->guide.PHP_EOL."
            Method: ".$this->method.PHP_EOL."
---------------
        ";
    }
}

class MySqlQueryErrors extends \Exception {
    public $string,$query,$method,$code;
    public function __construct($string_error,$query,$code,$method="None")
    {
        $this->string = $string_error;
        $this->query = $query;
        $this->code = $code;
        $this->method = $method;
    }
    public function Message() {
        return "-----Query------".PHP_EOL."
            (".$this->code.") ".$this->string.PHP_EOL."
            Query: ".$this->query.PHP_EOL."
            Method: ".$this->method.PHP_EOL."
---------------
        ";
    }
}

class Mysql extends \Mysqli {
    private $db;
    public $table;
    public function __construct($host,$user,$pass,$db_name=""){
        try {
            $this->db = parent::__construct($host, $user, $pass, $db_name);
            return $this->db;
        } catch (\mysqli_sql_exception $Error) {
            throw new MySqlQueryErrors($Error->GetMessage(),"No Query",$Error->GetCode(),__FUNCTION__);
        }
        
    }
    public function __destruct()
    {
        $this->close();
    }

    private function format_column($data,$method_name) {
        $AllowedKeys=['name','type','size','auto_increment','primary_key','not_null'];
        if(count(array_diff(array_keys($data),array_values($AllowedKeys))) > 0){
            throw new MySqlMethodErrors("One or more keys are invalid","Invalid keys-> ".rtrim(array_reduce(array_diff(array_keys($data),array_values($AllowedKeys)),function ($x,$y) {
                return $x.$y."-";
            }),"-"),1,$method_name);
        }
        $auto_increment = (isset($data['auto_increment']) && $data['auto_increment'] == true)?"AUTO_INCREMENT":"";
        $primary_key = (isset($data['primary_key']) && $data['primary_key'] == true)?"primary key":"";
        $not_null = (isset($data['not_null']) && $data['not_null'] == true)?"NOT NULL":"";
        $size = (isset($data['size']))?"(".$data['size'].")":"";
        return $data["name"]." ".$data["type"]." ".$size." ".$not_null." ".$primary_key." ".$auto_increment;
    }
    private function format_syntax($value) {
        $value = parent::escape_string($value);
        return (is_numeric($value))?$value:"'".$value."'";
    }
    /**
     * Checking the existence of a column
     * @return true|false
     * 
    */
    public function ColumnExists(string $column_name) {
        if(!isset($this->table)) {
            throw new MySqlMethodErrors("The table name is not passed",'use $MyDatabase->table = "TABLE_NAME";',1,__FUNCTION__);
        }
        $query = "SHOW COLUMNS FROM `".$this->table."` LIKE '".$column_name."'";
        try {
            $query = $this->query($query);
            return $query->num_rows?TRUE:FALSE;
        } catch (\mysqli_sql_exception $Error) {
            throw new MySqlQueryErrors($Error->GetMessage(),$query,$Error->GetCode(),__FUNCTION__);
        }
    }
    /**
     * Create a new column
    */
    public function CreateColumn(array ...$column_name) {
        if(!isset($this->table)) {
            throw new MySqlMethodErrors("The table name is not passed",'use $MyDatabase->table = "TABLE_NAME";',1,__FUNCTION__);
        }
        $query = "ALTER TABLE `".$this->table."` ";
        foreach($column_name as $value) {
            $query .= "ADD ".$this->format_column($value,"CreateColumn").",";
        }
        $query = rtrim($query,",");
        try {
            return $this->query($query);
        } catch (\mysqli_sql_exception $Error) {
            throw new MySqlQueryErrors($Error->GetMessage(),$query,$Error->GetCode(),__FUNCTION__);
        }
    }
    /**
     * Delete a column from the table
    */
    public function DeleteColumn(string $column_name) {
        if(!isset($this->table)) {
            throw new MySqlMethodErrors("The table name is not passed",'use $MyDatabase->table = "TABLE_NAME";',1,__FUNCTION__);
        }
        $query = "ALTER TABLE `".$this->table."` DROP COLUMN ".$column_name;
        try {
            return $this->query($query);
        } catch (\mysqli_sql_exception $Error) {
            throw new MySqlQueryErrors($Error->GetMessage(),$query,$Error->GetCode(),__FUNCTION__);
        }
    }

    /**
     * Add a new record to the table
    */
    public function InsertData(array $data) {
        if(!isset($this->table)) {
            throw new MySqlMethodErrors("The table name is not passed",'use $MyDatabase->table = "TABLE_NAME";',1,__FUNCTION__);
        }
        $query = "INSERT INTO `".$this->table."` ";
        $column = rtrim(array_reduce(array_keys($data),function ($x,$y) { return $x.$y.","; }),",");
        $value = rtrim(array_reduce(array_values($data),function ($x,$y) { return $x."'".$y."',"; }),",");
        $query .= "(".$column.") VALUES (".$value.")";
        try {
            return $this->query($query);
        } catch (\mysqli_sql_exception $Error) {
            throw new MySqlQueryErrors($Error->GetMessage(),$query,$Error->GetCode(),__FUNCTION__);
        }
    }
    /**
     * Adding multiple data to the table
    */
    function MultiInsertData($columns,$data) {
        $query = "INSERT INTO `".$this->table."` (";
        $column_name = "";
        foreach($columns as $value) {
            $column_name .= $value.",";
        }
        $query .= rtrim($column_name," ,");
        $query .= ") VALUES ";
        foreach($data as $value) {
            $query .= "(";
            foreach($value as $v) {
                $query .= $this->format_syntax($v).',';
            }
            $query = rtrim($query," ,");
            $query .= "),";
        }
        $query = rtrim($query," ,");
        try {
            return $this->query($query);
        } catch (\mysqli_sql_exception $Error) {
            throw new MySqlQueryErrors($Error->GetMessage(),$query,$Error->GetCode(),__FUNCTION__);
        }
    }
    /**
     * Search for a record
     * @param string $where last_name = 'azad' AND age = 20
     * @param string $select (Optional) - You can enter a column name
     * @return array|false an array containing all the data of a record
    */
    public function Find(string $where,$select="*") {
        if(!isset($this->table)) {
            throw new MySqlMethodErrors("The table name is not passed",'use $MyDatabase->table = "TABLE_NAME";',1,__FUNCTION__);
        }
        $query = "SELECT ".$select." FROM `".$this->table."` WHERE ".$where;
        try {
            $data = $this->query($query);
            if ($data->num_rows > 0) {
                return $data->fetch_assoc();
            } else {
                return false;
            }
        } catch (\mysqli_sql_exception $Error) {
            throw new MySqlQueryErrors($Error->GetMessage(),$query,$Error->GetCode(),__FUNCTION__);
        }
    }
    /**
     * Search for a record
     * @param string $where age = 20
     * @param string $select (Optional) - You can enter a column name
     * @return array|false A generators containing all records found
    */
    public function FindAll(string $where,$select="*") {
        if(!isset($this->table)) {
            throw new MySqlMethodErrors("The table name is not passed",'use $MyDatabase->table = "TABLE_NAME";',1,__FUNCTION__);
        }
        $query = "SELECT ".$select." FROM `".$this->table."` WHERE ".$where;
        try{
            $data = $this->query($query);
            if ($data->num_rows > 0) {
                while($row = $data->fetch_assoc()) {
                    yield $row;
                }
            } else {
                return false;
            }
        } catch (\mysqli_sql_exception $Error) {
            throw new MySqlQueryErrors($Error->GetMessage(),$query,$Error->GetCode(),__FUNCTION__);
        }
    }
    /**
     * Search for a record
     * @param array $data ['last_name'=>'azad2','age'=>22]
     * @param array $find (Optional) - The output array from the Find method
     * @return boolean
    */
    public function Update(array $data,array $find=null){
        if(!isset($this->table)) {
            throw new MySqlMethodErrors("The table name is not passed",'use $MyDatabase->table = "TABLE_NAME";',1,__FUNCTION__);
        }
        $query = "UPDATE `".$this->table."` SET ";
        foreach($data as $key => $value) {
            $value = ($value != null)?$this->format_syntax($value):"''";
            $query .= $key." = ".$value.",";
        }
        $query = rtrim($query,",");
        if ($find != null) {
            $query .= " WHERE ";
            foreach($find as $key => $value) {
                if($value == null) {
                    continue;
                }
                $value = $this->format_syntax($value);
                $query .= $key." = ".$value." AND ";
            }
            $query = rtrim($query," AND ");
        }
        try {
            return $this->query($query);
        } catch (\mysqli_sql_exception $Error) {
            throw new MySqlQueryErrors($Error->GetMessage(),$query,$Error->GetCode(),__FUNCTION__);
        }
    }
    /**
     * Create a table if it does not exist
     * @param array ...$columns An array that can contain these keys:
     * [name] : column name
     * [type] : column type (int,varchar,...)
     * [size] : length
     * [auto_increment] : (true|false) auto increment (It requires the primary key to be true)
     * [primary_key] : (true|false) The primary keys in the data table are never duplicated
     * [not_null] : (true|false) Handling the null value
     * @param array $find (Optional) - The output array from the Find method
     * @return boolean
    */
    public function Create(array ...$columns) {
        if(!isset($this->table)) {
            throw new MySqlMethodErrors("The table name is not passed",'use $MyDatabase->table = "TABLE_NAME";',1,__FUNCTION__);
        }
        $query = "CREATE TABLE IF NOT EXISTS `".$this->table."` (";
        foreach ($columns as $value) {
            $query .= $this->format_column($value,"Create")." ,";
        }
        $query = rtrim($query,",");
        $query .= ")";
        try {
            return $this->query($query);
        } catch (\mysqli_sql_exception $Error) {
            throw new MySqlQueryErrors($Error->GetMessage(),$query,$Error->GetCode(),__FUNCTION__);
        }
    }
}