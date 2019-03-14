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

    public function getAllForestsAction()
    {
        $read = $this->pdo->prepare('SELECT JF.*,COUNT(JT.ID) AS "Tree Count" FROM journey_forests JF LEFT JOIN journey_trees JT ON JT.ForestID = JF.ID GROUP BY JF.ID');
        $read->execute();
        $results = $read->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }


    public function createNewQuestionAction()
    {
        $write = $this->pdo->prepare("INSERT INTO `ApexProducts`.`journey_questions` (`QuestionText`, `Active`, `TreeID`) VALUES (:questionText, 'Y', :treeID);");
        $write->execute([':questionText'=>$this->data['question'],':treeID'=>$this->data['treeID']]);

        $questionID = $this->pdo->lastInsertId();

        foreach ($this->data['answers'] as $answer) {
            $writeAnswer = $this->pdo->prepare("INSERT INTO ApexProducts.journey_answers (AnswerText,QuestionID,NextQuestionID,Weight) VALUES (:answerText,:questionID,'-1','0')");
            $writeAnswer->execute([':answerText'=>$answer['answerText'],':questionID'=>$questionID]);
        }


        return false;
    }

    public function updatequestionAction()
    {
        $write = $this->pdo->prepare("UPDATE journey_questions SET QuestionText = :questionText WHERE ID = :questionID");
        $write->execute([':questionText' => $this->data['questionText'],':questionID'=>$this->data['questionID']]);
    }

    public function moveQuestionAction()
    {

        $write = $this->pdo->prepare("UPDATE journey_questions SET PositionX = :positionX, PositionY = :positionY WHERE ID = :questionID");
        $params = [':positionX'=>$this->data['positionX'],':positionY'=>$this->data['positionY'],':questionID'=>$this->data['questionID']];
        // error_log(print_r($params,1));
        $write->execute($params);
        return true;
    }

    protected function nullOutAnswers($questionID)
    {
        $write = $this->pdo->prepare("UPDATE journey_answers SET NextQuestionID = -1 WHERE NextQuestionID = :questionID");
        $write->execute([':questionID'=>$questionID]);
    }

    public function deleteQuestionAction()
    {
        $write = $this->pdo->prepare("UPDATE journey_questions SET TreeID = TreeID * -1 WHERE ID = :questionID");
        $write->execute([':questionID'=>$this->data['questionID']]);

        $this->nullOutAnswers($this->data['questionID']);

    }




}
