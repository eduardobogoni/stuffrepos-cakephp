<?php

App::uses('HttpResponse', 'Web.Lib');

class HttpClient {

    private $agent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:11.0) Gecko/20100101 Firefox/11.0';
    private $accept = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
    private $referer = '';
    private $targetResponseDirectory = null;
    private static $targetCount = 0;
    
    /**
     *
     * @var boolean
     */
    private $followLocation = false;

    /**
     *
     * @param array $url
     * @param array $parameters
     * @return HttpResponse
     */
    public function doPost($url, $parameters = array()) {
        return $this->_doRequest($url, $parameters, true);
    }

    /**
     *
     * @param array $url
     * @param array $parameters
     * @return HttpResponse
     */
    public function doGet($url, $parameters = array()) {
        return $this->_doRequest($url, $parameters, false);
    }
    
    /**
     *
     * @param boolean $enabled 
     */
    public function setFollowLocation($enabled) {
        $this->followLocation = $enabled;
    }
    
    public function setTargetResponseDirectory($path) {
        $this->targetResponseDirectory = $path;
    }

    private function _doRequest($url, $parameters, $post) {        
        $curlOptions = array();        
        
        if ($post) {
            $curlOptions[CURLOPT_URL] = $url;
            $curlOptions[CURLOPT_POST] = true;
            $curlOptions[CURLOPT_POSTFIELDS] = $this->_buildParameterLine($parameters);
        } else {
            $curlOptions[CURLOPT_URL] = $url . '?' . $this->_buildParameterLine($parameters);
            $curlOptions[CURLOPT_POST] = false;
        }

        $curlOptions[CURLOPT_USERAGENT] = $this->agent;
        $curlOptions[CURLOPT_COOKIEJAR] = $this->_getCookiesFile();
        $curlOptions[CURLOPT_COOKIEFILE] = $this->_getCookiesFile();
        $curlOptions[CURLOPT_FOLLOWLOCATION] = $this->followLocation;
        $curlOptions[CURLOPT_HEADER] = true;
        $curlOptions[CURLOPT_RETURNTRANSFER] = true;
        $curlOptions[CURLOPT_REFERER] = $this->referer;        

        $this->referer = $url;
        $response = new HttpResponse($curlOptions);
        
        if ($this->targetResponseDirectory) {
            $path = $this->targetResponseDirectory.'/'.(++self::$targetCount).'.html';
            file_put_contents($path, $response->getBody());
        }
        
        return $response;
    }

    private function _getCookiesFile() {
        if (empty($this->cookiesFile)) {
            $this->cookiesFile = tempnam("/tmp", "CURLCOOKIE");
        }
        return $this->cookiesFile;
    }

    private function _buildParameterLine($parameters) {
        $pairs = array();
        foreach ($parameters as $key => $value) {
            $pairs[] = urlencode($key) . '=' . urlencode($value);
        }
        return implode('&', $pairs);
    }

}

?>