<?php

use Illuminate\Database\Capsule\Manager as DB;
use Dotenv\Dotenv as Dotenv;
use OpenCloud\Rackspace;
use OpenCloud\ObjectStore\Constants as Constant;

class Content
{
    private $_params;
    private $data;
    private $pdo;
    private $user;
    private $files;
    private $exam;

    public function __construct($params,$user)
    {
        $this->_params = $params;
        $this->user = $user;
        $this->data = $params;
        $this->files = $_FILES;

        //Open database connection
        $this->pdo = apx_pdoConn::getConnection();
        
    }

    public function createNewContentAction()
    {
        error_log('creating new content');
        
        $write = $this->pdo->prepare("INSERT INTO journey_content (QuestionID,Content) VALUES (:questionID,:content)");
        $write->execute([':questionID'=>$this->data['questionID'],':content'=>$this->data['content']]);

        return $this->pdo->lastInsertId();
    }
    
    public function deleteContentAction()
    {
        $write = $this->pdo->prepare("UPDATE journey_content SET QuestionID = QuestionID * -1 WHERE ID = :questionID");
        $write->execute([':questionID'=>$this->data['questionID']]);
    }
    
    public function updateContentAction()
    {
        $write = $this->pdo->prepare("UPDATE journey_content SET Content = :content WHERE ID = :contentID");
        $write->execute([':contentID'=>$this->data['contentID'],':content'=>$this->data['content']]);
        
        return $this->pdo->lastInsertId();
    }

    public function uploadMediaAction()
    {
        $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT,array(
            'username' =>  getenv('RACKSPACE_USER'),
            'apiKey'   => getenv('RACKSPACE_API')
        ));
        $objectStoreService = $client->objectStoreService(null, 'ORD');
        
        $container = $objectStoreService->getContainer('journey');
        
        $name = $this->files["files"]["name"];
        $type = $this->files["files"]["type"];
        $size = $this->files["files"]["size"];
        
        // Temporary file name stored on the server
        $filename  = $this->files["files"]["tmp_name"];
        
        
        $handle = fopen($filename, 'r');
        $object = $container->uploadObject('question' . $this->data['questionID'] . $name, $handle);

        $publicURL = $object->getPublicUrl(Constant\UrlType::SSL);



        $string = (string)$publicURL;

        $this->data['content'] = json_encode(["type"=>"masterMedia","title"=>$name,"src"=>$string]);

        return $this->createNewContentAction();

    }

    




}
