<?php
class CreateAndStoreUserDetail {
    public $csvata = array();
    public $data;
    public function getOption()
    {
        //phpinfo();
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
        if(array_key_exists("file",$options))
        {
            $this->readCSV($options["file"]);
        }
        if(array_key_exists("create_table",$options))
        {
            echo "in create table";
            //echo $options["u"];
            $this->createTable($options["u"],$options["p"], $options["h"]);
        }


        
    }
    public function readCSV($filename)
    {
        if(!file_exists($filename) || !is_readable($filename))
            echo "file does not exists";
        $header = NULL;
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            while (($row = fgetcsv($handle)) !== FALSE)
                {
                    if(!$header)
                        $header = $row;
                    else
                        $csvData[] = array_combine($header, $row);
                }
                fclose($handle);
                print_r($csvData);
        }
        echo(count($csvData));
        for($c=0; $c<count($csvData);$c++)
        {
            $data = $csvData[$c];
            echo($data["name"]);
        }
    }

    public function createTable($username,$password,$hostvalue) {
        $host        = "host = ".$hostvalue."";
        $port        = "port = 5432";
        $dbname      = "dbname = userDb";
        $credentials = "user = ".$username." password=".$password."";

        $db = pg_connect( "$host $port $dbname $credentials");
        if(!$db) 
        {
            echo "Error : Unable to open database\n";
        } 
        else 
        {
            echo "Opened database successfully\n";
        }
        $sql =<<<EOF
      CREATE TABLE IF NOT EXISTS users
      (name character varying(255),
      surname character varying(255),
      email character varying(255) UNIQUE);
EOF;
        //print_r($data);
        /*$sql = <<<EOF 
                CREATE TABLE IF NOT EXISTS users 
                (
                        name character varying(255),
                        surname character varying(255),
                        email character varying(255) UNIQUE);
                EOF;*/
        $ret = pg_query($db, $sql);
        if(!$ret) 
        {
            echo pg_last_error($db);
        } 
        else 
        {
            echo "Table created successfully\n";
        }
        pg_close($db);
        
        // execute each sql statement to create new tables
        /*foreach ($sqlList as $sql) {
            $this->pdo->exec($sql);
        }
        
        return $this;*/
    }
}
$apple = new CreateAndStoreUserDetail();
$apple->getOption();
?>