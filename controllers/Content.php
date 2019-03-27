<?php

use Illuminate\Database\Capsule\Manager as DB;
use Dotenv\Dotenv as Dotenv;


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

    




}
