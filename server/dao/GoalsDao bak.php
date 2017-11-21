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
		$queryString="Declare @evalid int = :evalid;
		SELECT G.GoalID, G.EvaluationID, G.GoalDescription, cast(G.Weight as int) as Weight, G.AttributeCode, GA.CodeDescription, GA.AttDescription as AttributeFullDescription,
				A.Answer as EmpAchievement,
				AE.Answer as EvalAchievement,
				AR.Answer as EvalRevision
				FROM dbo.GOALS G
		        INNER JOIN Evaluations E ON E.EvaluationID=G.EvaluationID
				INNER JOIN GoalAttributes GA on GA.AttributeCode=G.AttributeCode
				LEFT JOIN Answers A ON A.GoalID=G.GoalID AND A.State=1
				LEFT JOIN Answers AE ON AE.GoalID=G.GoalID AND AE.State=2
				LEFT JOIN Answers AR ON AR.GoalID=G.GoalID AND AR.State=4
				WHERE G.EvaluationID=@evalid
		";
        $query = $this->connection->prepare($queryString);
        // $query->bindValue(':userid', $userid, PDO::PARAM_STR);
        // $query->bindValue(':userid1', $userid, PDO::PARAM_STR);
        $query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["goals"] = $query->fetchAll();
		return $result;
	}

	public function getMyGoalsPerCycle($empno)
	{
		$queryString="
		SELECT EC.ID AS CycleID, EC.EvaluationDescription, EC.goalsStatus as CycleGoalSetting, ISNULL(G.Empno, :empno1) AS Empno, ISNULL(G.GoalState, 0) as GoalsState FROM EvaluationsCycle EC
		LEFT JOIN Goals G on G.CycleID=EC.ID AND G.Empno=:empno
		GROUP BY EC.ID, EC.EvaluationDescription, EC.goalsStatus, G.Empno, G.GoalState
		ORDER BY EC.goalsStatus desc
		";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':empno', $empno, PDO::PARAM_STR);
		$query->bindValue(':empno1', $empno, PDO::PARAM_STR);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["CycleGoals"] = $query->fetchAll();
		return $result;
	}

	public function getGoals($empno, $cycleid)
	{
		$queryString="
		SELECT G.GoalID, EC.ID as CycleID, G.GoalDescription, cast(G.Weight as int) as Weight, G.AttributeCode, GA.CodeDescription, GA.AttDescription as AttributeFullDescription, G.Empno, G.GoalState
		FROM dbo.GOALS G
		INNER JOIN EvaluationsCycle EC ON EC.ID=G.CycleID
		INNER JOIN GoalAttributes GA on GA.AttributeCode=G.AttributeCode
		WHERE G.CycleID=:cycleid AND G.Empno=:empno
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
        Declare @cycleid as int;
        Declare @grade as varchar(2);
        Declare @empno as varchar(5);
        Declare @userid as varchar(5);
        SELECT @userid=:userid;
        SELECT @empno=:empno;
        BEGIN
            SET NOCOUNT ON
            BEGIN TRY
            SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1;
            DECLARE @evalid INT = (SELECT EvaluationID FROM Evaluations WHERE CycleID=@cycleid AND EmployeeID= @empno)
                IF (@evalid > 0) BEGIN
                    INSERT INTO dbo.Goals
                    OUTPUT INSERTED.EvaluationID
                    VALUES(@evalid, :goaldescr, :weight, @userid, :attributeCode);
                END
                IF (isnull(@evalid,0)=0) BEGIN
                    BEGIN TRANSACTION;

                    SELECT @grade=grade from vw_arco_employee where empno=@empno;

                    INSERT INTO dbo.Evaluations
                    OUTPUT INSERTED.EvaluationID
                    VALUES (@cycleid,  @empno, @grade, 0, getdate(), 0, @userid);

                    SELECT @evalid = EvaluationID FROM Evaluations WHERE CycleID=@cycleid AND EmployeeID=@empno;

                    INSERT INTO dbo.Goals
                    VALUES(@evalid, :goaldescr1, :weight1, @userid, :attributeCode1);
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

     public function deleteGoal($goalID){
        $queryString = "DELETE FROM dbo.Goals WHERE GoalID = :id";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':id', $goalID, PDO::PARAM_INT);
        $result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        return $result;
    }

     public function updateGoal($goal, $userID)
	{
        $queryString = "UPDATE dbo.Goals SET GoalDescription = :goaldesc, weight= :weight, AttributeCode=:attributeCode, UserID=:userid
        WHERE GoalID= :id";
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

	public function updateGoalState($cycleid, $empno, $state)
   {
	   $queryString = "UPDATE dbo.Goals SET GoalState = :state
	   WHERE CycleID= :cycldeid AND Empno=:empno;";
	   $query = $this->connection->prepare($queryString);
	   $query->bindValue(':cycldeid', $cycleid, PDO::PARAM_INT);
	   $query->bindValue(':state', $state, PDO::PARAM_INT);
	   $query->bindValue(':empno', $empno, PDO::PARAM_STR);
	   $result["success"] = $query->execute();
	   $result["errorMessage"] = $query->errorInfo();
	   return $result;
   }

} // END OF CLASS

?>
