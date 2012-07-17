<?php 

namespace Micha149\GalleryBundle\DataCollector;

use Micha149\GalleryBundle\FileManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class GalleryDataCollector extends DataCollector
{

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }
    
    
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'count' => $this->fileManager->getCount(),
        );
    }

    public function getCount()
    {
        return $this->data['count'];
    }

    public function getName()
    {
        return 'gallery';
    }
}