<?php defined('SYSPATH') or die('No direct script access.');
/**
 * OAuth helper class
 *
 * @author      sumh <oalite@gmail.com>
 * @package     Oauth
 * @copyright   (c) 2009 OALite team
 * @license     http://www.oalite.com/license.txt
 * @version     $id$
 * @link        http://www.oalite.com
 * @since       Available since Release 1.0
 * *
 */
abstract class Oauth_Core {

    public static $headers = NULL;

    /**
     * Normalized request string for signature verify
     *
     * @access  public
     * @param   string    $method
     * @param   string    $uri
     * @param   array     $params
     * @return  string
     */
    public static function normalize($method, $uri, array $params)
    {
        // ~ The oauth_signature parameter MUST be excluded.
        unset($params['signature']);

        return $method.'&'.Oauth::urlencode($uri).'&'.Oauth::build_query($params);
    }
    
    /**
     * Oauth_Signature::factory alias
     *
     * @see     Oauth_Signature::factory
     * @access  public
     * @param   string	$method
     * @param   string	$base_string
     * @return  object
     */
    public static function signature($method, $base_string)
    {
        return Oauth_Signature::factory($method, $base_string);
    }

    /**
     * This function takes a query like a=b&a=c&d=e and returns the parsed
     *
     * @access    public
     * @param     string    $query
     * @return    array
     */
    public static function parse_query($query = NULL, $args = NULL)
    {
        $params = array();

        if($query === NULL) $query = ltrim(URL::query(), '?');

        if( ! empty($query))
        {
            $query = explode('&', $query);

            foreach ($query as $param)
            {
                list($key, $value) = explode('=', $param, 2);
                $params[Oauth::urldecode($key)] = $value !== NULL ? Oauth::urldecode($value) : '';
            }
        }

        return $args === NULL ? $params : (isset($params[$args]) ? $params[$args] : NULL);
    }
    
    /**
     * Build HTTP Query
     *
     * @access  public
     * @param   arra    $params
     * @return  string  HTTP query
     */
    public static function build_query(array $params)
    {
        if (empty($params)) return '';

        $query = '';
        foreach ($params as $key => $value)
        {
            $query .= Oauth::urlencode($key).'='.Oauth::urlencode($value).'&';
        }

        return rtrim($query, '&');
    }
    
    /**
     * Explode the oauth parameter from $_POST and returns the parsed
     *
     * @access  public
     * @param   string  $query
     * @return  array
     */
    public static function parse_post($post)
    {
        $params = array();

        if (! empty($post))
        {
            if(isset(self::$headers['Content-Type'])
                AND stripos(self::$headers['Content-Type'], 'application/x-www-form-urlencoded') !== FALSE)
            {
                //
            }
        }

        return $params;
    }

    /**
     * helper to try to sort out headers for people who aren't running apache
     *
     * @access  public
     * @return  void
     */
    public static function request_headers()
    {
        if(self::$headers !== NULL) return self::$headers;

        $headers = array();
        if (function_exists('apache_request_headers'))
        {
            foreach(apache_request_headers() as $key => $value)
            {
                $headers[ucwords(strtolower($key))] = $value;
            }
        }

        foreach ($_SERVER as $key => $value)
        {
            if (substr($key, 0, 5) === 'HTTP_')
            {
                $key = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[ucwords($key)] = $value;
            }
        }

        return self::$headers = $headers;
    }

    /**
     * Utility function for turning the Authorization: header into parameters
     * has to do some unescaping
     * Can filter out any non-oauth parameters if needed (default behaviour)
     *
     * @access  public
     * @param   string    $headers
     * @param   string    $oauth_only    default [ TRUE ]
     * @return  array
     */
    public static function parse_header($headers)
    {
        $pattern = '/(([-_a-z]*)=("([^"]*)"|([^,]*)),?)/';
        $offset = 0;
        $params = array();
        if (isset($headers['Authorization']) && substr($headers['Authorization'], 0, 12) === 'Token token=')
        {
            $this->_params = Oauth::parse_header($headers['Authorization']) + $this->_params;
        }
        while (preg_match($pattern, $headers, $matches, PREG_OFFSET_CAPTURE, $offset) > 0)
        {
            $match = $matches[0];
            $header_name = $matches[2][0];
            $header_content = (isset($matches[5])) ? $matches[5][0] : $matches[4][0];
            $params[$header_name] = Oauth::urldecode($header_content);
            $offset = $match[1] + strlen($match[0]);
        }

        if (isset($params['realm']))
        {
            unset($params['realm']);
        }

        return $params;
    }

    public static function build_header(array $params, $realm = '')
    {
        $header ='Authorization: Token token="'.$realm.'"';
        foreach ($params as $key => $value)
        {
            if (is_array($value))
            {
                throw new OAuth_Exception('Arrays not supported in headers');
            }
            $header .= ','.Oauth::urlencode($key).'="'.Oauth::urlencode($value).'"';
        }
        return $header;
    }
    
    /**
     * URL Decode
     *
     * @param   mixed   $item Item to url decode
     * @return  string  URL decoded string
     */
    public static function urldecode($item)
    {
        if (is_array($item))
        {
            return array_map(array('Oauth', 'urldecode'), $item);
        }

        return rawurldecode($item);
    }

    /**
     * URL Encode
     *
     * @param   mixed $item string or array of items to url encode
     * @return  mixed url encoded string or array of strings
     */
    public static function urlencode($item)
    {
        static $search = array('+', '%7E');
        static $replace = array(' ', '~');

        if (is_array($item))
        {
            return array_map(array('Oauth', 'urlencode'), $item);
        }

        if (is_scalar($item) === FALSE)
        {
            return $item;
        }

        return str_replace($search, $replace, rawurlencode($item));
    }
    
    private function __construct()
    {
        // This is a static class
    }

} //END Oauth
