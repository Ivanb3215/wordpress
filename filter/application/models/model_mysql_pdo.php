<?php

/**
 * Class model Mysql
 * PDO MySQL interface
 * 
 * @author Alex Kaydansky <kaydansky@gmail.com>
 * @package EmailFiltering Dashboard software
 * @version 1.0
 * @since 2014/11/02
 */
class Model_Mysql
{   
    /**
     * Establish database connection
     * 
     * @access public
     * @uses constants defined in configuration file
     * @param void
     * @return void
     */
    public function db_connect()
    {
        $db = include CONFIG_PATH . 'database/database.php';

        try {
            $this->DBH = new PDO('mysql:host=' . $db['hostname'] . ';dbname=' . $db['database'], $db['username'], $db['password']);
            $this->DBH->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e) {  
            echo $e->getMessage();  
        }

        return $this->DBH;
    }

    /**
    * Issue query
    * o Return fetch assoc array if any
    * o Return last insert id if any
    * o Returns affected/retrieved rows number
    * 
    * @access public
    * @param string $sql
    * @param array $data
    * @return array
    */
    public function db_query($dbh, $sql, array $data = null) 
    {
        $result = null;
        $STH = $dbh->prepare($sql);
        $STH->setFetchMode(PDO::FETCH_ASSOC);
        $STH->execute($data);

        if (strpos($sql, 'SELECT') !== false || strpos($sql, 'SHOW') !== false) {
            foreach ($STH as $row)
                $result[] = $row;
        }
        
        return array('data' => $result, 'lastId' => $dbh->lastInsertId(), 'rows' => $STH->rowCount());
	}
}
?>
