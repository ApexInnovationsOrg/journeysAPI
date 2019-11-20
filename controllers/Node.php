<?php

use Illuminate\Database\Capsule\Manager as DB;
use Dotenv\Dotenv as Dotenv;


class Node
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
        $this->pdo = DB::connection()->getPdo();
        
    }

    public function getAllForestsAction()
    {
        $read = $this->pdo->prepare('SELECT JF.*,COUNT(JT.ID) AS "Tree Count" FROM journey_forests JF LEFT JOIN journey_trees JT ON JT.ForestID = JF.ID GROUP BY JF.ID');
        $read->execute();
        $results = $read->fetchAll(PDO::FETCH_ASSOC);
        return $results;
    }


    public function createNewNodeAction()
    {
        
        $write = $this->pdo->prepare("INSERT INTO `ApexProducts`.`journey_nodes` (`NodeText`, `Active`, `TreeID`, `TypeID`) VALUES (:NodeText, 'Y', :treeID,:typeID);");
        $write->execute([':NodeText'=>$this->data['nodeText'],':treeID'=>$this->data['treeID'],':typeID'=>$this->data['typeID']]);

        $nodeID = $this->pdo->lastInsertId();

        if(isset($this->data['positionX']) && isset($this->data['positionY']))
        {
            $write = $this->pdo->prepare("UPDATE journey_nodes SET PositionX = :positionX, PositionY = :positionY WHERE ID = :nodeID");
            $write->execute([':positionX'=>$this->data['positionX'],':positionY'=>$this->data['positionY'],':nodeID'=>$nodeID]);   
        }

        foreach ($this->data['answers'] as $answer) {
            $writeAnswer = $this->pdo->prepare("INSERT INTO ApexProducts.journey_answers (AnswerText,NodeID,NextNodeID,Weight) VALUES (:answerText,:nodeID,'-1','0')");
            $writeAnswer->execute([':answerText'=>$answer['answerText'],':nodeID'=>$nodeID]);
        }


        return false;
    }

    public function updatenodeAction()
    {
        $write = $this->pdo->prepare("UPDATE journey_nodes SET NodeText = :NodeText WHERE ID = :nodeID");
        $write->execute([':NodeText' => $this->data['nodeText'],':nodeID'=>$this->data['nodeID']]);
    }

    public function movenodeAction()
    {

        $write = $this->pdo->prepare("UPDATE journey_nodes SET PositionX = :positionX, PositionY = :positionY WHERE ID = :nodeID");
        $params = [':positionX'=>$this->data['positionX'],':positionY'=>$this->data['positionY'],':nodeID'=>$this->data['nodeID']];
        $write->execute($params);
        return true;
    }

    protected function nullOutAnswers($nodeID)
    {
        $write = $this->pdo->prepare("UPDATE journey_answers SET NextNodeID = -1 WHERE NextNodeID = :nodeID");
        $write->execute([':nodeID'=>$nodeID]);
    }

    public function deletenodeAction()
    {
        $write = $this->pdo->prepare("UPDATE journey_nodes SET TreeID = TreeID * -1 WHERE ID = :nodeID");
        $write->execute([':nodeID'=>$this->data['nodeID']]);

        $this->nullOutAnswers($this->data['nodeID']);

    }





}
