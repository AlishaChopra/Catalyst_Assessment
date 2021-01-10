<?php
class CreateAndStoreUserDetail {
    public $csvData = array();
    public $data;
    public function getOption()
    {
        $shortopts  = "";
        $shortopts .= "u:";  
        $shortopts .= "p:"; 
        $shortopts .= "h:";

        $longopts  = array(
            "file:",    
            "create_table::",   
            "dry_run::"
        );
        $options = getopt($shortopts, $longopts);
        var_dump($options);
        if(array_key_exists("create_table",$options))
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
        if(array_key_exists("file",$options))
        {
            $csvData=$this->readCSV($options["file"]);
            $db = $this->databaseConnectivity($options["u"],$options["p"], $options["h"]);
            if($db) 
            {
                $result = $this->createTable($db);
                if($result)
                {
                    for($c=0; $c<count($csvData);$c++)
                    {
                        $data = $csvData[$c];
                        echo($data["email"]);
                        if(filter_var($data["email"], FILTER_VALIDATE_EMAIL))
                        {
                            $this->insertData($db,$data);
                        }
                        else
                        {
                            echo ("invalid email");
                        }
                    }
                    pg_close($db);           
                }   
            } 
               
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
                        //$b = array_map('trim', $header);
                        //echo("header trim");
                        //print_r($b);
                        $csvData[] = array_combine($header, $row);
                        print_r($csvData);
                }
                fclose($handle);
                return $csvData;     
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
    public function insertData($db, $data)
    {
        $name = ucwords(strtolower(pg_escape_string($data["name"])));
        $surname = ucwords(strtolower(pg_escape_string($data["surname"])));
        $email = strtolower((pg_escape_string($data["email"])));
        $sql ="SELECT * from USERS WHERE email='$email'";
        $result = pg_query($db, $sql);
        $rows = pg_num_rows($result);
        if($rows==0) 
        {
            $sqlInsertQuery ="INSERT INTO USERS(name,surname,email)
                          VALUES ('$name', '$surname','$email');";
            $returnVal = pg_query($db, $sqlInsertQuery);
            if(!$returnVal) 
            {
                echo pg_last_error($db);
            } 
            else 
            {
                echo "Records created successfully\n";
            }
           
        }
    }
}
$apple = new CreateAndStoreUserDetail();
$apple->getOption();
?>