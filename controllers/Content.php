<?php

use Illuminate\Database\Capsule\Manager as DB;
use Dotenv\Dotenv as Dotenv;


class Question
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

    public function createNewMediaAction()
    {
        $write = $this->pdo->prepare("INSERT INTO journey_content (QuestionID,Content) VALUES (:questionID,:content)");
        $write->execute([':questionID'=>$this->data['questionID'],':content'=>htmlspecialchars_decode(htmlentities($data['content'], ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES));
    }

    




}
