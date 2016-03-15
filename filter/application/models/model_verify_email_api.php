<?php

/**
 * Class model Verify Email API
 * o Verify if email address is valid using API
 * 
 * @author Alex Kaydansky <kaydansky@gmail.com>
 * @package EmailFiltering Dashboard software
 * @version 1.0
 * @since 2014/11/02
 * @uses verify-email.org API
 */
class Verify_Email_API
{

    /**
     * verify-email.org API username
     * @var striing
     */
    private $username;
    
    /**
     * verify-email.org API password
     * @var string
     */
    private $password;
    
    /**
     * verify-email.org API url
     * @var string
     */
    private $api_url;
    
    /**
     * Byteplant API key
     * @var type 
     */
    private $byplant_key;
    
    /**
     * Byteplant API url
     * @var type 
     */
    private $byplant_url;

    /**
     * Model_API_Settings constructor
     * o Load SQL query templates list
     * o Implement database handle
     * 
     * @access public
     * @param void
     */
    public function __construct()
    {
        $this->username = API_USERNAME;
        $this->password = API_PASSWORD;
        $this->api_url = API_URL;
        
        $this->byplant_key = BYTEPLANT_API_KEY;
        $this->byplant_url = BYTEPLANT_API_URL;
        
        return;
    }

    /**
     * Call verify-email.org API
     * 
     * @access public
     * @uses verify-email.org API
     * @param string $address
     * @return object
     */
    public function call_api($address)
    {
        $url = $this->api_url . 'usr=' . $this->username . '&pwd=' . $this->password . '&check=' . $address;
        return json_decode($this->remote_get_contents($url));
    }
    
    /**
     * Call Byteplant API
     * 
     * @access public
     * @uses Byplant API
     * @param string $address
     * @return object
     */
    public function call_api_Byteplant($address)
    {
        $url = $this->byplant_url . '/?APIKey=' . $this->byplant_key . '&EmailAddress=' . $address . '&Timeout=5';
        return json_decode($this->remote_get_contents($url));
    }
    
    /**
     * Get remote content
     * 
     * @access public
     * @uses verify-email.org API
     * @param string $url
     * @return string
     */
    public function remote_get_contents($url)
    {
        if (function_exists('curl_init')) {
            return $this->curl_get_contents($url);
        }
        else {
            return file_get_contents($url);
        }
    }
    
    /**
     * Make CURL request
     * 
     * @access public
     * @param string $url
     * @return string
     */
    public function curl_get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        
        return $output;
    }
}
?>
