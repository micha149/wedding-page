<?php

namespace Micha149\GalleryBundle;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

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
    
    public function __construct(\AmazonS3 $s3, $bucket)
    {
        $this->_s3 = $s3;
        $this->_bucket = $bucket;
    }
    
    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;
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
        return 'http://' . $this->_bucket . '.s3-external-3.amazonaws.com/';
    }
    
    public function getCount($event = null)
    {
        return count($this->_loadData());
    }
    
    protected function getSignedUrl($url, $download = false)
    {
        $expires  = "3 hours";
        $response = array();
        
        if ($download) {
            $response['content-disposition'] = 'attachment';
        }
        
        return $this->_s3->get_object_url($this->_bucket, $url, $expires, array('response' => $response));
    }
        
    protected function _loadData()
    {
        if (!$this->data) {
                    
            $objects = $this->_s3->get_object_list($this->_bucket, array(
                'pcre' => '/(.+)\/(.+)\/(.+_o.+)/i'
            ));
            
            $results = array();
            
            foreach ($objects as $object) {

                list($event, $author, $filename) = explode('/', $object);
                
                if (!isset($results[$event])) {
                    $results[$event] = array();
                }

                $results[$event][] = array(
                    'original'  => $this->getSignedUrl((string) $object, true),
                    'thumbnail' => $this->getSignedUrl(str_replace('_o', '_s', $object)),
                    'lightbox'  => $this->getSignedUrl(str_replace('_o', '_l', $object)),
                    'filename'  => $filename,
                    'event'     => $event,
                    'author'    => $author,
                );                
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