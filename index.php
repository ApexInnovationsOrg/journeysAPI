<?php
// Define path to data folder
//error_reporting(E_ALL);
require_once($_SERVER['DOCUMENT_ROOT'] . '/DBCONNECT.php');			// Allow Database Connections
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apx_pdoConn.php');
// require_once($_SERVER['DOCUMENT_ROOT'] . '/admin/SESSIONCONFIG.php');

require_once (__DIR__.'/vendor/autoload.php');

require_once 'app/start.php';

header('Access-Control-Allow-Origin: *');
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
    // session_abort();
    include_once($_SERVER['DOCUMENT_ROOT'] . '/SESSIONCONFIG.php');
    if(isset($_SESSION['userID'])) {
        handleUserSession();
    }
    else
    {
        $user = new apx_User(0);
    }
}

function handleAdminSession()
{
    global $user;
    $employee = new apx_Employee($_SESSION['AdminID']);
    $user = new apx_User('Login = "' . $employee->Email . '"');
}

function handleUserSession()
{
    global $user;
    $user = new apx_User($_SESSION['userID']);
}

function validUser()
{
    global $user;

    $userDepartment = $user->Department();
    $userOrganization = $userDepartment->Organization();
    return $userOrganization->ID == 221;
}
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if(DEVELOPMENT)
{
    $user = new apx_User(152002);
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
    $result['errormsg'] = "You are not logged on to the admin website! <div style='font-size:2em'>Go to The <strong style='font-size:1.25em'><u><a target='blank' href='/admin/Home.php'>Admin Site</a></u></strong> and re-log in. </div><br/>UserID: " . $user->ID;
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