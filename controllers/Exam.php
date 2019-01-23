<?php

use Illuminate\Database\Capsule\Manager as DB;
use Dotenv\Dotenv as Dotenv;
use app\Models\journey_results as JourneyResults;

class Exam
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
          $this->exam = $this->getExam();
    }

    public function getTreeAction()
    {
        $read = $this->pdo->prepare('SELECT * FROM journey_trees JT JOIN journey_paths JP on JP.TreeID = JT.ID JOIN journey_questions JQ ON JP.QuestionID = JQ.ID WHERE JT.ID = :journeyTree');
        $read->execute([':journeyTree'=>1]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    public function getQuestionAction()
    {

      
        $lastQuestionArr = explode(',',$this->exam->QuestionsAsked);
        
        $read = $this->pdo->prepare('SELECT ID,QuestionText FROM journey_questions WHERE ID = :questionID');
        $read->execute([':questionID'=>end($lastQuestionArr)]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    public function getAnswersAction()
    {

        $lastQuestionArr = explode(',',$this->exam->QuestionsAsked);
        $read = $this->pdo->prepare('SELECT ID, AnswerText FROM journey_answers WHERE QuestionID = :questionID');
        $read->execute([':questionID'=>end($lastQuestionArr)]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }

    public function submitAnswerAction()
    {
        error_log(print_r($this->data['data'],1));


        return ['winning'];
    }

    private function isQuestionCorrect()
    {

    }

    private function getExam()
    {
        $exam = DB::table('journey_results')
                        ->where('UserID',$this->user->ID)
                        ->first();
        if(empty($exam))
        {
            $exam = $this->createNewExam();
        }
        return $exam;
    }

    private function createNewExam()
    {
        $exam = new JourneyResults;

        $exam->UserID = $this->user->ID;
        $exam->JourneyTreeID = 1;
        $exam->JourneyStarted = date("Y-m-d H:i:s");
        $exam->QuestionsAsked = $this->getExamMaster()->QuestionID;
        $exam->save();
        return $this->getExam();
    }

    private function getExamMaster()
    {
        $master = DB::table('journey_paths')
                        ->where('TreeID','1')
                        ->where('Master','Y')
                        ->first();
        return $master;

    }

}
