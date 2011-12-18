<?php defined('SYSPATH') or die('No direct script access.');
/**
 * OAuth Exception
 *
 * @author      sumh <oalite@gmail.com>
 * @package     Oauthz
 * @copyright   (c) 2010 OALite
 * @license     ISC License (ISCL)
 * @link        http://oalite.com
 * *
 */
class Oauthz_Exception extends Exception {

	/**
	 * REQUIRED.  A single error code
	 *
	 * @access	protected
	 * @var		string	$error
	 */
    protected $error;

	/**
	 * OPTIONAL.  A human-readable text providing additional information,
	 *   used to assist in the understanding and resolution of the error occurred.
	 *
	 * @access	protected
	 * @var		string	$error_description
	 */
    protected $error_description;

	/**
	 * OPTIONAL.  A URI identifying a human-readable web page with
     *   information about the error, used to provide the resource owner
     *   with additional information about the error.
	 *
	 * @access	public
	 * @var		string	$error_uri
	 */
    protected $error_uri;

	/**
	 * Initial OAuth error codes from config settings
	 *
	 * @access	public
	 * @param	string	$message
	 * @param	string	$state	extras parameters
	 * @param	string	$code	default [ 0 ]
	 * @return	void
	 */
	public function __construct($message, array $state = NULL, $code = 0)
	{
        $this->error        = $message;
        $this->state        = (array) $state;

		// Pass the message to the parent
		parent::__construct($message, $code);
	}

	public function as_json()
	{
        $params = array('error' => $this->error) + $this->state;

        if(isset($params['error_uri']))
        {
            $params['error_uri'] = url::site($params['error_uri'], TRUE);
        }
        else
        {
            $params['error_uri'] = url::site(Oauthz::config('error_uri'), TRUE).'/'.$this->error;
        }

        if(isset($params['error_description']))
        {
            $params['error_description'] = __($params['error_description']);
        }
        else
        {
            $params['error_description'] = $this->error_description;
        }

        return json_encode(array_filter($params));
	}

	public function as_query()
	{
        if(isset($this->state['error_uri']))
        {
            $params = array('error' => $this->error) + $this->state;

            if(isset($params['error_description']))
            {
                $params['error_description'] = __($params['error_description']);
            }
            else
            {
                $params['error_description'] = $this->error_description;
            }

            $error_uri = url::site($params['error_uri'], TRUE);

            // don't append error_uri to querystring
            unset($params['error_uri']);

            $error_uri .= '?'.http_build_query(array_filter($params), '', '&');
        }
        else
        {
            // no need to expose error, error_description
            $error_uri = url::site(Oauthz::config('error_uri'), TRUE).'/'.$this->error;
        }

        return $error_uri;
	}

} // END OAuth_Exception
