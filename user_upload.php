<?php
class CreateAndStoreUserDetail {
    public $csvData = array();
    public $data;
    public $count;
    public $errorLog;
    public $options;
    public function getOption()
    {
        $this->count = 0;
        $this->errorLog = " ";
        $shortopts  = "";
        $shortopts .= "u:";  
        $shortopts .= "p:"; 
        $shortopts .= "h:"; 
        $longopts  = array(
            "file:",    
            "create_table::",   
            "dry_run::",
            "help::"
        );
        $this->options = getopt($shortopts, $longopts);
        if(array_key_exists("file",$this->options) && array_key_exists("dry_run",$this->options))
        {
            if($this->validate("file"))
            {
                if(!$this->readCSV($this->options["file"]))
                    echo "CSV file does not exists";
                else{
                    for($c=0; $c<count($this->csvData);$c++)
                    {
                        if($this->validateEmail($this->csvData[$c]))
                        {
                            $this->count = $this->count + 1;
                        }
                    }
                    echo $this->count. " Out of " . count($this->csvData) . " Users have valid email\n";
                    echo count($this->csvData)-$this->count . " Users does not have  valid email\n";
                }
            }
            else
            {
                echo "File Name is not provided";
            }
        }
        elseif(array_key_exists("create_table",$this->options))
        {
            if($this->validate("u") && $this->validate("p") && $this->validate("h"))
            {
                $db = $this->databaseConnectivity($this->options["u"],$this->options["p"], $this->options["h"]);
                if(!$db) 
                    echo "Error : Unable to open database\n";
                else 
                {
                    if($this->createTable($db))
                        echo "Table created successfully";
                    else
                        echo pg_last_error($db);
                    pg_close($db);
                }
            }
            else{
                echo "Hostname, username, and password is not provided";
            }   
        } 
        elseif(array_key_exists("file",$this->options))
        {
            if($this->validate("file"))
            {
                if($this->readCSV($this->options["file"]))
                {
                    if($this->validate("u") && $this->validate("p") && $this->validate("h"))
                    {
                        $db = $this->databaseConnectivity($this->options["u"],$this->options["p"], $this->options["h"]);
                        if($db) 
                        {
                            if($this->createTable($db))
                            {
                                for($c=0; $c<count($this->csvData);$c++)
                                {
                                    if($this->validateEmail($this->csvData[$c]))
                                    {
                                        $this->insertData($db,$this->csvData[$c]);
                                    }
                                    else
                                    {
                                        $this->errorLog = $this->errorLog. $this->csvData[$c]["name"] . " " . $this->csvData[$c]["surname"]. " ". $this->csvData[$c]["email"] . " invalid email\n" ;
                                    }
                                }
                                echo $this->count. " Out of " . count($this->csvData) . " Processed Successfully\n";
                                echo "Error log: \n" .   $this->errorLog;
                                pg_close($db);           
                            }   
                        }
                    } 
                    else
                    {
                        echo "Hostname, username, and password is not provided";
                    }       
                }
            }
            else
            {
                echo "File name is not provided";
            }
        }   
        elseif(array_key_exists("help",$this->options))
        {
            echo "
            --file [csv file name] – this is the name of the CSV to be parsed\n
            --create_table – this will cause the PostgreSQL users table to be built (and no further action
            will be taken)\n
            --dry_run – this will be used with the --file directive in case we want to run the script but not
            insert into the DB. All other functions will be executed, but the database won't be altered\n
            -u – PostgreSQL username\n
            -p – PostgreSQL password\n
            -h – PostgreSQL host";
        }   
    }
    public function readCSV($filename)
    {
        if(!file_exists($filename) || !is_readable($filename))
            return FALSE;
        $header = NULL;
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            while (($row = fgetcsv($handle)) !== FALSE)
            {
                if(!$header)
                    $header = array_map('trim', $row);
                else
                    $this->csvData[] = array_combine($header, $row);
            }
            fclose($handle);
            return TRUE;     
        }
    }
    public function databaseConnectivity($username,$password,$hostvalue)
    {
        $host        = "host = ".$hostvalue."";
        $port        = "port = 5432";
        $dbname      = "dbname = postgres";
        $credentials = "user = ".$username." password=".$password."";
        $db = pg_connect( "$host $port $dbname $credentials");
        return $db;
    }
    public function createTable($db) 
    {    
        $sql ="CREATE TABLE IF NOT EXISTS users
        (name character varying(255),
        surname character varying(255),
        email character varying(255) UNIQUE);";
        $ret = pg_query($db, $sql);
        return $ret;    
    }
    public function insertData($db, $data)
    {
        $name = ucwords(strtolower(pg_escape_string($data["name"])));
        $surname = ucwords(strtolower(pg_escape_string($data["surname"])));
        $email = strtolower((pg_escape_string($data["email"])));
        $sql ="SELECT * from USERS WHERE email='$email'"; // check whether an email exists before inserting
        $result = pg_query($db, $sql);
        $rows = pg_num_rows($result);
        if($rows==0) 
        {
            $sqlInsertQuery = "INSERT INTO USERS(name,surname,email)
                               VALUES ('$name', '$surname','$email');";
            $returnVal = pg_query($db, $sqlInsertQuery);
            if(!$returnVal) 
            {
                $this->errorLog = $this->errorLog.pg_last_error($db)."\n";    
            } 
            else 
            {
                $this->count = $this->count + pg_affected_rows($returnVal);
            } 
        }
        else
        {
            $this->errorLog = $this->errorLog. $name . " " . $surname . " " . $email . " User exists" .  "\n";
        }
    }
    public function validate($cmdOption)
    {
        if(isset($this->options[$cmdOption]))
            return TRUE;
        else
            return FALSE;    
    }
    public function validateEmail($data)
    {
        if(filter_var($data["email"], FILTER_VALIDATE_EMAIL))
            return TRUE;
        else
            return FALSE;
    }
}
$userDetail = new CreateAndStoreUserDetail();
$userDetail->getOption();
?>