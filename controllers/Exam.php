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

    public function completeExam()
    {
        $this->exam->JourneyCompleted = date("Y-m-d H:i:s");
        $this->exam->Score = 93;
        $this->exam->save();

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
        $answer = $this->data['data'];
        $questionArr = explode(',',$this->exam->QuestionsAsked);

        $this->exam->AnswersGiven .= ',' . $answer;
     
        $this->exam->AnswersGiven = trim($this->exam->AnswersGiven,",");
    
        // $this->exam->save();
        $nextQuestion = $this->getNextQuestion();

        $this->exam->QuestionsAsked .= ',' . $nextQuestion->ID;
        $this->exam->QuestionsAsked = trim($this->exam->QuestionsAsked,",");

        $this->exam->save();

        //run complete exam routine;

        return ['success'=>true,'followUp'=>'That’s correct. Patients presenting with signs and symptoms of an ischemic stroke should begin certain treatments within 4.5 hours. Therefore, asking about the timing of the onset of symptoms can help determine the course of treatment. Asking about the timing of the last meal and the disposition of medications from home take a lesser priority at this time.
(The links are references…perhaps we can use them as documents as well.)
https://www.ahajournals.org/doi/abs/10.1161/STR.0000000000000158','examComplete'=>$nextQuestion->ID == -1];
        

        // return ['success'=>true];
    }

    private function getNextQuestion()
    {


        $questionArr = explode(',',$this->exam->QuestionsAsked);
        $answerArr = explode(',',$this->exam->AnswersGiven);


        $answer = DB::table('journey_answers')
                    ->where('ID',end($answerArr))
                    ->first();

        

        $question = DB::table('journey_questions')
                    ->where('ID',$answer->NextQuestionID)
                    ->first();



        if($answer->NextQuestionID === -1)
        {
            $this->completeExam();
            return (object)['ID'=>-1];
        }

        return $question;
    }

    public function getExamResultsAction()
    {
        return ['completed'=>$this->exam->JourneyCompleted,'score'=>$this->exam->Score,'numberOfQuestions'=>count(explode(',', $this->exam->QuestionsAsked))];
    }

    

    private function getExam()
    {
        $exam = JourneyResults::where('UserID',$this->user->ID)
                        ->where('JourneyCompleted',null)
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
                        ->where('TreeID','1')//need to get the journey ID they are launching
                        ->where('Master','Y')
                        ->first();
        return $master;

    }

}
