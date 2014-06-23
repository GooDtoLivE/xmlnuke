<?php
/*
 * Abraham Williams (abraham@abrah.am) http://abrah.am
 *
 * Basic lib to work with Twitter's OAuth beta. This is untested and should not
 * be used in production code. Twitter's beta could change at anytime.
 *
 * Code based on:
 * Fire Eagle code - http://github.com/myelin/fireeagle-php-lib
 * twitterlibphp - http://github.com/poseurtech/twitterlibphp
 */

/* Load OAuth lib. You can find it at http://oauth.net */

namespace OAuthClient\v10;

use Exception;

/**
 * Twitter OAuth class
 */
class TwitterOAuth extends BaseOAuth {/*{{{*/
	
	/* Set up the API root URL */
	public static $TO_API_ROOT = "https://twitter.com";

	/**
	* Set API URLS
	*/
	function requestTokenURL() { return self::$TO_API_ROOT.'/oauth/request_token'; }
	function authorizeURL() { return self::$TO_API_ROOT.'/oauth/authorize'; }
	function accessTokenURL() { return self::$TO_API_ROOT.'/oauth/access_token'; }

	function validateRequest($result) 
	{
		$status = trim(parent::validateRequest($result));
		
		if ($status != "200")
		{
			$obj = json_decode($result);
			throw new Exception($status . ": " . ($obj->error != "" ? $obj->error : $result));
		}
	}
	
	public function getData($data, $params = null)
	{
		return json_decode($this->OAuthRequest(self::$TO_API_ROOT . $data . ".json", ($params == null ? array() : $params), 'GET'));
	}
	
	public function publishData($data, $params = null)
	{
		return json_decode($this->OAuthRequest(self::$TO_API_ROOT . $data . ".json", ($params == null ? array() : $params), 'POST'));
	}
	
}/*}}}*/