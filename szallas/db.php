<?php

class db_controller
{
    private $mysqli;
    public $security_preg_patterns;
    
    function __construct()
    {
        require_once('config/config.php');
        $this->security_preg_patterns = $security_preg_patterns;
        
        require_once 'db.php';
        $this->dbconn();
    }
    
    function __destruct()
    {
        $this->mysqli->close();
    }
    
    private function dbconn()
    {
        $this->mysqli = new mysqli (__DB_SERVER__,__DB_USER__,__DB_PASS__);
        if ($this->mysqli->connect_errno)
        {
            die ("<br><br>Nem lehet kapcsolódni a MySQL szerverhez! Hibakód: " . $this->mysqli->connect_errno . "<br>" . $this->mysqli->connect_error . PHP_EOL);
        }
        $this->db_table_chk($this->mysqli);
    } 
    
    private function db_table_chk(&$mysqli)
    {
        $mysqli->select_db(__DB_NAME__);
        if ($mysqli->errno)
        {
            die ("<br><br>Adatbázis elérési hiba! Hibakód: " . $mysqli->errno . "<br>" . $mysqli->error . PHP_EOL);
        }
        $this->db_utf8_chk($mysqli);
    }
    
    private function db_utf8_chk(&$mysqli)
    {
        $mysqli->set_charset('utf8');
        if ($mysqli->errno)
        {
            echo ("<br><br>UTF8 karakterkészlet beállítási hiba! Lehetséges, nem a megfelelő formátumban fognak megjelenni a betűk! Hibakód: " . $mysqli->errno . "<br>" . $mysqli->error . PHP_EOL);
        }
    }

    public function sql_query($sql,$forced_arr = false)
    {
        $return = array();
        
        if (!empty($sql))
        {
            $data_values = $this->mysqli->query($sql);
            if (!is_object($data_values))
            {
                $return = $data_values;
            }
            else if (!empty($data_values->num_rows) && $data_values->num_rows>0)
            {
                if ($data_values->num_rows == 1 && $forced_arr == false)
                {
                    $return = $data_values->fetch_assoc();
                }
                else
                {
                    while ($record = $data_values->fetch_array(MYSQLI_ASSOC))
                    {
                        $return[] = $record;
                    }
                }
            }
        }
        
        return $return;
    }
}

?>
