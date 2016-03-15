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
 * @package DomainFiltering Dashboard software
 * @version 1.0
 * @since 2014/11/12
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
     * Unique domains extracted from source file
     * 
     * @access public
     * @var array
     */
    public $unique_domains = array();
    
    /**
     * Checked domains extracted from source file
     * 
     * @access public
     * @var array
     */
    public $checked_domains = array();
    
    /**
     * File name
     * 
     * @access public
     * @var string
     */
    public $file_name = '';
    
    /**
     * Script execution time count start flag
     * 
     * @access public
     * @var float
     */
    public $start = 0;
    
    /**
     * Path to source csv file
     * 
     * @access public
     * @var string
     */
    public $sourcePath = '';
    
    public $f_bounce_rate_out = 0;
    
    public $f_pages_visit_out = 0;
    
    public $f_visit_duration_out = 0;
    
    public $f_keywords_out = 0;

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
        $this->Model_API = new Similarweb_API();
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
        
        if (($handle = fopen($file, 'r')) !== false) {
            while (($line = fgetcsv($handle, 1000, ',')) !== false) {
                $raw_rows[] = $line;
            }
        }
        
        $this->f_raw_num = count($raw_rows) - 1;
        $fileName = explode('/', $file);
        $this->file_name = end($fileName);
        
        return $raw_rows;
    }

    /**
     * Extract domains from source file
     * 
     * @access public
     * @param array $array
     * @return integer
     * @throws Exception
     */
    public function parse_csv(array $array)
    {
        if (!count($array)) {
            throw new Exception('CSV array is not provided' . "\n<br />");
        }
        
        $rawUniqueDomains = array();
        $targetPath = FILES_PATH . '/' . $this->file_name;
        
        $firstLine = array_shift($array);
        array_unshift($firstLine, 'Estimated Visits');
        file_put_contents($targetPath, implode(',', $firstLine) . PHP_EOL, FILE_APPEND);

        foreach ($array as $k => $line) {
            if (empty($line[0])) {
                $this->f_empty_num++;
                unset($array[$k]);
                continue;
            }

            if (in_array($line[0], $rawUniqueDomains)) {
                $this->f_duplic_num++;
                unset($array[$k]);
                continue;
            }

            $rawUniqueDomains[] = $line[0];

            if ($this->check_duplication($line[0])) {
                $this->f_duplic_num++;
                unset($array[$k]);
                continue;
            }

            //$this->unique_domains[] = $line[0];

            if ($this->filter_bounce_rate($line[6])) {
                $this->f_bounce_rate_out++;
                unset($array[$k]);
                continue;
            }

            if ($this->filter_pages_visit($line[5])) {
                $this->f_pages_visit_out++;
                unset($array[$k]);
                continue;
            }

            if ($this->filter_avg_visit_duration($line[4])) {
                $this->f_visit_duration_out++;
                unset($array[$k]);
                continue;
            }

            if (strpos($line[0], '.') !== false) {
                if (!$this->api_check_keywords($line[0])) {
                    $this->f_keywords_out++;
                    unset($array[$k]);
                    continue;
                }
            }
            
            $estimatedVisits = $this->api_estimated_visits($line[0]) ?: 0;
            array_unshift($line, $estimatedVisits);
            
            $line[3] = str_replace(',', '.', $line[3]);
            $this->checked_domains[] = $line;
            $line = $this->add_quotes_to_arr($line);
            unset($array[$k]);
            file_put_contents($targetPath, implode(',', $line) . PHP_EOL, FILE_APPEND);
            
            if ((microtime(true) - $this->start) >= 25) {
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
     * Get search keywords and check for matching domain name in it
     * 
     * @access public
     * @uses model Similarweb_API
     * @param string $domain
     * @return integer|boolean
     */
    public function api_check_keywords($domain)
    {   
        //return 1;
        
        $match = 0;
        $result = $this->Model_API->call_api($domain, 'orgsearch');
        
        if (!is_object($result)) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $_SESSION['message'] .= 'API is not responding';
            }
            else {
                echo 'API is not responding' . "<br>\n";
            }
            
            return true;
        }
        
        if (isset($result->Error)) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $_SESSION['message'] .= 'API error: ' . $result->Error->Message . "<br>\n";
            }
            else {
                echo 'API error: ' . $result->Error->Message . "\n";
            }
            
            return true;
        }
        
        if (!isset($result->Data)) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $_SESSION['message'] .= 'API returned no result for: ' . $domain . "<br>\n";
            }
            else {
                echo 'API returned no result for: '  . $domain . "\n";
            }
            
            return true;
        }
        
        $domainName = explode('.', $domain);
        $count = 3;
        //$count = count($result->Data);
        
        for ($i = 0; $i < $count; $i++) {
            if (strpos($result->Data[$i]->SearchTerm, $domainName[0]) !== false) {
                $match++;
            }
        }

        if (!$match) {
            return false;
        }
        else {
            return $match;
        }
    }
    
    /**
     * Get estimated traffic per domain
     * 
     * @access public
     * @uses model Similarweb_API
     * @param string $domain
     * @return float
     */
    public function api_estimated_visits($domain)
    {   
        //return 1;
        
        $result = $this->Model_API->call_api($domain, 'visits');
        
        if (!is_object($result)) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $_SESSION['message'] .= 'API - visits - is not responding';
            }
            else {
                echo 'API - visits - is not responding' . "<br>\n";
            }
            
            return false;
        }
        
        if (isset($result->Error)) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $_SESSION['message'] .= 'API error: ' . $result->Error->Message . "<br>\n";
            }
            else {
                echo 'API error: ' . $result->Error->Message . "\n";
            }
            
            return false;
        }
        
        if (!isset($result->Values[1])) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $_SESSION['message'] .= 'API - visits - returned no result for: ' . $domain . "<br>\n";
            }
            else {
                echo 'API - visits - returned no result for: '  . $domain . "\n";
            }
            
            return false;
        }
        
        return $result->Values[1]->Value;
    }
    
    /**
     * Check for duplicated email address against database records
     * 
     * @access public
     * @param string $email
     * @return boolean
     */
    public function check_duplication($domain)
    {
        $q = $this->db_query($this->dbh, $this->query[5], ['d_domain' => $domain]);

        if ($q['rows']) {
            return true;
        }
        else {
            return false;
        }
    }
    
    /**
     * Check for bounce rate to be under 50%
     * 
     * @access public
     * @param string $param
     * @return boolean
     */
    public function filter_bounce_rate($param) 
    {
        $val = intval(trim($param, '%'));
        
        if ($val > 0) {
            if ($val > 50) {
                return true;
            }
            else {
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Check for pages/visit to be over 3
     * 
     * @access public
     * @param string $param
     * @return boolean
     */
    public function filter_pages_visit($param) 
    {
        $val = floatval($param);
        
        if ($val > 0) {
            if ($val <= 2.5) {
                return true;
            }
            else {
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Check for average visit duration to be over 3 min
     * 
     * @access public
     * @param string $param
     * @return boolean
     */
    public function filter_avg_visit_duration($param) 
    {
        $t = explode(':', $param);
        
        /*
        echo $param;
        echo '<br>';
        print_r($t);
        echo '<br>';
         * 
         */
        
        if (isset($t[0]) && isset($t[1]) && isset($t[2])) {
            $hrs = intval($t[0]);
            $min = intval($t[1]);
            $sec = intval($t[2]);
            
            $to_time = strtotime($hrs . ':' . $min . ':' . $sec);
            $from_time = strtotime('00:00:00');
            $time = round(abs($to_time - $from_time) / 60,2);
            
            if ($time <= 2.5) {
                return true;
            }
            
            return false;
            
            /*
            if ($hrs > 0) {
                return false;
            }
            
            if ($min >= 3) {
                return false;
            }
            
            if ($min >= 2 && $sec > 30) {
                return false;
            }
            else {
                return true;
            }
             * 
             */
        }
        
        return true;
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
        
        $q = $this->db_query($this->dbh, $this->query[1], ['f_file_path' => $filePath, 'f_raw_num' => $this->f_raw_num]);
        
        return $q['lastId'];
    }
    
    /**
     * Record checked domains into database
     * 
     * @access public
     * @param string $domains
     * @param integer $fileId
     * @return void
     * @throws Exception
     */
    public function db_record_domains($filePath, $fileId)
    {
        //ini_set('auto_detect_line_endings', true);
        $f_added_num = 0;
        $recordedDomains = array();
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            //$k = 0;
            
            while (($d = fgetcsv($handle, 1000, ',')) !== false) {
                //$k++;
                
                //if ($k == 1) {
                  //  continue;
                //}
                
                if (strpos($d[1], '.') === false) {
                    continue;
                }
                
                if (in_array($d[1], $recordedDomains)) {
                    if (empty($_SERVER['HTTP_HOST'])) {
                        echo $d[1] . "\n";
                    }
                    
                    continue;
                }

                $recordedDomains[] = $d[1];
                    
                $data = ['d_f_id' => $fileId, 
                    'd_domain' => $d[1],
                    'd_global_rank' => $d[4],
                    'd_visit_duration' => $d[5],
                    'd_pages_visit' => $d[6],
                    'd_bounce_rate' => trim($d[7], '%'),
                    'd_estimated_visits' => $d[0]];
                $this->db_query($this->dbh, $this->query[2], $data);
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
    
    public function show_domian_list($fileId) 
    {
        $q = $this->db_query($this->dbh, $this->query[11], ['d_f_id' => $fileId]);
        
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
        $this->db_query($this->dbh, $this->query[9], ['d_f_id' => $id]);
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
    public function record_file_log($f_id, $f_added_num = false) {
        if ($f_added_num) {
            $data = ['f_id' => $f_id, 'f_added_num' => $f_added_num];

            return $this->db_query($this->dbh, $this->query[12], $data);
        }
        else {
            $data = ['f_id' => $f_id, 
                'f_duplic_num' => $this->f_duplic_num, 
                'f_error_num' => $this->f_empty_num,
                'f_bounce_rate_out' => $this->f_bounce_rate_out,
                'f_pages_visit_out' => $this->f_pages_visit_out,
                'f_visit_duration_out' => $this->f_visit_duration_out,
                'f_keywords_out' => $this->f_keywords_out];
            
            return $this->db_query($this->dbh, $this->query[10], $data);
        }
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
