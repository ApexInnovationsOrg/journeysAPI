<?php

use Illuminate\Database\Capsule\Manager as DB;
use Dotenv\Dotenv as Dotenv;


class Forest
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

    public function getSingleForestAction()
    {
        $read = $this->pdo->prepare('SELECT ID, Name, MasterQuestionID, TreeOrder FROM journey_trees WHERE ForestID = :forestID');
        $read->execute([':forestID'=>$this->data['data']]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }


    public function getTreeAction()
    {
       
        $questions = $this->getQuestions($this->data['data']);

        foreach ($questions as $key => $question) {
            $questions[$key]['Answers'] = $this->getAnswers($question['ID']);
        }
        
        return $questions;
    }

    private function getQuestions($treeID)
    {
        $read = $this->pdo->prepare('SELECT * FROM journey_questions WHERE TreeID = :treeID');
        $read->execute([':treeID'=>$treeID]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);
        
        return $results;
    }

    private function getAnswers($questionID)
    {   
        $read = $this->pdo->prepare('SELECT * FROM journey_answers WHERE QuestionID = :questionID');
        $read->execute([':questionID'=>$questionID]);
        $answers = $read->fetchAll(PDO::FETCH_ASSOC);

        return $answers;
    }



}
