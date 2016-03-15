<?php

/**
 * Class controller Router
 * o Route script regarding URI and HTTP request
 * o Dispatch methods
 * 
 * @extends PDO MySQL interface
 * @author Alex Kaydansky <kaydansky@gmail.com>
 * @package DomainFiltering Dashboard software
 * @version 1.0
 * @since 2014/11/12
 */
class Router extends Model_Mysql
{
    /**
     * Main HTML template filename
     * 
     * @access private
     * @var string
     */
    private $templateMain = 'index.html';
    
    /**
     * UI Settings HTML template filename
     * 
     * @access private
     * @var string
     */
    private $templateUiSettings = 'ui_settings.html';
    
    /**
     * File List HTML template filename
     * 
     * @access private
     * @var string
     */
    private $templateFileList = 'file_list.html';
    
    /**
     * File Rows HTML template filename
     * 
     * @access private
     * @var string
     */
    private $templateFileRows = 'file_rows.html';
    
    /**
     * Domain List HTML template filename
     * 
     * @access private
     * @var string
     */
    private $templateDomainList = 'domain_list.html';

    /**
     * Domian Rows HTML template filename
     * 
     * @access private
     * @var string
     */
    private $templateDomainRows = 'domain_rows.html';
    
    /**
     * Files in progress list HTML template filename
     * 
     * @access private
     * @var string
     */
    private $templateRestList  = 'rest_list.html';
    
    /**
     * Files in progress box HTML template filename
     * 
     * @access private
     * @var string
     */
    private $templateRestRows = 'rest_rows.html';

    /**
     * Class constructor
     * o Dispatch action depending on URI and HTTP request
     * o Include necessary models/controllers files
     * o Define default internal content
     * o Echo messages if any
     * o Establish datbase connection
     * 
     * @param void
     */
    public function __construct()
    {
        switch (strtolower($_SERVER['REQUEST_URI'])) {
            default:
                
                $fileList = null;
                $fileRows = null;
                
                // Show message if any
                if(isset($_SESSION['message'])) {
                    echo '<center>' . $_SESSION['message'] . '</center><hr />';
                    unset($_SESSION['message']);
                } 

                // Load necessary models/controllers files
                require_once(MODELS_PATH . 'model_similarweb_api.php');
                require_once(CONTROLLERS_PATH . 'controller_file_handler.php');
                require_once(CONTROLLERS_PATH . 'controller_wrapper.php');

                // Establish datbase connection
                $dbh = $this->db_connect();
                $file = new File_Handler($dbh);
                
                // Cron job
                if (empty($_SERVER['HTTP_HOST'])) {
                    $restFiles = $file->check_uncompleted();

                    if (count($restFiles)) {
                        $sourcePath = SOURCE_PATH . '/' . array_values($restFiles)[0];                        
                        $this->process_source_csv($sourcePath, $file);
                        return;
                    }
                    else {
                        exit;
                    }
                }

                if ($_FILES && is_uploaded_file($_FILES['csv']['tmp_name'])) {
                    // Move source csv to server
                    try {
                        $sourcePath = $file->move_file($_FILES);
                    }
                    catch (Exception $e) {
                        $_SESSION['message'] = $e->getMessage();
                        header('location: ' . DOMAIN_ROOT);
                        exit;
                    }
                    
                    $this->process_source_csv($sourcePath, $file);
                }
                
                // Make file download
                $download = filter_input(INPUT_GET, 'download', FILTER_SANITIZE_NUMBER_INT);
                
                if (isset($download)) {
                    try {
                        $file->download_file($download);
                    }
                    catch (Exception $e) {
                        $_SESSION['message'] = $e->getMessage();
                        header('location: ' . DOMAIN_ROOT);
                        exit;
                    }
                    
                    exit;
                }
                
                // Delete file
                $delete = filter_input(INPUT_GET, 'delete', FILTER_SANITIZE_NUMBER_INT);

                if (isset($delete)) {
                    try {
                        $file->delete_file($delete);
                        header('location: ' . DOMAIN_ROOT);
                        exit;
                    }
                    catch (Exception $e) {
                        $_SESSION['message'] = $e->getMessage();
                        header('location: ' . DOMAIN_ROOT);
                        exit;
                    }
                }               
                    
                // Check/show uncompleted files:
                $restFiles = $file->check_uncompleted();
                $restFilesNum = count($restFiles);
                $restRows = '';
                
                if ($restFilesNum) {
                    foreach ($restFiles as $f) {
                        $a['{REST_FILE_NAME}'] = $f;
                        $restRows .= Wrapper::wrap($this->templateRestRows, $a, false);
                    }
                    
                    $restList = Wrapper::wrap($this->templateRestList, array('{REST_ROWS}' => $restRows), false);
                }
                else {
                    $restList = '';
                }
                
                // Compile File Upload form
                $content = Wrapper::wrap($this->templateUiSettings, array(), false);
                
                $file_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                
                if (isset($file_id)) {
                    // Compile Domain List
                    $domainListData = $file->show_domian_list($file_id);
                    $domainRows = '';

                    if ($domainListData) {
                        $i = 0;

                        foreach ($domainListData as $d) {
                            $i++;
                            $a['{ESTIMATED_VISITS}'] = $d['d_estimated_visits'] > 0 ? number_format($d['d_estimated_visits'] + 0) : 'Not Available';
                            $a['{DOMAIN}'] = $d['d_domain'];
                            $a['{GLOBAL_RANK}'] = floatval($d['d_global_rank']) > 0 ? $d['d_global_rank'] + 0 : 'Not Available';
                            $a['{VISIT_DURATION}'] = $d['d_visit_duration'];
                            $a['{PAGES_VISIT}'] = $d['d_pages_visit'];
                            $a['{BOUNCE_RATE}'] = $d['d_bounce_rate'];
                            $a['{row_class}'] = is_int($i / 2) ? 'even' : 'odd';
                            $domainRows .= Wrapper::wrap($this->templateDomainRows, $a, false);
                        }

                        $fileList = Wrapper::wrap($this->templateDomainList, array('{DOMAIN_ROWS}' => $domainRows), false);
                    }
                    else {
                        $fileList = '<center>No domain found</center>';
                    }
                }
                else {
                    // Compile File List
                    $fileListData = $file->show_file_list();

                    if ($fileListData) {
                        $i = 0;

                        foreach ($fileListData as $r) {
                            $i++;
                            $filePath = explode('/', $r['f_file_path']);
                            $a['{FILE_NAME}'] = end($filePath);
                            $a['{FILE_DATE}'] = date_format(date_create($r['f_updated']), 'F d, Y H:i:s');
                            $a['{RAW_NUM}'] = $r['f_raw_num'];
                            $a['{DUPLIC_NUM}'] = $r['f_duplic_num'];
                            $a['{ERROR_NUM}'] = $r['f_error_num'];
                            $a['{ADDED_NUM}'] = $r['f_added_num'];
                            $a['{BOUNCE_RATE_OUT}'] = $r['f_bounce_rate_out'];
                            $a['{PAGES_VISIT_OUT}'] = $r['f_pages_visit_out'];
                            $a['{VISIT_DURATION_OUT}'] = $r['f_visit_duration_out'];
                            $a['{KEYWORDS_OUT}'] = $r['f_keywords_out'];
                            $a['{ID}'] = $r['f_id'];
                            $a['{row_class}'] = is_int($i / 2) ? 'even' : 'odd';
                            $fileRows .= Wrapper::wrap($this->templateFileRows, $a, false);
                        }

                        $fileList = Wrapper::wrap($this->templateFileList, array('{FILE_ROWS}' => $fileRows), false);
                    }
                    else {
                        $fileList = '<center>No file found</center>';
                    }
                }
                
                // Show compiled HTML page
                Wrapper::wrap($this->templateMain, 
                        array('{CONTENT}' => $content,
                            '{FILE_LIST}' => $fileList,
                            '{REST_LIST}' => $restList));

                break;
        }

        return;
    }
    
    private function process_source_csv($sourcePath, $file) {
        $f_added_num = 0;
        
        // Convert source csv into array
        try {
            $sourceArray = $file->explode_source($sourcePath);
        }
        catch (Exception $e) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $_SESSION['message'] = $e->getMessage();
                header('location: ' . DOMAIN_ROOT);
            }
            else {
                echo $e->getMessage();
            }
            
            exit;
        }
        
        $filePath = FILES_PATH . '/' . $file->file_name;
        $fileId = $file->db_record_file($filePath);

        // Filter source file
        try {
            $numRest = $file->parse_csv($sourceArray);
        } 
        catch (Exception $e) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $_SESSION['message'] = $e->getMessage();
                header('location: ' . DOMAIN_ROOT);
            }
            else {
                echo $e->getMessage();
            }
            
            exit;
        }

        $file->record_file_log($fileId);

        if ($numRest) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $_SESSION['message'] = 'File has been partialy processed. Enqueued processing.' . "\n<br />";
                header('location: ' . DOMAIN_ROOT);
            }
            
            exit;
        }

        if (!count($file->checked_domains)) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                $_SESSION['message'] = 'No valid domains found' . "\n<br />";
                header('location: ' . DOMAIN_ROOT);
            }
            else {
                echo 'No valid domains found' . "\n<br />";
            }
            
            exit;
        }

        // Record processed file into server
        if (!$numRest) {
            if ($fileId) {
                $f_added_num = $file->db_record_domains($filePath, $fileId);
                
                $file->record_file_log($fileId, $f_added_num);
                
                if (!empty($_SERVER['HTTP_HOST'])) {
                    $_SESSION['message'] .= 'File ' . $file->file_name 
                            . ' has been processed successfuly.<br>' . 
                            $f_added_num . ' domains were added to database.';
                
                    header('location: ' . DOMAIN_ROOT);
                    exit;
                }
                else {
                    echo 'complete';
                    exit;
                }
            }
            else {
                if (!empty($_SERVER['HTTP_HOST'])) {
                    $_SESSION['message'] = 'File ID not found in database' . "\n<br />";
                    header('location: ' . DOMAIN_ROOT);
                }
                else {
                    echo 'File ID not found in database';
                }

                exit;
            }
        }
        
        return;
    }
}
?>
