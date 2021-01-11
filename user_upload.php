<?php
class CreateAndStoreUserDetail {
    public $csvData = array();
    public $data;
    public $count;
    public $errorLog;
    //public $invalidEmailCount;
    public function getOption()
    {
        $shortopts  = "";
        $shortopts .= "u:";  
        $shortopts .= "p:"; 
        $shortopts .= "h:";
        $this->count = 0;
        $this->errorLog = " ";
        $longopts  = array(
            "file:",    
            "create_table::",   
            "dry_run::",
            "help::"
        );
        $options = getopt($shortopts, $longopts);
        var_dump($options);
        if(array_key_exists("file",$options) && array_key_exists("dry_run",$options) )
        {
            if(isset($options["file"]))
            {
                $csvData=$this->readCSV($options["file"]);
                echo "dry run completed";
            }
            else
            {
                echo "File Name is not provided";
            }
        }
        elseif(array_key_exists("create_table",$options))
        {
            if(isset($options["u"]) && isset($options["p"]) && isset($options["h"]))
            {
                $db = $this->databaseConnectivity($options["u"],$options["p"], $options["h"]);
                if(!$db) 
                {
                    echo "Error : Unable to open database\n";
                } 
                else 
                {
                    $result=$this->createTable($db);
                    if($result)
                    {
                        echo "Table created successfully";
                    }
                    else
                    {
                        echo pg_last_error($db);
                    }
                    pg_close($db);
                }
            }
            else{
                echo "Hostname, username, and password is not provided";
            }   
        } 
        elseif(array_key_exists("file",$options))
        {
            if(isset($options["file"]))
            {
                $csvData=$this->readCSV($options["file"]);
            }
            else
            {
                echo "File name is not provided";
            }
            
            if(isset($options["u"]) && isset($options["p"]) && isset($options["h"]))
            {
                $db = $this->databaseConnectivity($options["u"],$options["p"], $options["h"]);
                if($db) 
                {
                    $result = $this->createTable($db);
                    if($result)
                    {
                        for($c=0; $c<count($csvData);$c++)
                        {
                            $data = $csvData[$c];
                            if(filter_var($data["email"], FILTER_VALIDATE_EMAIL))
                            {
                                $this->insertData($db,$data,count($csvData));
                            }
                            else
                            {
                                $this->errorLog = $this->errorLog. $data["name"] . " " . $data["surname"]. " ". $data["email"] . " invalid email\n" ;
                            }
                        }
                        echo $this->count. " Out of " . count($csvData) . " Processed Successfully\n";
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
        /*elseif(array_key_exists("help",$options))
        {

        }*/   
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
                        $csvData[] = array_combine($header, $row);
                }
                fclose($handle);
                return TRUE;     
        }
    }

    public function databaseConnectivity($username,$password,$hostvalue)
    {
        $host        = "host = ".$hostvalue."";
        $port        = "port = 5432";
        $dbname      = "dbname = userDb";
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
        //pg_close($db);
        return $ret;    
    }
    public function insertData($db, $data, $totalRecords)
    {
        $name = ucwords(strtolower(pg_escape_string($data["name"])));
        $surname = ucwords(strtolower(pg_escape_string($data["surname"])));
        $email = strtolower((pg_escape_string($data["email"])));
        $sql ="SELECT * from USERS WHERE email='$email'";
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
}
$apple = new CreateAndStoreUserDetail();
$apple->getOption();
?>