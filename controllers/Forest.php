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
        $read = $this->pdo->prepare('SELECT ID, Name, MasterNodeID, TreeOrder FROM journey_trees WHERE ForestID = :forestID');
        $read->execute([':forestID'=>$this->data['data']]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }


    public function getTreeAction()
    {
       
        $nodes = $this->getNodes($this->data['data']);

        foreach ($nodes as $key => $node) {
            $nodes[$key]['Answers'] = $this->getAnswers($node['ID']);
            $nodes[$key]['Contents'] = DB::table('journey_content')->where('NodeID',$node['ID'])->get();
        }
        
        return $nodes;
    }

    private function getNodes($treeID)
    {
        $read = $this->pdo->prepare('SELECT * FROM journey_nodes WHERE TreeID = :treeID');
        $read->execute([':treeID'=>$treeID]);
        $results = $read->fetchAll(PDO::FETCH_ASSOC);
        
        // foreach($results as $result)
        // {

        //     $contents = DB::table('journey_content')
        //                     ->where('QuestionID',$result['ID'])
        //                     ->get();

        //     $result['Contents'] = $contents;
        // }

        return $results;
    }

    private function getAnswers($nodeID)
    {   
        $read = $this->pdo->prepare('SELECT JA.*,JF.ID AS `FollowupTextID`,JF.FollowupText FROM journey_answers JA LEFT JOIN journey_followups JF ON JF.AnswerID = JA.ID WHERE NodeID = :nodeID');
        $read->execute([':nodeID'=>$nodeID]);
        $answers = $read->fetchAll(PDO::FETCH_ASSOC);

        return $answers;
    }

    public function setMasterNodeAction()
    {
        $write = $this->pdo->prepare("UPDATE journey_trees SET MasterNodeID = :masterNodeID WHERE ID = :treeID");
                                      
        $write->execute([':masterNodeID'=>$this->data['masterNodeID'],':treeID'=>$this->data['treeID']]);
        
    }

    public function createTreeAction()
    {
        return DB::table('journey_trees')
            ->insertGetId(['Name'=>$this->data['name'],'ForestID'=>$this->data['forestID']]);
    }

    public function createForestAction()
    {
        return DB::table('journey_forests')
            ->insertGetId(['Name'=>$this->data['name']]);
    }
}
