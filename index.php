<?php
// Define path to data folder
//error_reporting(E_ALL);
// require_once($_SERVER['DOCUMENT_ROOT'] . '/DBCONNECT.php');			// Allow Database Connections
// require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apx_pdoConn.php');
// require_once($_SERVER['DOCUMENT_ROOT'] . '/admin/SESSIONCONFIG.php');

require_once (__DIR__.'/vendor/autoload.php');

require_once 'app/start.php';

use app\Models\user as user;
use app\Models\employee as employee;

header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Credentials: true');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

const DEVELOPMENT = true;
$user = null;
if(isset($_SESSION['AdminID']))
{

    handleAdminSession();
}
else
{
    // error_log(print_r($_SESSION,1));
    // session_abort();
    // include_once($_SERVER['DOCUMENT_ROOT'] . '/SESSIONCONFIG.php');
    if(isset($_SESSION['userID'])) {
        handleUserSession();
    }
    else
    {
        $user = user::find(0);
    }
}

function handleAdminSession()
{
    global $user;
    $employee = employee::find($_SESSION['AdminID']);
    $user = user::where('Login', $employee->Email)->first();
}

function handleUserSession()
{

    global $user;
    $user = user::find($_SESSION['userID']);
}

function validUser()
{
    global $user;
    if($user == null)
    {
        return false;
    }
    
    $userDepartment = $user->Department();
    $userOrganization = $userDepartment->Organization();
    return $userOrganization->ID == 221;
}
// error_log(print_r($_FILES,1));
if($_FILES)
{
    $input = json_decode($_REQUEST['json'],true);
    
}
else
{

    $inputJSON = file_get_contents('php://input');
    
    $input = json_decode($inputJSON, TRUE);
}
// error_log(print_r($input,1));
if(DEVELOPMENT)
{
    $user = user::find(152002);
}

if(validUser() || $input['controller'] == 'Exam')
{
 
    //wrap the whole thing in a try-catch block to catch any wayward exceptions!
    try {
        //get all of the parameters in the POST/GET request
        $params = $input;

        //get the controller and format it correctly so the first
        //letter is always capitalized
        $controller = ucfirst(strtolower($params['controller']));
        $controllerString = $controller;
        //get the action and format it correctly so all the
        //letters are not capitalized, and append 'Action'
        $action = strtolower($params['action']) . 'Action';

        //check if the controller exists. if not, throw an exception
        if (file_exists("controllers/{$controller}.php")) {
            include_once "controllers/{$controller}.php";
        } else {
            throw new Exception('Controller is invalid. Controller given:' . $controller);
        }

        //create a new instance of the controller, and pass
        //it the parameters from the request
        $controller = new $controller($params,$user);

        //check if the action exists in the controller. if not, throw an exception.
        if (method_exists($controller, $action) === false) {
            throw new Exception('Action is invalid. Controller/Action given: ' . $controllerString . '/' . $action);
        }

        //execute the action
        $result['data'] = $controller->$action();
        $result['success'] = true;

    } catch (Exception $e) {
        //catch any exceptions and report the problem
        $result = array();
        $result['success'] = false;
        $result['errormsg'] = $e->getMessage();
    }
}
else
{

//    error_log('3');
    $result = array();
    $result['success'] = false;
    $result['errormsg'] = "You are not logged on to the admin website! Go to The Admin Site and re-log in. UserID: " . $user->ID;
}
// error_log(print_r($result,1));
//echo the result of the API call
    if(isset($params) && array_key_exists('datatype', $params) && $params['datatype'] !== 'JSON')
    {
        return $result['data'];        
    }
    else
    {
        echo json_encode($result);
    }


exit();