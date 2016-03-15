<?php

/**
 * Class controller Router
 * o Route script regarding URI and HTTP request
 * o Dispatch methods
 * 
 * @extends PDO MySQL interface
 * @author Alex Kaydansky <kaydansky@gmail.com>
 * @package EmailFiltering Dashboard software
 * @version 2.0
 * @since 2014/11/02
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
                $valid = null;
                $invalid = null;
                $fileList = null;
                $fileRows = null;
                $fileId = 0;
                $f_added_num = 0;
                
                // Show message if any
                if(isset($_SESSION['message'])) {
                    echo '<center>' . $_SESSION['message'] . '</center><hr />';
                    unset($_SESSION['message']);
                } 

                // Load necessary models/controllers files
                require_once(MODELS_PATH . 'model_verify_email_api.php');
                require_once(CONTROLLERS_PATH . 'controller_file_handler.php');
                require_once(CONTROLLERS_PATH . 'controller_wrapper.php');

                // Establish datbase connection
                $dbh = $this->db_connect();
                $file = new File_Handler($dbh);

                if ($_FILES && is_uploaded_file($_FILES['csv']['tmp_name'])) {
                    // Move source file to server
                    try {
                        $sourcePath = $file->move_file($_FILES);
                    }
                    catch (Exception $e) {
                        $_SESSION['message'] = $e->getMessage();
                        header('location: ' . DOMAIN_ROOT);
                        exit;
                    }

                    // Convert source csv into array
                    try {
                        $sourceArray = $file->explode_source($sourcePath);
                    } 
                    catch (Exception $e) {
                        echo $e->getMessage();
                        exit;
                    }
                    
                    // Extract email addresses from source file
                    try {
                        $numRest = $file->extract_emails($sourceArray);
                    } 
                    catch (Exception $e) {
                        $_SESSION['message'] = $e->getMessage();
                        header('location: ' . DOMAIN_ROOT);
                        exit;
                    }

                    // Skip API validation
                    goto end_validation;
                    
                    // Check emails using verify-email.org API
                    $valid = $file->unique_emails;
                    $limit = 30;
                    //$limit = count($uniqueAddresses);
                    
                    foreach ($uniqueAddresses as $a) {
                        if ($limit-- == 0) {
                            goto end_validation;
                        }
                        
                        $checked = false;
                        $checked = $file->verify_email_address_byteplant($a);

                        if ($checked) {
                            $valid[] = $a;
                        }
                        else {
                            $invalid[] = $a;
                        }
                    }
                    //
                    
                    end_validation:
                        
                    $filePath = FILES_PATH . '/' . $file->file_name;
                    $fileId = $file->db_record_file($filePath);
                    
                    if ($f_added_num = $file->db_record_emails($fileId)) {
                        $file->record_file_log($fileId, $f_added_num);
                    }
                    
                    if ($numRest) {
                        $_SESSION['message'] = 'File has been partialy processed. Enqueued processing.' . "\n<br />";
                        header('location: ' . DOMAIN_ROOT);
                        exit;
                    }
                    else {
                        $_SESSION['message'] = 'File has been processed successfuly<br><br>' . 
                                $file->file_name . ' was uploaded with ' .
                                $file->f_raw_num . ' emails.<br>' .
                                $file->f_duplic_num . ' duplicates found. ' .
                                $file->f_empty_num . ' lines without email. ' .
                                $f_added_num . ' were added to database.';
                        header('location: ' . DOMAIN_ROOT);
                        exit;
                    }
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
                        $a['{BUPLIC_NUM}'] = $r['f_duplic_num'];
                        $a['{ERROR_NUM}'] = $r['f_error_num'];
                        $a['{ADDED_NUM}'] = $r['f_added_num'];
                        $a['{ID}'] = $r['f_id'];
                        $a['{row_class}'] = is_int($i / 2) ? 'even' : 'odd';
                        $fileRows .= Wrapper::wrap($this->templateFileRows, $a, false);
                    }
                    
                    $fileList = Wrapper::wrap($this->templateFileList, array('{FILE_ROWS}' => $fileRows), false);
                }
                else {
                    $fileList = '<center>No file found</center>';
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
}
?>
