<?php

namespace Micha149\GalleryBundle;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

class FileManager
{

    /**
     * Amazon AWS SDK S3 client
     *
     * @var \AmazonS3 $_s3
     */
    protected $_s3;
    
    protected $_cache;
    
    protected $_cacheId = 'all';
    
    protected $_bucketName;
    
    protected $_logger;
    
    protected $_data;
    
    public function __construct(\AmazonS3 $s3, $bucket)
    {
        $this->_s3 = $s3;
        $this->_bucket = $bucket;
    }
    
    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }
    
    public function setCacheDriver($cache) {
        $this->_cache = $cache;
    }
    
    public function getImagesByEvent ($event) {
        $data = $this->_getData();
        
        return $data[$event];
    }
    
    public function getEvents() {
        $data = $this->_getData();    
        return array_keys($data);
    }
    
    public function eventExists($event) {
        $data = $this->_getData();
        return isset($data[$event]) || array_key_exists($event, $data);
    }
    
    public function getBaseUrl()
    {
        return 'http://' . $this->_bucket . '.s3-external-3.amazonaws.com/';
    }
    
    public function getCount($event = null)
    {
        return count($this->_getData());
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
        
    protected function _getData()
    {
        if ($this->_data) {
            return $this->_data;
        }
        
        if (!$data = $this->_loadFromCache()) {
            $data = $this->_loadData();            
            $this->_saveToCache($data);
        }

        $this->_data = $data;
        
        return $this->_data;
    }
    
    protected function _loadData()
    {               
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
        
        return $results;
    }
    
    protected function _loadFromCache()
    {
        if ($this->_cache && $this->_cache->contains($this->_cacheId)) {
            return $this->_cache->fetch($this->_cacheId);
        }
        return false;
    }
    
    protected function _saveToCache($data)
    {
        if ($this->_cache) {
            $this->_cache->save($this->_cacheId, $data);
        }
    }
    
    protected function _log($level, $message)
    {
        if ($this->_logger) {
            $this->_logger->{$level}($message);
        }
    }
}