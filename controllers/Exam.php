<?php 

use Illuminate\Database\Capsule\Manager as DB;
use Dotenv\Dotenv as Dotenv;
use app\Models\journey_results as JourneyResults;
include('Forest.php');

class Exam
{
    private $_params;
    private $data;
    private $pdo;
    private $user;
    private $files;
    private $exam;
    private $wholeTree;
    private $foundNodes = [];

    public function __construct($params,$user)
    {
        $this->_params = $params;
        $this->user = $user;
        $this->data = $params;
        //Open database connection
        $this->pdo = DB::connection()->getPdo();
        $this->exam = $this->getExam();
        $completeTree = new Forest(['data'=>$this->exam->JourneyTreeID],$this->user);
        $this->wholeTree = $completeTree->getTreeAction();
    }

    public function getTreeAction()
    {
        $read = $this->pdo->prepare('SELECT * FROM journey_trees JT JOIN journey_paths JP on JP.TreeID = JT.ID JOIN journey_nodes JQ ON JP.NodeID = JQ.ID WHERE JT.ID = :journeyTree');
        
        $read->execute([':journeyTree'=>$this->_params['treeID']]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }

    public function getquestionAction()
    {
        return $this->getNodeAction();
    }

    public function getNodeAction()
    {

        $lastNodeHitArr = explode(',',$this->exam->NodesHit);
        $read = $this->pdo->prepare('SELECT ID,NodeText FROM journey_nodes WHERE ID = :nodeID AND TreeID = :treeID');
        $read->execute([':nodeID'=>end($lastNodeHitArr),':treeID'=>$this->_params['treeID']]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);


        $content = DB::table('journey_content')
                ->select('Content')
                ->where('NodeID',end($lastNodeHitArr))
                ->get();
        $contentArr = [];
        foreach($content as $piece)
        {
            array_push($contentArr,$piece->Content);
        }

        $totalNodes =  count($this->findNextNodes($this->findInTree($this->getExamMaster()->MasterNodeID)));
        
        $possibleNodes = count($this->getProgress());


        $progress = ($totalNodes - $possibleNodes) / $totalNodes;


        return ['node'=>$results,'content'=>$contentArr,'progress'=>$progress];
    }

    private function getProgress()
    {
        
        $lastNodeHitArr = explode(',',$this->exam->NodesHit);        
        $currentNode = $this->findInTree(end($lastNodeHitArr));
        $this->foundNodes = [];
        return $this->findNextNodes($currentNode);
   
    }
    private function findNextNodes($node)
    {
        if(!in_array($node['ID'],$this->foundNodes))
        {  
            $this->foundNodes[] = $node['ID'];
            foreach($node['Answers'] as $answer)
            {
                // error_log(print_r($answer['NextNodeID'],1));
                if($answer['NextNodeID'] !== -1)
                {
                    $this->findNextNodes($this->findInTree($answer['NextNodeID']));

                }
            }
        }

        return $this->foundNodes;
    }

    private function findInTree($nodeID)
    {       
        foreach($this->wholeTree as $node)
        {
            if($node['ID'] == $nodeID)
            {
                return $node;
            }
        }
    }

    public function completeExam()
    {
        $this->exam->JourneyCompleted = date("Y-m-d H:i:s");
        $this->exam->Score = 93;
        $this->exam->save();

    }

    public function getAnswersAction()
    {

        $lastNodeHitArr = explode(',',$this->exam->NodesHit);
        $read = $this->pdo->prepare('SELECT ID, AnswerText FROM journey_answers WHERE NodeID = :nodeID');
        $read->execute([':nodeID'=>end($lastNodeHitArr)]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }

    public function submitAnswerAction()
    {
        $answer = $this->data['data'];

        $this->exam->AnswersGiven .= ',' . $answer;
     
        $this->exam->AnswersGiven = trim($this->exam->AnswersGiven,",");
    
        $nextNode = $this->getNextNode();

        $this->exam->NodesHit .= ',' . $nextNode->ID;
        $this->exam->NodesHit = trim($this->exam->NodesHit,",");

        $this->exam->save();

        //run complete exam routine;

        return ['success'=>true,'followUp'=>$this->getFollowupForLastQuestion(),'examComplete'=>$nextNode->ID == -1];
        
    }

    private function getFollowupForLastQuestion()
    {
        $followupText = 'N/A';
        $followup = DB::table('journey_followups')
                    ->where('AnswerID',$this->data['data'])
                    ->first();
        if($followup)
        {
            $followupText = $followup->FollowupText;
        }


        return $followupText;
    }

    private function getNextNode()
    {


        $questionArr = explode(',',$this->exam->NodesHit);
        $answerArr = explode(',',$this->exam->AnswersGiven);


        $answer = DB::table('journey_answers')
                    ->where('ID',end($answerArr))
                    ->first();

        

        $question = DB::table('journey_nodes')
                    ->where('ID',$answer->NextNodeID)
                    ->first();


        $content = DB::table('journey_content')
                        ->where('NodeID',$answer->NextNodeID)
                        ->get();


        if($answer->NextNodeID === -1)
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
            $exam = JourneyResults::where('UserID','=',$this->user->ID)
                        ->where('JourneyTreeID','=',$this->_params['treeID'])
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

        if($exam->NodesHit == null)
        {
            $exam->NodesHit = $this->getExamMaster()->MasterNodeID;
            $exam->save();
        }


        return $exam;
    }

    private function createNewExam()
    {
        $exam = new JourneyResults;
        $exam->UserID = $this->user->ID;
        $exam->JourneyTreeID = $this->_params['treeID'];
        $exam->JourneyStarted = date("Y-m-d H:i:s");
        $exam->NodesHit = $this->getExamMaster()->MasterNodeID;
        $exam->save();
        return $this->getExam();
    }


    private function getExamMaster()
    {
        $master = DB::table('journey_trees')
                        ->where('ID',$this->_params['treeID'])
                        ->first();
        return $master;

    }

}
