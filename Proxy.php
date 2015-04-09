<?php

class Proxy
{
    private $cookiePath = '/_session/';
    private $cachePath = '/_cache/';
    private $requestTimeout = 5; // in secons
    private $cookieEnabled = true;

    // hook fn
    private $_requestHook = null;
    private $_responseHook = null;

    public function __construct() {
        ob_start("ob_gzhandler");
        session_start();
    }

    public function setRequestHook($fnct) {
        $this->_requestHook = $fnct;
    }

    public function setResponseHook($fnct) {
        $this->_responseHook = $fnct;
    }

    private function _getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    private function _getCookieFile() {
    	$cookiePath = realpath(dirname(__FILE__)) . $this->cookiePath;
    	if(isset($_SESSION['cookieFile']) && file_exists($_SESSION['cookieFile'])){
    		$cookieFile = $_SESSION['cookieFile'];
    	} else {
    		$cookieFile = $cookiePath . md5(time() . rand(0, 1000000)) . '.txt';
    		$_SESSION['cookieFile'] = $cookieFile;
    	}
        return $cookieFile;
    }

    private function _packHeaders($headers) {
        $res = [];
        foreach($headers as $key => $value) {
            array_push($res, $key . ': ' . $value);
        }
        return $res;
    }

    private function _parseHeadersFromCurl($headerText) {
	    $headers = [];
	    foreach (explode("\r\n", $headerText) as $i => $line) {
            @list($key, $value) = explode(': ', $line);
            if($i === 0 || !$value) continue;
            if($key == 'Content-Length' || $key == 'Set-Cookie' || $key == 'Transfer-Encoding') continue;
            $headers[$key] = $value;
        }
	    return $headers;
	}

    private function _render($headers, $body) {
        $headers = $this->_packHeaders($headers);
        foreach($headers as $item) header($item);
        echo($body);
    }

    public function render($url) {

        $requestHeader = [];
        $requestBody = '';

        // Call request hook
        if ($this->_requestHook) $this->_requestHook->__invoke($requestHeader, $requestBody);

    	// Generate request
    	$ch = curl_init();

        // Turn On Cookies
        if ($this->cookieEnabled) {
            $cookieFile = $this->_getCookieFile();
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        }
        // Setup curl
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_HEADER, 1);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
    	curl_setopt ($ch, CURLOPT_USERAGENT, $this->_getUserAgent());
    	if(count($_POST) > 0) {
    		curl_setopt($ch, CURLOPT_POST, 1);
    		@curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
    	}
        // Set custom headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_packHeaders($requestHeader));
    	$response = curl_exec($ch);

    	$responseHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

    	$responseBody = mb_substr($response, $responseHeaderSize);
    	$responseHeaders = $this->_parseHeadersFromCurl(mb_substr($response, 0, $responseHeaderSize));

        // Call response hook
        if ($this->_responseHook) $this->_responseHook->__invoke($responseHeaders, $responseBody);

        return $this->_render($responseHeaders, $responseBody);

    }

}
