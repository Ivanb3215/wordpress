<?php

/**
 * Class controller File Handler
 * o Move uploaded file
 * o Extract emails from file
 * o Verify emails if existant
 * o Check for duplicated emails
 * o Remove bounced and duplicated emails from file
 * o Record valid and unique emails into database
 * o Show uploaded files list
 * o Get file to download
 * o Delete file
 * 
 * @author Alex Kaydansky <kaydansky@gmail.com>
 * @package EmailFiltering Dashboard software
 * @version 2.0
 * @since 2014/11/02
 */
class File_Handler extends Model_Mysql
{

    /**
     * @var object 
     */
    private $Model_API;
    
    /**
     * Number of emails found in file
     * 
     * @access public
     * @var integer 
     */
    public $f_raw_num = 0;
    
    /**
     * Number of duplicated emails found in file
     * 
     * @access public
     * @var integer 
     */
    public $f_duplic_num = 0;
    
    /**
     * Number of invalid emails found in file
     * 
     * @deprecated since version 2.0
     * @access public
     * @var integer
     */
    public $f_error_num = 0;
    
    /**
     * Number of added emails
     * 
     * @access public
     * @var integer 
     */
    public $f_added_num = 0;
    
    /**
     * Number of lines without emails
     * 
     * @access public
     * @var integer
     */
    public $f_empty_num = 0;
    
    /**
     * Refactored file rows
     * 
     * @access public
     * @var array
     */
    public $new_rows = array();
    
    /**
     * Unique emails extracted from source file
     * 
     * @access public
     * @var array
     */
    public $unique_emails = array();
    
    public $current_emails = array();

    /**
     * File_Handler constructor
     * o Load SQL query templates list
     * o Create model Verify_Email_API instance
     * o Establish database connection
     * 
     * @access public
     * @param object $dbh
     * @return void
     */
    public function __construct($dbh)
    {
        $this->query = include QUERIES_PATH . 'queries_file_handler.php';
        $this->Model_API = new Verify_Email_API();
        $this->dbh = $dbh;

        return;
    }

    /**
     * Move uploaded source file to server
     * 
     * @access public
     * @param array $fileData User uploaded file
     * @return string File path
     * @throws Exception
     */
    public function move_file($fileData)
    {
        if (!is_dir(SOURCE_PATH)) {
            mkdir(SOURCE_PATH, 0777, true);
        }
        else {
            chmod(SOURCE_PATH, 0777);
        }
        
        $uploadfile = SOURCE_PATH . '/' . str_replace(' ', '', basename($fileData['csv']['name']));

        if (move_uploaded_file($fileData['csv']['tmp_name'], $uploadfile)) {
            chmod(SOURCE_PATH, 0755);
            return $uploadfile;
        }
        else {
            throw new Exception('File has not been uploaded' . "\n<br />");
            chmod(SOURCE_PATH, 0755);
            return false;
        }
    }
    
    /**
     * Convert csv file into array
     * 
     * @access public
     * @param string $file
     * @return array
     * @throws Exception
     */
    public function explode_source($file)
    {
        if (!file_exists($file)) {
            throw new Exception('File not found to explode source' . "\n<br />");
        }
        
        $this->start = microtime(true);
        
        ini_set('auto_detect_line_endings', true);
        
        if (($handle = fopen($file, 'r')) === false) {
            throw new Exception('Unable to read csv source' . "\n<br />");
        }
        
        while (($line = fgetcsv($handle, 5000, ',')) !== false) {
            $raw_rows[] = array_map('strtolower', $line);
        }

        $fileName = explode('/', $file);
        $this->file_name = end($fileName);
        
        return $raw_rows;
    }

    /**
     * Extract email adresses from source file
     * o Remove lines with duplicated emails from source file
     * o Remove lines without emails
     * o Count duplicated emails
     * o Count raw emails in source file
     * o Break down lines with multiple emails to separate lines
     * o Get unique emails
     * 
     * @access public
     * @param string $file
     * @return array
     * @throws Exception
     */
    public function extract_emails(array $array)
    {
        if (!count($array)) {
            throw new Exception('CSV array is not provided' . "\n<br />");
        }
        
        $targetPath = FILES_PATH . '/' . $this->file_name;
        $firstLine = array_shift($array);
        $this->pattern = '/[\.A-Za-z0-9_-]+@[\.A-Za-z0-9_-]+\.([A-Za-z0-9_-][A-Za-z0-9_]+)/';
        
        if (!preg_match_all($this->pattern, $firstLine[11])) {
            file_put_contents($targetPath, implode(',', $firstLine) . PHP_EOL, FILE_APPEND);
        }
        else {
            array_unshift($array, $firstLine);
        }
        
        $rawUniqueEmails = array();
        
        foreach ($array as $k => $line) {
            if (empty($line[11]) || !isset($line[11]) || $line[11] == '') {
                $this->f_empty_num++;
                unset($array[$k]);
                continue;
            }
            
            $lineEmails = explode(',', $line[11]);
            
            for ($i = 0; $i < 11; $i++) {
                if (preg_match_all($this->pattern, $line[$i])) {
                    $lineEmails[] = $line[$i];
                    $line[$i] = '';
                }

                if (strpos($line[$i], ',') !== false) {
                    $line[$i] = '"' . $line[$i] . '"';
                }
            }

            //$lineEmailsUnique = array_intersect($lineEmails, $this->unique_emails);

            if (!count($lineEmails)) {
                $this->f_empty_num++;
                unset($array[$k]);
                continue;
            }

            foreach ($lineEmails as $e) {
                $this->f_raw_num++;
                
                if (!$this->check_duplication($e) && !in_array($e, $rawUniqueEmails)) {
                    $line[11] = $e;
                    $this->current_emails[] = $e;
                    file_put_contents($targetPath, implode(',', $line) . PHP_EOL, FILE_APPEND);
                }
                else {
                    $this->f_duplic_num++;
                }

                $rawUniqueEmails[] = $e;
            }
            
            unset($array[$k]);

            if ((microtime(true) - $this->start) >= 15) {
                break;
            }
        }
        
        $this->sourcePath = SOURCE_PATH . '/' . $this->file_name;
        $restLines = '';
        $num_rest = count($array);

        if ($num_rest) {
            foreach ($array as $ar) {
                $ar = $this->add_quotes_to_arr($ar);
                $restLines .= implode(',', $ar) . PHP_EOL;
            }

            file_put_contents($this->sourcePath, $restLines);
        }
        else {
            unlink($this->sourcePath);
        }

        return $num_rest;
    }
    
    /**
     * Add quotes to array elements which have comma
     * 
     * @access public
     * @param array $a
     * @return array
     */
    public function add_quotes_to_arr($a)
    {
        foreach ($a as $k => $v) {
            if (strpos($v, ',') !== false) {
                $a[$k] = '"' . $v . '"';
            }
        }
        
        return $a;
    }
    
    /**
     * Verify email address using verify-email.org API
     * 
     * @deprecated since version 2.0
     * @access public
     * @uses model Verify_Email_API
     * @param string $address
     * @return boolean
     */
    public function verify_email_address($address)
    {
        /**
         * @todo Comment out to enable
         */
        return false;
        
        $object = $this->Model_API->call_api($address);
        
        if (!is_object($object)) {
            $_SESSION['message'] = 'API failure';
            return false;
        }
        
        if ($object->limit_status > 0) {
            $_SESSION['message'] = $object->limit_desc;
        }
        
        if (isset($object->verify_status)) {
            if ($object->verify_status > 0) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }
    
    /**
     * Verify email address using Byteplant API
     * 
     * @deprecated since version 2.0
     * @access public
     * @uses model Verify_Email_API
     * @param string $address
     * @return boolean
     */
    public function verify_email_address_byteplant($address)
    {
        /**
         * @todo Uncomment to disable
         */
        //return false;
        
        $object = $this->Model_API->call_api_Byteplant($address);
        
        if (!is_object($object)) {
            $_SESSION['message'] = 'API failure';
            return false;
        }
        
        if (!isset($object->status)) {
            $_SESSION['message'] = 'API error occured: ' . $object->info;
        }
        
        if ($object->status <= 0) {
            $_SESSION['message'] = $object->info;
        }
        
        switch ($object->status) {
            case 200 :
            case 207 :
            case 215 :
                $valid = true;
                break;
            default :
                $valid = false;
                break;
        }
        
        return $valid;
    }
    
    /**
     * Compile emails list for Byteplant Bulk API
     * 
     * @deprecated since version 2.0
     * @access public
     * @param array $a
     * @return string
     * @throws Exception
     */
    public function get_bulk_emails_string(array $a = null)
    {
        if (!count($a)) {
            throw new Exception('No emails supplied to compile bulk list' . "\n<br />");
        }
        
        $string = '';
        $count = 5;
        //$count = count($a);
        
        for ($i = 0; $i < $count; $i++) {
            foreach ($a[$i] as $email) {
                $string .= $email . "\n";
            }
        }
        
        return trim($string, "\n");
    }
    
    /**
     * Check for duplicated email address against database records
     * 
     * @access public
     * @param string $email
     * @return boolean
     */
    public function check_duplication($email)
    {
        $q = $this->db_query($this->dbh, $this->query[5], ['e_email' => $email]);

        if ($q['rows']) {
            return true;
        }
        else {
            return false;
        }
    }
    
    /**
     * Remove strings from source file
     * 
     * @deprecated since version 2.0
     * @access public
     * @param string $file
     * @param array $strings
     * @return integer
     * @throws Exception
     */
    public function remove_strings_from_file($file, array $strings = null)
    {
        if (!file_exists($file)) {
            throw new Exception('File not found to remove invalid emails' . "\n<br />");
        }
        
        if (!is_writable($file)) {
            throw new Exception('File is not writable' . "\n<br />");
        }
        
        return file_put_contents($file, str_replace($strings, '', file_get_contents($file)));
    }
    
    /**
     * Record processed file into server
     * 
     * @access public
     * @param string $file
     * @return integer
     */
    public function write_file_processed($file)
    {
        if (count($this->new_rows)) {
            return file_put_contents($file, implode(PHP_EOL, $this->new_rows));
        }
        else {
            return false;
        }
    }
    
    /**
     * Record source file path into database
     * 
     * @access public
     * @param string $filePath
     * @return integer
     */
    public function db_record_file($filePath)
    {
        $q = $this->db_query($this->dbh, $this->query[0], ['f_file_path' => $filePath]);
        
        if ($q['rows']) {
            return $q['data'][0]['f_id'];
        }
        
        $q = $this->db_query($this->dbh, $this->query[1], ['f_file_path' => $filePath]);
        
        return $q['lastId'];
    }
    
    /**
     * Record email addresses into database
     * 
     * @access public
     * @param array $emails
     * @param integer $fileId
     * @return void
     * @throws Exception
     */
    public function db_record_emails($fileId)
    {
        if (!$fileId) {
            return false;
        }
        
        if (!count($this->current_emails)) {
            return false;
        }
        
        $f_added_num = 0;
        
        foreach ($this->current_emails as $e) {
            if (strpos($e, '@') !== false) {
                $this->db_query($this->dbh, $this->query[2], ['e_f_id' => $fileId, 'e_email' => $e]);
                $f_added_num++;
            }
        }
        
        return $f_added_num;
    }
    
    /**
     * Show uploaded, filtered source files list
     * 
     * @access public
     * @param void
     * @return array
     */
    public function show_file_list()
    {
        $q = $this->db_query($this->dbh, $this->query[6]);
        
        if ($q['rows']) {
            return $q['data'];
        }
        
        return false;
    }
    
    /**
     * Force file for download
     * 
     * @access public
     * @param integer $id
     * @return boolean
     */
    public function download_file($id = 0)
    {
        $file = $this->check_file_by_id($id);
        
        if (!$file) {
            return false;
        }
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    }
    
    /**
     * Delete file from server and from database
     * o Delete all emails related to file from database
     * 
     * @access public
     * @param integer $id
     * @return boolean
     */
    public function delete_file($id = 0) 
    {
        $file = $this->check_file_by_id($id);
        
        if (!$file) {
            return false;
        }

        $this->db_query($this->dbh, $this->query[8], ['f_id' => $id]);
        $this->db_query($this->dbh, $this->query[9], ['e_f_id' => $id]);
        unlink($file);
    }
    
    /**
     * Check if file is existant
     * 
     * @access public
     * @param integer $id
     * @return string file path
     * @throws Exception
     */
    public function check_file_by_id($id)
    {
        if (!$id) {
            throw new Exception('Missing file ID' . "\n<br />");
        }
        
        $q = $this->db_query($this->dbh, $this->query[7], ['f_id' => $id]);
        
        if (!$q['rows']) {
            throw new Exception('File not found' . "\n<br />");
        }
        
        if (!file_exists($q['data'][0]['f_file_path'])) {
            throw new Exception('File does not exist' . "\n<br />");
        }
        
        return $q['data'][0]['f_file_path'];
    }
    
    /**
     * Record file processing log into database
     * 
     * @access public
     * @param integer $f_id
     * @param integer $f_added_num
     * @return array
     */
    public function record_file_log($f_id, $f_added_num) {
        $data = ['f_id' => $f_id,
            'f_raw_num' => $this->f_raw_num,
            'f_duplic_num' => $this->f_duplic_num,
            'f_error_num' => $this->f_empty_num,
            'f_added_num' => $f_added_num];

        return $this->db_query($this->dbh, $this->query[10], $data);
        
    }
    
    /**
     * Get squeued source csv if any
     * 
     * @access public
     * @return array
     */
    public function check_uncompleted() {
        return array_diff(scandir(SOURCE_PATH), array('..', '.'));
    }
}
?>
