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

/**
 * Changes in this lib for XMLNuke support
 * João Gilberto Magalhães
 */


/**
 * Base OAuth class
 */
abstract class baseOAuth {/*{{{*/
  /* Contains the last HTTP status code returned */
  private $http_status;

  /* Contains the last API call */
  private $last_api_call;

  /**
   * Set API URLS
   */
  abstract function requestTokenURL();
  abstract function authorizeURL();
  abstract function accessTokenURL();

  /**
   * It is a good idea to implement this also
   */
  function validateRequest($result)
  {
	$statusCodes = array(
		"200" => "",
		"304" => "Not Modified",
		"400" => "Bad Request",
		"401" => "Unauthorized",
		"403" => "Forbidden",
		"404" => "Not Found",
		"406" => "Not Acceptable",
		"420" => "Enhance Your Calm",
		"500" => "Internal Server Error",
		"502" => "Bad Gateway",
		"503" => "Service Unavailable"
	);
	  
	if (array_key_exists($this->lastStatusCode(), $statusCodes))
		return $this->lastStatusCode() . " " . $statusCodes[$this->lastStatusCode()];
	else {
		return $this->lastStatusCode() . " Unknow";
	}	
  }

  
  
  /**
   * Debug helpers
   */
  function lastStatusCode() { return $this->http_status; }
  function lastAPICall() { return $this->last_api_call; }

  /**
   * construct baseOAuth object
   */
  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {/*{{{*/
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }/*}}}*/


  /**
   * Get a request_token from OAuth server
   *
   * @returns a key/value array containing oauth_token and oauth_token_secret
   */
  function getRequestToken($args = array()) {/*{{{*/
    $r = $this->oAuthRequest($this->requestTokenURL(), $args);
    $token = $this->oAuthParseResponse($r);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }/*}}}*/

  /**
   * Parse a URL-encoded OAuth response
   *
   * @return a key/value array
   */
  function oAuthParseResponse($responseString) {
    $r = array();
    foreach (explode('&', $responseString) as $param) {
      $pair = explode('=', $param, 2);
      if (count($pair) != 2) continue;
      $r[urldecode($pair[0])] = urldecode($pair[1]);
    }
    return $r;
  }

  /**
   * Get the authorize URL
   *
   * @returns a string
   */
  function getAuthorizeURL($token) {/*{{{*/
    if (is_array($token)) $token = $token['oauth_token'];
    return $this->authorizeURL() . '?oauth_token=' . $token;
  }/*}}}*/

  /**
   * Exchange the request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @returns array("oauth_token" => the access token,
   *                "oauth_token_secret" => the access secret)
   */
  function getAccessToken($args = array()) {/*{{{*/
    $r = $this->oAuthRequest($this->accessTokenURL(), $args);
    //var_dump($r);
    $token = $this->oAuthParseResponse($r);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }/*}}}*/

  /**
   * Format and sign an OAuth / API request
   */
  function oAuthRequest($url, $args = array(), $method = NULL) {/*{{{*/
    if (empty($method)) $method = empty($args) ? "GET" : "POST";
    $req = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $args);
    $req->sign_request($this->sha1_method, $this->consumer, $this->token);
	
    switch ($method) {
    case 'GET': $result = $this->http($req->to_url());
    case 'POST': $result = $this->http($req->get_normalized_http_url(), $req->to_postdata());
    }
	
	$this->validateRequest($result);

	return $result;
  }/*}}}*/

  /**
   * Make an HTTP request
   *
   * @return API results
   */
  function http($url, $post_data = null) {/*{{{*/
    $ch = curl_init();
    if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //////////////////////////////////////////////////
    ///// Set to 1 to verify Server's SSL Cert //////
    //////////////////////////////////////////////////
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    if (isset($post_data)) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }

    $response = curl_exec($ch);
    $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $this->last_api_call = $url;
    curl_close ($ch);

	/**
    echo "<pre>";
    echo $url . "\n";
    print_r(explode("&", $post_data)) . "\n";
    print_r($response) . "\n";
    echo "---\n</pre>";
	 */

    return $response;
  }/*}}}*/
}/*}}}*/