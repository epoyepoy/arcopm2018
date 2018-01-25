<?php

class GoalsDAO{

	private $connection = NULL;


	public function __construct($conn)
	{
		$this->connection = $conn;
	}


    /*****
     *	Administration of Goals
     *
     */
    public function getQuestionaireGoals($empno, $userid, $evalid)
	{
		$queryString="Declare @evalid int = :evalid, @userid varchar(5)=:userid ;
		SELECT G.GoalID, G.EvaluationID, G.GoalDescription, cast(G.Weight as int) as Weight, G.AttributeCode, GA.CodeDescription, GA.AttDescription as AttributeFullDescription,
				A.Answer as EmpAchievement,
				AE.Answer as EvalAchievement,
				AR.Answer as RevAchievement 
				FROM dbo.GOALS G
		        INNER JOIN Evaluations E ON E.EvaluationID=G.EvaluationID 
				INNER JOIN GoalAttributes GA on GA.AttributeCode=G.AttributeCode
				LEFT JOIN Answers A ON A.GoalID=G.GoalID AND A.State=3
				LEFT JOIN Answers AE ON AE.GoalID=G.GoalID AND AE.State=5 AND E.EmployeeID<>@userid --this is in order not to retrieve the evaluator's answer if you are the employee
				LEFT JOIN Answers AR ON AR.GoalID=G.GoalID AND AR.State=6 AND (E.EmployeeID<>@userid OR (E.State=7 AND AR.Finished=1)) 
				WHERE G.EvaluationID=@evalid AND G.State=2
		";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':userid', $userid, PDO::PARAM_STR);
        // $query->bindValue(':userid1', $userid, PDO::PARAM_STR);
        $query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["goals"] = $query->fetchAll();
		return $result;
	}

	public function getActiveGoalCycles()
	{
		$queryString="
		SELECT ID AS CycleID, CycleDescription FROM EvaluationsCycle WHERE goalsInputStatus=1
		";
		$query = $this->connection->prepare($queryString);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result["activeGoalCycles"] = $query->fetchAll();
		return $result;
	}

	public function getMyGoalsPerCycle($empno, $cycleid)
	{
		$queryString="
		Declare @empno as varchar(5) = :empno;
		Declare @cycleid as int = :cycleid;
		SELECT EC.ID as CycleID, EC.CycleDescription, EC.goalsInputStatus, E.ManagesTeam, ISNULL(E.EmployeeID, @empno) AS Empno, HR.job_desc,
		rtrim(ltrim(HR.family_name))+' '+rtrim(ltrim(HR.first_name)) as 'employeeName', HR.grade, ISNULL(E.State, 0) as EvalState, E.EvaluationID, onBehalf.NoAsnwers as onBehalfFlag
		FROM EvaluationsCycle EC
		LEFT JOIN Evaluations E ON E.CycleID=EC.ID AND E.EmployeeID=@empno
		LEFT JOIN Goals G on G.EvaluationID=E.EvaluationID
		LEFT JOIN  dbo.vw_arco_employee HR on HR.empno=isnull(E.EmployeeID, @empno)
		LEFT JOIN ReportingLine RL on RL.empnosource = HR.empno AND RL.state=5
		OUTER APPLY (
		SELECT case when count(*) >0 then 1 else 0 end as 'NoAsnwers' FROM Evaluations E
		WHERE State=0 AND UserID<>@empno AND CycleID=@cycleid and E.EmployeeID=@empno
		) onBehalf
		WHERE EC.ID=@cycleid AND HR.grade>3 AND ISNULL(RL.excludeFromCycles,0)<>@cycleid
		GROUP BY EC.ID, EC.CycleDescription, EC.goalsInputStatus, E.ManagesTeam, E.EmployeeID, E.State, HR.family_name, HR.first_name,HR.job_desc, HR.grade, E.EvaluationID, onBehalf.NoAsnwers
		ORDER BY HR.grade asc, EC.goalsInputStatus desc
		";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':empno', $empno, PDO::PARAM_STR);
		$query->bindValue(':cycleid', $cycleid, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["CycleGoals"] = $query->fetchAll();
		return $result;
	}

	public function getUsersToSetGoals($userid, $cycleid)
	{
		$queryString="
		Declare @cycleid as int=:cycleid;
        Declare @userid as varchar(5)=:userid;
		DECLARE @hasExc	AS INT=(SELECT COUNT(*) FROM dbo.ReportingLineExceptions WHERE empnotarget=@userid AND goalCycle=@cycleid)
		IF @hasExc>0
		BEGIN
			SELECT EvCycle.ID as CycleID, EvCycle.CycleDescription, EvCycle.goalsInputStatus, Ev.ManagesTeam, RL.empnosource AS Empno, HR.job_desc,
			rtrim(ltrim(HR.family_name))+' '+rtrim(ltrim(HR.first_name)) as 'employeeName', HR.grade,
			CASE
				WHEN HR.GRADE<4 AND ISNULL(Ev.State, 0)=0 THEN 2
				ELSE ISNULL(Ev.State, 0)
			END as EvalState,
			Ev.EvaluationID, onBehalf.NoAsnwers as onBehalfFlag, yourAction.nstate as yourActionState, yourAction.yourAction as yourActionStateDescr, isnull(RL.wrongManager,0) as wrongManager,EvalAnswers.flagEvalAnswers
	        FROM dbo.ReportingLine RL
			LEFT JOIN  dbo.vw_arco_employee HR on HR.empno=RL.empnosource
			LEFT JOIN  dbo.Evaluations Ev on Ev.EmployeeID=RL.empnosource AND Ev.CycleID=@cycleid
			OUTER APPLY(
			SELECT * FROM EvaluationsCycle WHERE ID=@cycleid
			)EvCycle
			OUTER APPLY (
			SELECT case when count(*) >0 then 1 else 0 end as 'NoAsnwers' FROM Evaluations E
			WHERE State=0 AND UserID<>@userid AND CycleID=@cycleid and E.EmployeeID=rl.empnosource
			) onBehalf
			OUTER APPLY (
				SELECT case when count(*) >0 then 1 else 0 end as 'flagEvalAnswers' FROM ANSWERS 
				WHERE EvaluationID=Ev.EvaluationID
				) EvalAnswers
			OUTER APPLY
			(
				SELECT TOP 1
				CASE
				WHEN state=4 THEN 'Complete as Dotted Line Manager'
				WHEN state=5 THEN 'Complete as Evaluator' END
					 as yourAction, isnull(wrongManager,0) as wrongManager, isnull(state,0) as nstate
				FROM ReportingLine WHERE
				State>=isnull(Ev.State,0)
				and empnotarget=@userid and empnosource=HR.empno
				ORDER BY state asc
			) yourAction
	        WHERE RL.empnotarget=@userid AND ISNULL(RL.excludeFromCycles,0)<>@cycleid --AND RL.state=5
			AND Rl.empnosource NOT IN (SELECT RLE2.empnosource FROM dbo.ReportingLineExceptions RLE2 --exclude employees that are in the ReportingLineException, keep employee in case user is
			INNER JOIN dbo.ReportingLine RL2 ON RL2.empnosource=RLE2.empnosource AND  RLE2.state=5 WHERE RL2.empnotarget=@userid AND RLE2.empnotarget<>@userid AND RLE2.goalCycle=@cycleid)
			UNION
			SELECT EvCycle.ID as CycleID, EvCycle.CycleDescription, EvCycle.goalsInputStatus, Ev.ManagesTeam, RL.empnosource AS Empno,  HR.job_desc,
			rtrim(ltrim(HR.family_name))+' '+rtrim(ltrim(HR.first_name)) as 'employeeName', HR.grade,
			CASE
				WHEN HR.GRADE<4 AND ISNULL(Ev.State, 0)=0 THEN 2 --check it was 1
				ELSE ISNULL(Ev.State, 0)
			END as EvalState,
			Ev.EvaluationID, onBehalf.NoAsnwers as onBehalfFlag, yourAction.nstate as yourActionState, yourAction.yourAction as yourActionStateDescr, isnull(RL.wrongManager,0) as wrongManager,EvalAnswers.flagEvalAnswers
	        FROM dbo.ReportingLineExceptions RL
			LEFT JOIN  dbo.vw_arco_employee HR on HR.empno=RL.empnosource
			LEFT JOIN  dbo.Evaluations Ev on Ev.EmployeeID=RL.empnosource AND Ev.CycleID=@cycleid
			OUTER APPLY(
			SELECT * FROM EvaluationsCycle WHERE ID=@cycleid
			)EvCycle
			OUTER APPLY (
			SELECT case when count(*) >0 then 1 else 0 end as 'NoAsnwers' FROM Evaluations E
			WHERE State=0 AND UserID<>@userid AND CycleID=@cycleid and E.EmployeeID=rl.empnosource
			) onBehalf
			OUTER APPLY (
				SELECT case when count(*) >0 then 1 else 0 end as 'flagEvalAnswers' FROM ANSWERS 
				WHERE EvaluationID=Ev.EvaluationID
				) EvalAnswers
				OUTER APPLY
				(
					SELECT TOP 1
					CASE
						WHEN state=4 THEN 'Complete as Dotted Line Manager'
						WHEN state=5 THEN 'Complete as Evaluator' END
						 as yourAction, isnull(wrongManager,0) as wrongManager, isnull(state,0) as nstate
					FROM ReportingLine WHERE
					State>=isnull(Ev.State,0)
					and empnotarget=@userid and empnosource=HR.empno
					ORDER BY state asc
				) yourAction
	        WHERE RL.empnotarget=@userid --AND RL.state=5 --AND ISNULL(RL.excludeFromCycles,0)<>@cycleid
			ORDER BY HR.grade ASC
		END
		ELSE
		BEGIN
			SELECT EvCycle.ID as CycleID, EvCycle.CycleDescription, EvCycle.goalsInputStatus, Ev.ManagesTeam, RL.empnosource AS Empno,  HR.job_desc,
			rtrim(ltrim(HR.family_name))+' '+rtrim(ltrim(HR.first_name)) as 'employeeName', HR.grade,
			CASE
				WHEN HR.GRADE<4 AND ISNULL(Ev.State, 0)=0 THEN 2 --check it was 1
				ELSE ISNULL(Ev.State, 0)
			END as EvalState,
			Ev.EvaluationID, onBehalf.NoAsnwers as onBehalfFlag, yourAction.nstate as yourActionState, ISNULL(yourAction.yourAction, 'No Action') as yourActionStateDescr, isnull(RL.wrongManager,0) as wrongManager, EvalAnswers.flagEvalAnswers
	        FROM dbo.ReportingLine RL
			LEFT JOIN  dbo.vw_arco_employee HR on HR.empno=RL.empnosource
			LEFT JOIN  dbo.Evaluations Ev on Ev.EmployeeID=RL.empnosource AND Ev.CycleID=@cycleid
			OUTER APPLY(
			SELECT * FROM EvaluationsCycle WHERE ID=@cycleid
			)EvCycle
			OUTER APPLY (
			SELECT case when count(*) >0 then 1 else 0 end as 'NoAsnwers' FROM Evaluations E
			WHERE State=0 AND UserID<>@userid AND CycleID=@cycleid and E.EmployeeID=rl.empnosource
			) onBehalf
			OUTER APPLY (
				SELECT case when count(*) >0 then 1 else 0 end as 'flagEvalAnswers' FROM ANSWERS 
				WHERE EvaluationID=Ev.EvaluationID
				) EvalAnswers
				OUTER APPLY
				(
					SELECT TOP 1
					CASE
						WHEN state=4 THEN 'Complete as Dotted Line Manager'
						WHEN state=5 THEN  'Complete as Evaluator'
						END as yourAction, isnull(wrongManager,0) as wrongManager, isnull(state,0) as nstate
					FROM ReportingLine WHERE
					state>= CASE WHEN ISNULL(Ev.State,0) in (0,1) THEN 4 WHEN ISNULL(Ev.State,0) = 2 THEN 5 END  
					and empnotarget=@userid and empnosource=HR.empno
					ORDER BY state asc
				) yourAction
	        WHERE RL.empnotarget=@userid AND ISNULL(RL.excludeFromCycles,0)<>@cycleid --AND RL.state=5
			AND RL.empnosource NOT IN (SELECT RLE2.empnosource FROM dbo.ReportingLineExceptions RLE2 --exclude employees that are in the ReportingLineException, keep employee in case user is
			INNER JOIN dbo.ReportingLine RL2 ON RL2.empnosource=RLE2.empnosource AND  RLE2.state=5 WHERE RL2.empnotarget=@userid AND RLE2.empnotarget<>@userid AND RLE2.goalCycle=@cycleid)
			ORDER BY HR.grade ASC
		END
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':userid', $userid, PDO::PARAM_STR);
		$query->bindValue(':cycleid', $cycleid, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result["CycleGoals"] = $query->fetchAll();
		return $result;
	}
	
	public function getGoals($empno, $cycleid)
	{
		$queryString="
		SELECT G.GoalID, E.CycleID as CycleID, G.GoalDescription, cast(G.Weight as int) as Weight, G.AttributeCode, GA.CodeDescription, 
		GA.AttDescription as AttributeFullDescription,
		E.EmployeeID as Empno, E.State as EvalState, G.UserID as CreatedByID, 
		RTRIM(ltrim(createdby.family_name))+' '+rtrim(ltrim(createdby.first_name)) as CreatedByName, G.State as GoalState, 
		CASE 
		WHEN G.State=0 THEN 'By Employee' 
		WHEN G.State=1 THEN 'By Dotted'
		WHEN G.State=2 THEN 'By Evaluator'
		END as AddedByRole, ValidateGoalDescription.GoalExists, E.EvaluationID
		FROM dbo.GOALS G
		INNER JOIN Evaluations E ON E.EvaluationID=G.EvaluationID
		INNER JOIN GoalAttributes GA on GA.AttributeCode=G.AttributeCode
		LEFT JOIN dbo.vw_arco_employee createdby on createdby.empno=G.UserID
		OUTER APPLY (
		SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END AS GoalExists  FROM dbo.Goals G2 
		INNER JOIN Evaluations E2 ON E2.EvaluationID=G2.EvaluationID
		WHERE G2.GoalDescription=G.GoalDescription AND G2.State=2
		)ValidateGoalDescription
		WHERE E.CycleID=:cycleid AND E.EmployeeID=:empno
		";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':empno', $empno, PDO::PARAM_STR);
		$query->bindValue(':cycleid', $cycleid, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["EmpGoals"] = $query->fetchAll();
		return $result;
	}

	public function getGoalsHistory($empno, $cycleid)
	{
		$queryString="
		SELECT G.GoalID, E.CycleID as CycleID, G.GoalDescription, cast(G.Weight as int) as Weight, G.AttributeCode, GA.CodeDescription, 
		GA.AttDescription as AttributeFullDescription,
		E.EmployeeID as Empno, E.State as EvalState, G.UserID as CreatedByID, 
		RTRIM(ltrim(createdby.family_name))+' '+rtrim(ltrim(createdby.first_name)) as CreatedByName, G.State as GoalState, 
		CASE 
		WHEN G.State=0 THEN 'By Employee' 
		WHEN G.State=1 THEN 'By Dotted'
		WHEN G.State=2 THEN 'By Evaluator'
		END as AddedByRole, G.Date, E.EvaluationID
		FROM dbo.GoalsHistory G
		INNER JOIN Evaluations E ON E.EvaluationID=G.EvaluationID
		INNER JOIN GoalAttributes GA on GA.AttributeCode=G.AttributeCode
		LEFT JOIN dbo.vw_arco_employee createdby on createdby.empno=G.UserID
		OUTER APPLY (
		SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END AS GoalExists  FROM dbo.Goals G2 
		INNER JOIN Evaluations E2 ON E2.EvaluationID=G2.EvaluationID
		WHERE G2.GoalDescription=G.GoalDescription AND G2.State=2
		)ValidateGoalDescription
		WHERE E.CycleID=:cycleid AND E.EmployeeID=:empno
		ORDER BY G.Date DESC, G.State DESC
		";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':empno', $empno, PDO::PARAM_STR);
		$query->bindValue(':cycleid', $cycleid, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["EmpGoals"] = $query->fetchAll();
		return $result;
	}


    public function getGoalAttributes()
	{
		$queryString="
		SELECT * FROM dbo.GoalAttributes
		";
        $query = $this->connection->prepare($queryString);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["goalAttributes"] = $query->fetchAll();
		return $result;
	}



    public function saveGoals($goal, $empno, $userID, $cycleid)
	{
		$queryString = "
	   DECLARE @cycleid as int=:cycleid;
	   DECLARE @grade as varchar(2);
	   DECLARE @empno as varchar(5)=:empno;
	   DECLARE @userid as varchar(5) =:userid;
	   DECLARE @evalid int;
	   DECLARE @evalstate int;
	   BEGIN
		   SET NOCOUNT ON
		   BEGIN TRY
		   --SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1;
		   SELECT @evalid = EvaluationID, @evalstate=State FROM Evaluations WHERE CycleID=@cycleid AND EmployeeID= @empno;
			   IF (@evalid > 0 AND (ISNULL(@evalstate,0) in (0,1,2))) BEGIN
				   INSERT INTO dbo.Goals
				   OUTPUT INSERTED.EvaluationID
				   VALUES(@evalid, :goaldescr, :weight, @userid, :attributeCode, @evalstate);
			   END
			   IF (ISNULL(@evalid,0)=0) BEGIN
				   BEGIN TRANSACTION;

				   SELECT @grade=grade from vw_arco_employee where empno=@empno;

				   INSERT INTO dbo.Evaluations
				   OUTPUT INSERTED.EvaluationID
				   VALUES (@cycleid,  @empno, @grade, 0, getdate(), 0, @userid, NULL, NULL);

				   SELECT @evalid = EvaluationID, @evalstate=State FROM Evaluations WHERE CycleID=@cycleid AND EmployeeID=@empno;

				   INSERT INTO dbo.Goals
				   VALUES(@evalid, :goaldescr1, :weight1, @userid, :attributeCode1, @evalstate);

				   COMMIT TRANSACTION;
			   END
		   END TRY
		   BEGIN CATCH
			   DECLARE @ErrorMessage NVARCHAR(4000)= ERROR_MESSAGE(), @ErrorSeverity INT= ERROR_SEVERITY(), @ErrorState INT=ERROR_STATE();
			   RAISERROR (@ErrorMessage, @ErrorSeverity, @ErrorState);
			   ROLLBACK TRANSACTION;
			   THROW;
		   END CATCH;
	   END
	   ";
	   $query = $this->connection->prepare($queryString);
	   $query->bindValue(':empno',  $empno, PDO::PARAM_STR);
	   $query->bindValue(':goaldescr', $goal["GoalDescription"], PDO::PARAM_STR);
	   $query->bindValue(':goaldescr1', $goal["GoalDescription"], PDO::PARAM_STR);
	   $query->bindValue(':attributeCode', $goal["attributeCode"], PDO::PARAM_INT);
	   $query->bindValue(':attributeCode1', $goal["attributeCode"], PDO::PARAM_INT);
	   $query->bindValue(':weight', $goal["Weight"], PDO::PARAM_INT);
	   $query->bindValue(':weight1', $goal["Weight"], PDO::PARAM_INT);
	   $query->bindValue(':userid', $userID, PDO::PARAM_STR);
	   $query->bindValue(':cycleid', $cycleid, PDO::PARAM_INT);
	   $result["success"] = $query->execute();
	   $result["errorMessage"] = $query->errorInfo();
	   if ($result["errorMessage"][1]!=null){
		   return $result;
	   }
	   $query->setFetchMode(PDO::FETCH_ASSOC);
	   $evalid = $query->fetch();
	   $result["evalid"]=$evalid["EvaluationID"];
	   return $result;
	}

	 /*****
     *	Clone Selected Goals: Copy goals from employees and dotted line managers and create them as evaluator.
     *
     */
	public function cloneSelectedGoals($goals, $evalid, $userid)
	{

	foreach ($goals as &$goalid) 
		{
		//validate if state has changed
		   $queryString = "
		   Declare @evalid int = :evalid;
		   SELECT count(*) as cnt FROM dbo.Evaluations WHERE EvaluationID=@evalid and State=2";
	   
		   $query = $this->connection->prepare($queryString);
		   $query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
		   if (!$query->execute()){
			   $result["success"] = false;
			   $result["errorMessage"] = $query->errorInfo();
			   return $result;
		   }
		   $query->setFetchMode(PDO::FETCH_ASSOC);
		   $changed = $query->fetch();
		   if ($changed["cnt"]==0){
			   $result["success"] = false;
			   $result["errorMessage"] = $query->errorInfo();
			   $result["message"] = 'Please refresh the page as it seems you are trying to update goals while the state has changed.';
			   return $result;
		   }
	   // Start Cloning	
	   $queryString = "
	   Declare @evalid int = :evalid;
	   Declare @userid varchar(5) = :userid;
	   --Clone Goals
	   INSERT INTO dbo.Goals
	   (
		   EvaluationID,
		   GoalDescription,
		   Weight,
		   UserID,
		   AttributeCode,
		   State
	   )
	   SELECT EvaluationID, GoalDescription, Weight, UserID, AttributeCode, 2 FROM dbo.Goals WHERE GoalID=:goalid
	   ";
	   $query = $this->connection->prepare($queryString);
	   $query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
	   $query->bindValue(':goalid', $goalid, PDO::PARAM_INT);
	   $query->bindValue(':userid', $userid, PDO::PARAM_STR);
	   if (!$query->execute())
		   {
		   $result["success"] = false;
		   $result["errorMessage"] = $query->errorInfo();
		   return $result;
		   }
		}	
		$result["success"] = true;
		return $result;
	}

	 /*****
     * Delete Goal
     *
     */
     public function deleteGoal($goalID){
        $queryString = "
		DELETE G FROM dbo.Goals G INNER JOIN Evaluations E on E.EvaluationID=G.EvaluationID WHERE G.GoalID = :id and E.State<3";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':id', $goalID, PDO::PARAM_INT);
        $result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        return $result;
    }

	 /*****
     *	Update Goal
     *
     */
     public function updateGoal($goal, $userID)
	{
        $queryString = "
		UPDATE G SET G.GoalDescription = :goaldesc, G.weight= :weight, G.AttributeCode=:attributeCode, G.UserID=:userid
		FROM dbo.Goals G
		INNER JOIN Evaluations E on E.EvaluationID=G.EvaluationID
		WHERE G.GoalID= :id AND E.State<3";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':goaldesc', $goal["GoalDescription"], PDO::PARAM_STR);
        $query->bindValue(':attributeCode', $goal["AttributeCode"], PDO::PARAM_INT);
        $query->bindValue(':weight', $goal["Weight"], PDO::PARAM_INT);
        $query->bindValue(':userid', $userID, PDO::PARAM_STR);
        $query->bindValue(':id', $goal["GoalID"], PDO::PARAM_INT);
        $result["success"] = $query->execute();
        $result["errorMessage"] = $query->errorInfo();
		return $result;
	}
	
	/*****
    *	Send Back Goals to user: Send back goals to user for review
    *
    */
    public function sendBackGoals($evalid)
	{
			$queryString = "
			UPDATE E
			SET E.STATE = 0
			FROM dbo.Evaluations E
			LEFT JOIN (select EvaluationID, COUNT(*) as answerCNT
			   from dbo.Answers
			  group by EvaluationID) as A
			on E.EvaluationID = A.EvaluationID  
			WHERE E.EvaluationID=:evalid AND ISNULL(A.answerCNT,0)=0 AND E.State in (1,2,3)
			";
			$query = $this->connection->prepare($queryString);
			$query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
			$result["success"] = $query->execute();
			$result["errorMessage"] = $query->errorInfo();
			return $result;
	}


	/*****
    *	Save Comment
    *
    */
    public function saveComment($evalid, $userid, $state, $comment)
	{
			$queryString = "
			INSERT INTO dbo.EvaluationComments
			VALUES ( :evalid, :userid, GETDATE(), :state, :comment)";
			$query = $this->connection->prepare($queryString);
			$query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
			$query->bindValue(':state', $state, PDO::PARAM_INT);
			$query->bindValue(':userid', $userid, PDO::PARAM_STR);
			$query->bindValue(':comment', $comment, PDO::PARAM_STR);
			$result["success"] = $query->execute();
			$result["errorMessage"] = $query->errorInfo();
			return $result;
	}

	/*****
    *	Get Evaluation's Comments
    *
    */
    public function getComments($evalid)
	{
			$queryString = "
			SELECT CONVERT(DATETIME2(0),EC.CommentDate) AS   
			'CommentDate', EC.State, EC.UserID+' - '+E.first_name+' ' +E.family_name AS 'By', EC.Comment 
			FROM dbo.EvaluationComments EC
			INNER JOIN dbo.vw_arco_employee E ON E.empno=EC.UserID
			WHERE EC.EvaluationID=:evalid
			ORDER BY 1, 2";
			$query = $this->connection->prepare($queryString);
			$query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
			$result["success"] = $query->execute();
			$result["errorMessage"] = $query->errorInfo();
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$result["comments"] = $query->fetchAll();
			return $result;
	}


	/*****
    *	Get CommentsCount
    *
    */
    public function getCommentsCount($evalid)
	{
			$queryString = "
			SELECT COUNT(*) as CommentsCount 
			FROM dbo.EvaluationComments EC
			INNER JOIN dbo.vw_arco_employee E ON E.empno=EC.UserID
			WHERE EC.EvaluationID=:evalid";
			$query = $this->connection->prepare($queryString);
			$query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
			$result["success"] = $query->execute();
			$result["errorMessage"] = $query->errorInfo();
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$result["commentsCount"] = $query->fetch();
			return $result;
	}
} // END OF CLASS

?>
