<?php

/**
 * Calass controller Wrapper
 * Embed data into HTML template
 * 
 * @author Alex Kaydansky <kaydansky@gmail.com>
 * @package EmailFiltering Dashboard software
 * @version 1.0
 * @since 2014/11/02
 */
class Wrapper
{
    /**
     * Convert regular array to compiler ready array
     *
     * @static 
     * @param array $a
     * @return array
     */
    static function array_prepare(array $a)
    {
        if (!count($a)) {
            return false;
        }

        foreach ($a as $k => $v) {
            $result['{' . $k . '}'] = $v;
        }

        return $result;
    }

    /**
     * Replace placeholders with data in HTML template file
     * o Return or throw compiled HTML page
     * 
     * @static
     * @final
     * @param string $template
     * @param array $data
     * @param boolean $throw
     * @return string
     */
    static function wrap($template, array $data, $throw = true)
    {
        $templatePath = TEMPLATES_PATH . $template;
 
        if (!file_exists($templatePath)) {
            die('Template file "' . $template . '" not found.');
        }

        if (!$f = @fopen($templatePath, 'r')) {
            die('Failed open template file "' . $template . '".');
        }

        // Insert paths for <link rel="stylesheet"> and <script> tags of <head>
        array_push($data, array('{CSS_URI}' => CSS_URI, '{JS_URI}' => JS_URI));

        $page = fread($f, filesize($templatePath));
        fclose($f);

        if (count($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $page = str_replace($k, $v, $page);
                    }
                } 
                else {
                    $page = str_replace($key, $value, $page);
                }
            }
        }

        if ($throw) {
            echo $page;
        } 
        else {
            return $page;
        }
    }
}
?>
