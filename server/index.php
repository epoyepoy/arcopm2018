<?php
session_start();
error_reporting(E_ERROR);
# Require the Slim Framework
require 'vendor/autoload.php';


# Configuration Options to display errors * ONLY FOR DEVELOPMENT MODE *
$options = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
# The configuration object  to use as an argument in slim app  * ONLY FOR DEVELOPMENT MODE *
$configuration = new \Slim\Container($options);

# Instantiate a Slim application
$app = new Slim\App($configuration);


/**
 * Routes definition
 *
 * $app->get: Handles GET requests (Retrieve data)
 * $app->post: Handles POST requests (Create)
 * $app->put: Handles PUT requests (Update)
 * $app->delete: Handles DELETE requests (Delete)
 *
 */


/**
*   Auth
*   Authorization and Authentication of Users.
*/
# Authorization
/*require_once "middleware/Authentication.php";
require_once "middleware/Authorization.php";
require_once "services/RoleService.php";
*/
/**
 * Auth
 * Authorization and Authentication of Users.
 */

$app->post("/login", function ($request, $response, $args) {

    # Get user data
    $credentials = $request->getParsedBody();

    $userService = new UserService();
    $user = $userService->authenticate($credentials);
    $userService->login($user);

    return $response->getBody()->write(json_encode($user));
});

$app->get("/logout", function ($request, $response, $args) {

    $userService = new UserService();
    $userService->logout();
});

$app->post('/auth/login', function ($request, $response, $args) {
     require 'core/Login.php';

});



$app->get('/auth/login', function ($request, $response, $args) {

   require 'core/LoginDirectory.php';

});

$app->get('/auth/logout', function ($request, $response, $args){

   require 'core/Logout.php';

});

/**
*   Various Actions
*   E.g., Read an excel file
*/

$app->get('/excel', function ($request, $response, $args) {

   require 'actions/read.excel.php';

});

# Exports Raw data about project projects to an excel file
$app->get('/projects/{id:[0-9]+}/export', function ($request, $response, $args) {

   require 'actions/reports.export.excel.php';

});

# Route for the server logging end point
$app->post('/log/message', function ($request, $response, $args) {
    require 'actions/logger.php';
});

$app->post('/email/{email}/inform', function ($request, $response, $args) {

   require 'actions/email.inform.php';

});

$app->post('/email/{email}/launch', function ($request, $response, $args) {

   require 'actions/email.launch.php';

});





/**
*   Documentation
*   The API reference documentation
*/
$app->get('/api', function ($request, $response, $args){

   require 'core/Documentation.php';

});



/* Roles */
/*
$app->group("/roles",function(){
    require_once "actions/roles.php";
});*/

/**
*   Users
*   API to access User data.
*/


$app->get('/users', function ($request, $response, $args) {

  	require 'actions/users.get.php';

});

$app->put('/updatepass/{newpass}/{oldpass}', function ($request, $response, $args) {

  require 'actions/update.password.php';

});

$app->get('/users/count', function ($request, $response, $args) {

  	require 'actions/users.count.php';

});


//Angelos Choursoglou
// Get evaluations
$app->get('/evaluations/{userid}', function ($request, $response, $args) {
    require 'actions/evaluations.get.php';
});

$app->get('/myevaluations/{userid}', function ($request, $response, $args) {
    require 'actions/myevaluations.get.php';
});

// Get evaluations cycles
$app->get('/evaluationscycles/', function ($request, $response, $args) {
    require 'actions/evaluationscycles.get.php';
});

//Get active cycles
$app->get('/cycles/', function ($request, $response, $args) {
    require 'actions/cycles.get.php';
});

// Get questions
$app->get('/questions/{evalID}/{state}', function ($request, $response, $args) {
    require 'actions/questions.get.php';
});

// Get sections
$app->get('/sections/{evalID}/{userid}/{state}', function ($request, $response, $args) {
    require 'actions/sections.get.php';
});

// Get scores
$app->get('/scores/{evalID}/{userid}/{state}', function ($request, $response, $args) {
    require 'actions/scores.get.php';
});

// Get scores scales
$app->get('/scorescales/{evalID}', function ($request, $response, $args) {
    require 'actions/scorescales.get.php';
});

//Get dotted comments
$app->get('/evaluations/dottedcomments/{evalID}', function ($request, $response, $args) {
    require 'actions/evaluations.dottedcomments.get.php';
});

// Get development plans
$app->get('/evaluations/devplan/{evalID}/{state}', function ($request, $response, $args) {
    require 'actions/evaluations.devplan.get.php';
});

//Add new development plan
$app->post('/evaluations/addnewdevplan/{evalID}/{userid}/{state}', function ($request, $response, $args) {
    require 'actions/evaluations.devplan.create.php';
});

//Update development plan
$app->post('/evaluations/updatedevplan/{userid}', function ($request, $response, $args) {
    require 'actions/evaluations.devplan.update.php';
});

//Delete development plan
$app->post('/evaluations/deletedevplan/{devplanid}', function ($request, $response, $args) {
    require 'actions/evaluations.devplan.delete.php';
});

// Get development plan history
$app->get('/evaluations/devplanhistory/{evalID}', function ($request, $response, $args) {
    require 'actions/evaluations.devplanhistory.get.php';
});

// Get empDetails
$app->get('/empdetails/{evalID}', function ($request, $response, $args) {
    require 'actions/employeedetails.get.php';
});

// Get User Role
$app->get('/userrole/{evalID}', function ($request, $response, $args) {
    require 'actions/userrole.get.php';
});

// Get empDetails
$app->get('/reportingline/{evalID}', function ($request, $response, $args) {
    require 'actions/reportingline.get.php';
});

$app->get('/userreportingline/{evalID}/{cycleid}', function ($request, $response, $args) {
    require 'actions/userreportingline.get.php';
});

// Get charts evaluations
$app->get('/evaluations/statistics/{userid}', function ($request, $response, $args) {
    require 'actions/evaluations.statistics.get.php';
});

$app->post('/evaluations/documents/{empid}/{evalid}/{cycle}', function ($request, $response, $args) {
    require 'actions/document.create.php';
});

$app->post('/evaluations/documentsdelete/{empid}/{evalid}', function ($request, $response, $args) {
    require 'actions/document.delete.php';
});

$app->get('/support/', function ($request, $response, $args) {
    require 'actions/support.get.php';
});

// Update answers in questions
$app->put('/answers/{evalID}/{state}/{finish}/{pause}', function ($request, $response, $args) {
    require 'actions/answers.update.php';
});

//Update evaluation(employee manages team in configuration)
$app->post('/evaluations/updateevaluation/{empid}/{managesteam}/{userid}/{cycleid}', function ($request, $response, $args) {
    require 'actions/evaluations.employeemanagesteam.update.php';
});

//Add new goal
$app->post('/evaluations/addnewgoal/{userid}/{empid}/{cycleid}', function ($request, $response, $args) {
    require 'actions/evaluations.goal.create.php';
});

//Delete goal
$app->post('/evaluations/deletegoal/{goalid}', function ($request, $response, $args) {
    require 'actions/evaluations.goal.delete.php';
});

//Update goal
$app->post('/evaluations/updategoal/{userid}', function ($request, $response, $args) {
    require 'actions/evaluations.goal.update.php';
});

//Get goals inside evaluation form
$app->get('/evaluations/questionairegoals/{empid}/{userid}/{evalid}', function ($request, $response, $args) {
    require 'actions/evaluations.questionairegoals.get.php';
});

//Get goals
$app->get('/evaluations/goals/{empid}/{cycleid}', function ($request, $response, $args) {
    require 'actions/evaluations.goals.get.php';
});

//Get goals history
$app->get('/evaluations/goalshistory/{empid}/{cycleid}', function ($request, $response, $args) {
    require 'actions/evaluations.goalshistory.get.php';
});

//Get my goals list
$app->get('/evaluations/mygoalspercycle/{userid}/{cycleid}', function ($request, $response, $args) {
    require 'actions/evaluations.mygoalspercycle.get.php';
});

//Get employees goals list
$app->get('/evaluations/goalspercycle/{userid}/{cycleid}', function ($request, $response, $args) {
    require 'actions/evaluations.goalspercycle.get.php';
});

//Update state
$app->post('/evaluations/updatestate/{evalid}/{cycleid}/{userid}/{empid}/{onbehalf}', function ($request, $response, $args) {
    require 'actions/evaluations.state.update.php';
});

//Get goal attributes
$app->get('/evaluations/goalattributes/', function ($request, $response, $args) {
    require 'actions/evaluations.goalattributes.get.php';
});

//Reject manager
$app->post('/evaluations/rejectmanager/{empid}/{youraction}', function ($request, $response, $args) {
    require 'actions/evaluations.reject.manager.php';
});

//Reject rejection of manager
$app->post('/evaluations/revertmanager/{empid}/{youraction}', function ($request, $response, $args) {
    require 'actions/evaluations.revert.manager.php';
});

//Send back goals to employee
$app->post('/evaluations/sendbackgoals/{evalid}', function ($request, $response, $args) {
    require 'actions/evaluations.sendbackgoals.php';
});

//Revise evaluations
$app->post('/evaluations/revise/{empid}', function ($request, $response, $args) {
    require 'actions/evaluations.revise.php';
});

//Get evaluators
$app->get('/evaluators/{userid}', function ($request, $response, $args) {
    require 'actions/evaluators.get.php';
});

//Get evaluator's statistics of evaluation
$app->post('/evaluatorsevals/', function ($request, $response, $args) {
    require 'actions/evaluatorsevals.get.php';
});

//Get evaluator's statistics of evaluation(tendency 1)
$app->post('/evaluatorsavgtendency/', function ($request, $response, $args) {
    require 'actions/evaluatorsavgtendency.get.php';
});

//Get plot chart(bar chart)
$app->post('/plotchart/', function ($request, $response, $args) {
    require 'actions/plotchart.get.php';
});

//Get bell shaped chart(line chart)
$app->post('/bellshapedchart/', function ($request, $response, $args) {
    require 'actions/bellshapedchart.get.php';
});

//Get charts(tendency 1)
$app->post('/chartsdataavgtendency/', function ($request, $response, $args) {
    require 'actions/chartsdataavgtendency.get.php';
});

//Get company stats
$app->post('/companystats/', function ($request, $response, $args) {
    require 'actions/companystats.get.php';
});

//Get company stats by region
$app->post('/companystatsbyregion/', function ($request, $response, $args) {
    require 'actions/companystatsbyregion.get.php';
});

//Get company stats by question
$app->post('/companystatsbyquestion/', function ($request, $response, $args) {
    require 'actions/companystatsbyquestion.get.php';
});

//Get Scores Per Section
$app->post('/scorespersection/', function ($request, $response, $args) {
    require 'actions/scorespersection.get.php';
});

//Get Satisfaction Statistics
$app->post('/satisfactionbyquestion/', function ($request, $response, $args) {
    require 'actions/satisfactionbyquestion.get.php';
});

//Get Satisfaction Statistics (with question 12 or 14)
$app->post('/satisfactionbygradequestion/', function ($request, $response, $args) {
    require 'actions/satisfactionbygradequestion.get.php';
});

//Get localuser
$app->post('/localusers/', function ($request, $response, $args) {
    require 'actions/adminlocalUser.get.php';
});

//Update localuser
$app->post('/update-localusers/', function ($request, $response, $args) {
    require 'actions/adminlocalUser.update.php';
});


//Get Administration Reporting Line
$app->post('/adminreportingline/', function ($request, $response, $args) {
    require 'actions/adminreportingline.get.php';
});

//Get Administration Reporting Line
$app->post('/adminreportinglineupdate/', function ($request, $response, $args) {
    require 'actions/adminreportingline.update.php';
});

//Get Administration Projects
$app->get('/projects/', function ($request, $response, $args) {
    require 'actions/adminprojects.get.php';
});

//Get Administration Active Cycles
$app->get('/activecycles/', function ($request, $response, $args) {
    require 'actions/adminactivecycles.get.php';
});

//Get Administration Employee Evaluations
$app->get('/employeeevaluations/{empid}', function ($request, $response, $args) {
    require 'actions/adminemployeeevaluations.get.php';
});

//Reset Evaluation/Goals
$app->post('/resetevaluation/{evalid}/{userid}/{resetgoals}', function ($request, $response, $args) {
    require 'actions/adminresetevaluation.update.php';
});

//Reset Evaluation to previous state
$app->post('/resetlaststate/{evalid}/{userid}', function ($request, $response, $args) {
    require 'actions/adminresetlaststate.update.php';
});

//Delete from arcopm
$app->post('/adminreportinglineremove/', function ($request, $response, $args) {
    require 'actions/adminreportinglineremove.delete.php';
});

//Get Evaluation Periods(Cycles) for statistics
$app->get('/evaluationperiods/', function ($request, $response, $args) {
    require 'actions/evaluationperiods.get.php';
});

//Get Job Families for statistics
$app->get('/getfamilies/{userid}', function ($request, $response, $args) {
    require 'actions/getfamilies.get.php';
});

//Reports Get My Reporting Line
$app->post('/myreportingline/', function ($request, $response, $args) {
    require 'actions/myreportingline.get.php';
});

//Get Evaluation Periods(Cycles) for reports
$app->get('/reportsevaluationperiods/', function ($request, $response, $args) {
    require 'actions/reportsevaluationperiods.get.php';
});

//Get Goals' Comments
$app->get('/evaluations/goalscomments/{evalid}', function ($request, $response, $args) {
    require 'actions/goalscomments.get.php';
});

//save Comment in Goals
$app->post('/evaluations/goalssavecomment/{evalid}/{userid}/{state}', function ($request, $response, $args) {
    require 'actions/goalssavecomment.create.php';
});

//Clone selected goals
$app->post('/evaluations/cloneselectedgoals/{userid}/{evalid}', function ($request, $response, $args) {
    require 'actions/evaluations.goals.clone.php';
});


# Run the Slim application
$app->run();
