<?php

/**
 * Class model Similarweb API
 * o Get stats for given domain from Similarweb API
 * 
 * @author Alex Kaydansky <kaydansky@gmail.com>
 * @package DomainFiltering Dashboard software
 * @version 1.0
 * @since 2014/11/12
 * @uses verify-email.org API
 */
class Similarweb_API
{
    /**
     * Similarweb API url
     * 
     * @access protected
     * @var string
     */
    protected $api_url;
    
    /**
     * Similarweb API key
     * 
     * @access protected
     * @var string 
     */
    protected $api_key;

    /**
     * Similarweb_API constructor
     * o Load SQL query templates list
     * o Implement database handle
     * 
     * @access public
     * @param void
     */
    public function __construct()
    {
        $this->api_key = API_USERKEY;
        $this->api_url = API_URL;
        
        return;
    }

    /**
     * Call Similarweb API
     * o Compile API url
     * 
     * @access public
     * @uses Similarweb API
     * @param string $domain
     * @return object
     */
    public function call_api($domain, $endpoint)
    {           
        $current = new DateTime();
        $interval1 = new DateInterval('P1M');
        $u = $current->sub($interval1);
        $until = $u->format('n-Y');
        $interval2 = new DateInterval('P1M');
        $f = $current->sub($interval2);
        $from = $f->format('n-Y');       

        $url = $this->api_url . $domain . '/v1/' . $endpoint . '?start=' . $from . '&end=' . $until . '&md=false&Format=JSON&UserKey=' . $this->api_key;

        return json_decode($this->remote_get_contents($url));
    }
    
    /**
     * Get remote content
     * 
     * @access public
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
