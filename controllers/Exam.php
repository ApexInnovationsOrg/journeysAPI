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
        
        $read->execute([':journeyTree'=>$this->_params['treeID']]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    public function getQuestionAction()
    {

        
        $lastQuestionArr = explode(',',$this->exam->QuestionsAsked);
        
        $read = $this->pdo->prepare('SELECT ID,QuestionText FROM journey_questions WHERE ID = :questionID AND TreeID = :treeID');
        $read->execute([':questionID'=>end($lastQuestionArr),':treeID'=>$this->_params['treeID']]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);


        $content = DB::table('journey_content')
                ->select('Content')
                ->where('QuestionID',end($lastQuestionArr))
                ->get();
        $contentArr = [];
        foreach($content as $piece)
        {
            array_push($contentArr,$piece->Content);
        }

        return ['question'=>$results,'content'=>$contentArr];
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
    
        $nextQuestion = $this->getNextQuestion();

        $this->exam->QuestionsAsked .= ',' . $nextQuestion->ID;
        $this->exam->QuestionsAsked = trim($this->exam->QuestionsAsked,",");

        $this->exam->save();

        //run complete exam routine;

        return ['success'=>true,'followUp'=>$this->getFollowupForLastQuestion(),'examComplete'=>$nextQuestion->ID == -1];
        
    }

    private function getFollowupForLastQuestion()
    {
        $followupText = 'N/A';
        $followup = DB::table('journey_followups')
                    ->where('AnswerID',$this->data['data'])
                    ->first();
        
        if($followup->ID)
        {
            $followupText = $followup->FollowupText;
        }

        return $followupText;
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


        $content = DB::table('journey_content')
                        ->where('QuestionID',$answer->NextQuestionID)
                        ->get();


        if($answer->NextQuestionID === -1)
        {
            $this->completeExam();
            return (object)['ID'=>-1];
        }

        return $question;
    }

    public function getExamResultsAction()
    {
        return ['completed'=>$this->exam->JourneyCompleted,'score'=>$this->getScore(),'numberOfQuestions'=>count(explode(',', $this->exam->QuestionsAsked))];
    }

    private function getScore()
    {
        $score = 0;
        $answersArr = explode(',', $this->exam->AnswersGiven);

        foreach($answersArr as $answerID)
        {
            $answer = DB::table('journey_answers')
                            ->where('ID',$answerID)
                            ->first();

            $score += $answer->Weight;
        }

        return $score;

    }

    private function getExam()
    {
        if($this->data['action'] == 'getExamResults')
        {
            $exam = JourneyResults::where('UserID',$this->user->ID)
                        ->where('JourneyTreeID',$this->_params['treeID'])
                        ->whereNotNull('JourneyCompleted')
                        ->orderBy('ID','desc')
                        ->first();
        }
        else
        {
            $exam = JourneyResults::where('UserID',$this->user->ID)
            ->where('JourneyTreeID',$this->_params['treeID'])
            ->where('JourneyCompleted',null)
            ->first();
        }
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
        $exam->JourneyTreeID = $this->_params['treeID'];
        $exam->JourneyStarted = date("Y-m-d H:i:s");
        $exam->QuestionsAsked = $this->getExamMaster()->MasterQuestionID;
        $exam->save();
        return $this->getExam();
    }

    // private function userValidForExam()
    // {

    // }

    private function getExamMaster()
    {
        $master = DB::table('journey_trees')
                        ->where('ID',$this->_params['treeID'])
                        ->first();
        return $master;

    }

}
