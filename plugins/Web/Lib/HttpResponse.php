<?php

class HttpResponse {

    private $curlHandle;
    private $result;
    private $curlOptions;

    public function __construct($curlOptions) {
        $this->curlOptions = $curlOptions;
        $this->curlHandle = curl_init();
        curl_setopt_array($this->curlHandle, $curlOptions);
        $this->result = curl_exec($this->curlHandle);
        $outputFile = tempnam('/tmp', 'OUTPUT_');
        file_put_contents($outputFile, $this->getBody());
        
    }
    
    public function getInfo() {
        return curl_getinfo($this->curlHandle);
    }

    public function getUrl() {
        return $this->curlOptions[CURLOPT_URL];
    }

    public function getStatusCode() {
        return curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    }

    public function getHeader() {
        $info = curl_getinfo($this->curlHandle);
        return substr($this->result, 0, $info['header_size']);
    }

    public function getBody() {
        $info = curl_getinfo($this->curlHandle);
        return substr($this->result, -$info['download_content_length']);
    }

    public function close() {
        curl_close($this->curlHandle);
    }

}

?>