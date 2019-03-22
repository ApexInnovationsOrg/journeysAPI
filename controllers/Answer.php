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
    public function updateanswerAction()
    {
        if($this->data['answerID'] == '-1')
        {
            $this->data['answerID'] = $this->createnewanswerAction();
        }

        if(isset($this->data['answerWeight']))
        {
            $write = $this->pdo->prepare("UPDATE journey_answers SET AnswerText = :answerText, `Weight` = :answerWeight WHERE ID = :answerID");
            $write->execute([':answerText' => $this->data['answerText'],':answerWeight'=>$this->data['answerWeight'],':answerID'=>$this->data['answerID']]);
        }
        else
        {
            $write = $this->pdo->prepare("UPDATE journey_answers SET AnswerText = :answerText WHERE ID = :answerID");
            $write->execute([':answerText' => $this->data['answerText'],':answerID'=>$this->data['answerID']]);
        }

        if(isset($this->data['followupText']))
        {
            if(!isset($this->data['followupTextID']) || $this->data['followupTextID'] == null)
            {
                $this->createFollowupText();
            }
            else
            {
                $write = $this->pdo->prepare("UPDATE journey_followups SET FollowupText = :followupText WHERE ID = :followupTextID");
                $write->execute([':followupText' => $this->data['followupText'],':followupTextID'=>$this->data['followupTextID']]);
            }
        }
    }

    public function deleteanswerAction()
    {
        if(isset($this->data['answerID']))
        {
            $delete = $this->pdo->prepare("UPDATE journey_answers SET QuestionID = QuestionID * -1 WHERE ID = :answerID");
            $delete->execute([':answerID'=>$this->data['answerID']]);
        }
    }

    public function createFollowupText()
    {
        $write = $this->pdo->prepare("INSERT INTO journey_followups (FollowupText,AnswerID) VALUES (:followupText,:answerID)");
        $params = [':followupText'=>$this->data['followupText'],':answerID'=>$this->data['answerID']];
        $write->execute($params);
        return $this->pdo->lastInsertId();
    }

    public function createnewanswerAction()
    {
        $write = $this->pdo->prepare("INSERT INTO journey_answers (AnswerText, QuestionID, NextQuestionID, Weight) VALUE (:answerText, :questionID, '-1', '0')");
        $write->execute([':answerText'=>$this->data['answerText'],':questionID'=>$this->data['questionID']]);

        $answerID = $this->pdo->lastInsertId();

        return $answerID;
    }



}
