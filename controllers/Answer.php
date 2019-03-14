<?php

use Illuminate\Database\Capsule\Manager as DB;
use Dotenv\Dotenv as Dotenv;


class Answer
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

    public function createNewLinkAction()
    {
        $write = $this->pdo->prepare("UPDATE journey_answers SET NextQuestionID = :nextQuestionID WHERE ID = :answerID");
        $params = [':nextQuestionID'=>$this->data['nextQuestionID'],':answerID'=>$this->data['answerID']];
        // error_log(print_r($params,1));
        $write->execute($params);
        return true;
    }

    public function removeLinkAction()
    {
        $write = $this->pdo->prepare("UPDATE journey_answers SET NextQuestionID = -1 WHERE ID = :answerID");
        $params = [':answerID'=>$this->data['answerID']];
        // error_log(print_r($params,1));
        $write->execute($params);
        return true;
    }



}
