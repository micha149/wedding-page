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
    
    public function __construct(HttpClient $client, $bucket, LoggerInterface $logger = null)
    {
        $this->_httpClient = $client;
        $this->_bucketName = $bucket;
        
        if ($logger) {
            $this->_logger = $logger;				
        }
    }
    
    public function getImagesByEvent ($event) {
        return $this->_loadData();
    }
    
    public function getBaseUrl()
    {
        return 'http://' . $this->_bucketName . '.s3-external-3.amazonaws.com/';
    }
    
    public function getCount($event = null)
    {
        return count($this->_loadData());
    }
    
    protected function _loadData()
    {
        $this->_httpClient->setBaseUrl($this->getBaseUrl());
        
        $response = $this->_httpClient->get('/')->send();
        
        $xml = new \SimpleXMLElement((string) $response->getBody());
        $results = array();
        
        foreach ($xml->Contents as $content) {
            $matches = array();
            $match   = preg_match("#(.+)\/(.+)\/(.+_o.+)#", $content->Key, $matches);
            
            if ($match) {
            
                $results[] = array(
                    'original'  => (string) $content->Key,
                    'thumbnail' => str_replace('_o', '_s', $content->Key),
                    'lightbox'  => str_replace('_o', '_l', $content->Key),
                    'filename'  => $matches[3],
                    'event'     => $matches[1],
                    'author'    => $matches[2],
                );                
            }
        }
        
        $this->_log('info', 'Loaded data from ' . $this->getBaseUrl());
        
        return $results;
    }
    
    protected function _log($level, $message)
    {
        if ($this->_logger) {
            $this->_logger->{$level}($message);
        }
    }
}