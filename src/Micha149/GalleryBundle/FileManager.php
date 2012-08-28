<?php

namespace Micha149\GalleryBundle;

use Guzzle\Service\Client as HttpClient,
    Symfony\Component\HttpKernel\Log\LoggerInterface;

class FileManager
{

    /**
     * Http Client
     * @var Guzzle\Service\Client
     */
    protected $_httpClient;
    
    protected $_bucketName;
    
    protected $_logger;
    
    protected $data;
    
    public function __construct(HttpClient $client, $bucket, $secretKey, $accessKey, LoggerInterface $logger = null)
    {
        $this->_httpClient = $client;
        $this->_bucketName = $bucket;
        $this->secretKey   = $secretKey;        
        $this->accessKey   = $accessKey;
        
        if ($logger) {
            $this->_logger = $logger;				
        }
    }
    
    public function getImagesByEvent ($event) {
        $data = $this->_loadData();
        
        return $data[$event];
    }
    
    public function getEvents() {
        $data = $this->_loadData();    
        return array_keys($data);
    }
    
    public function getBaseUrl()
    {
        return 'http://' . $this->_bucketName . '.s3-external-3.amazonaws.com/';
    }
    
    public function getCount($event = null)
    {
        return count($this->_loadData());
    }
    
    protected function getSignedUrl($url, $download = false) {
        $expires      = time() + 86400;
        $url = ltrim($url, '/');
        $stringToSign = sprintf("GET\n\n\n%s\n/%s/%s", $expires, $this->_bucketName, $url);
        
        if ($download) {
            $stringToSign .= '?response-content-disposition=attachment';
        }

        $signature = urlencode(base64_encode(hash_hmac('sha1', utf8_encode($stringToSign), $this->secretKey, true)));
        
        return sprintf(
            "%s?AWSAccessKeyId=%s&Signature=%s&Expires=%d%s",
            $url,
            $this->accessKey,
            $signature,
            $expires,
            $download ? '&response-content-disposition=attachment' : ''
        );
    }
        
    protected function _loadData()
    {
        if (!$this->data) {
            $this->_httpClient->setBaseUrl($this->getBaseUrl());
            
            $response = $this->_httpClient->get($this->getSignedUrl('/'))->send();
            
            $xml = new \SimpleXMLElement((string) $response->getBody());
            $results = array();
            
            foreach ($xml->Contents as $content) {
                $matches = array();
                $match   = preg_match("#(.+)\/(.+)\/(.+_o.+)#", $content->Key, $matches);
                
                if ($match) {
                
                    if (!isset($results[$matches[1]])) {
                        $results[$matches[1]] = array();
                    }

                    $results[$matches[1]][] = array(
                        'original'  => $this->getSignedUrl((string) $content->Key, true),
                        'thumbnail' => $this->getSignedUrl(str_replace('.JPG', '.jpg', (str_replace('_o', '_s', $content->Key)))),
                        'lightbox'  => $this->getSignedUrl(str_replace('.JPG', '.jpg', (str_replace('_o', '_l', $content->Key)))),
                        'filename'  => $matches[3],
                        'event'     => $matches[1],
                        'author'    => $matches[2],
                    );                
                }
            }
            
            $this->_log('info', 'Loaded data from ' . $this->getBaseUrl());
            
            $this->data = $results;
        }        
        
        return $this->data;
    }
    
    protected function _log($level, $message)
    {
        if ($this->_logger) {
            $this->_logger->{$level}($message);
        }
    }
}