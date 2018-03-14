<?php

class AdminDAO{

	private $connection = NULL;


	public function __construct($conn)
	{
		$this->connection = $conn;
	}

	/*****
	 *	Get Evaluations Available for updates.
	 *
	 */
	public function getActiveCycles()
	{
		$queryString="
		SELECT ID AS CycleID, CycleDescription FROM EvaluationsCycle WHERE questionaireInputStatus=1 or goalsInputStatus=1 ORDER BY ID
		";
		$query = $this->connection->prepare($queryString);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result["activeCycles"] = $query->fetchAll();
		return $result;
	}

	/*****
	 *	Get Available projects for input
	 *
	 */
	public function getProjects()
	{
		$queryString="
		SELECT DISTINCT EMP.pay_cs as projectCode, EMP.site_desc as projectDesc FROM dbo.ReportingLine RL
		INNER JOIN dbo.vw_arco_employee EMP ON EMP.empno=RL.empnosource
		";
		$query = $this->connection->prepare($queryString);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result["projectsList"] = $query->fetchAll();
		return $result;
	}

    /*****
     *	Get pending evaluations based on reporting line
     *
     */
    public function getReportingLine ($filters)
	{
        $queryString="
		DECLARE @sql NVARCHAR(max);
 		DECLARE @ParmDefinition NVARCHAR(max);
 		DECLARE @empid NVARCHAR(50)=:empid, @evaluatorid NVARCHAR(5)=:evaluatorid, @dottedid NVARCHAR(5)=:dottedid, @projectcode NVARCHAR(5)=:projectcode,
		@wrongmanager varchar(2) =:wrongmanager, @isactive varchar(3) =:isactive

		SELECT @sql=N'
		Declare @currentcycleid int, @nextcycleid int, @nextcycleDesc as varchar(50);
		SELECT @currentcycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		SELECT @nextcycleid = ID, @nextcycleDesc=CycleDescription FROM EvaluationsCycle WHERE status=0 and questionaireInputStatus=0 and goalsInputStatus=1;
	    SELECT emp.empno as EmpNo, RTRIM(LTRIM(emp.family_name))+'' ''+RTRIM(LTRIM(emp.first_name)) As EmpName,
		emp.empCategory as Category, emp.empstatus as EmployeeAhrisStatus,  emp.family_code as FamilyCode, emp.family_desc as FamilyDesc,
		emp.section_code as SectionCode,emp.section_desc as SectionDesc,
		emp.post_title_code as PositionCode, emp.job_desc as PositionDesc , emp.region, emp.pay_cs as ProjectCode, emp.site_desc as ProjectDesc, emp.grade, emp.groupYears,

		CASE WHEN nextevalperiod.empno = '' '' THEN @nextcycleid ELSE '' '' END AS CycleExclude, 
		CASE WHEN nextevalperiod.empno = '' '' THEN @nextcycleDesc ELSE '' '' END AS CycleDescription,
		
		eval.empno AS EvaluatorNumber,
		RTRIM(LTRIM(eval.family_name))+'' ''+RTRIM(LTRIM(eval.first_name)) AS EvaluatorName,
		eval.empstatus AS EvaluatorAhrisStatus,
		RL.wrongManager AS ReportedWrongEvaluator,

		nextevalperiod.empno AS NextEvaluationEvaluatorNumber,
		nextevalperiod.Name AS NextEvaluationEvaluatorName,
		nextevalperiod.wrongmanager AS NextEvaluationReportedWrongEvaluator,

		Dot1.empno AS Dotted1Empno, 
		Dot1.Dotted1Name AS Dotted1Name, 
		Dot1.empstatus AS dotted1AhrisStatus,
		Dot1.wrongManager AS ReportedWrongDot1 ,

		nextDot1.empno AS NextDotted1Empno, 
		nextDot1.DottedName AS NextDotted1Name, 
		nextDot1.empstatus AS NextDotted1empstatus, 
		nextDot1.wrongManager AS NextReportedWrongDot1, 

		Dot2.empno AS Dotted2Empno, 
		Dot2.Dotted2Name AS Dotted2Name, 
		Dot2.empstatus AS dotted2AhrisStatus,
		Dot2.wrongManager AS ReportedWrongDot2 ,

		nextDot2.empno AS NextDotted2Empno, 
		nextDot2.DottedName AS NextDotted2Name, 
		nextDot2.empstatus AS NextDotted2empstatus, 
		nextDot2.wrongManager AS NextReportedWrongDot2, 

		Dot3.empno AS Dotted3Empno, 
		Dot3.Dotted3Name AS Dotted3Name, 
		Dot3.empstatus AS dotted3AhrisStatus,
		Dot3.wrongManager AS ReportedWrongDot3 ,

		nextDot3.empno AS NextDotted3Empno, 
		nextDot3.DottedName AS NextDotted3Name, 
		nextDot3.empstatus AS NextDotted3empstatus, 
		nextDot3.wrongManager AS NextReportedWrongDot3 
		FROM dbo.ReportingLine RL
		INNER JOIN [dbo].[vw_arco_employee] emp on emp.empno=RL.empnosource 
		INNER JOIN [dbo].[vw_arco_employee] eval on eval.empno=RL.empnotarget

		OUTER APPLY (
		SELECT  TOP 1 empnotarget, empnotarget AS empno, RTRIM(LTRIM(emp1.family_name))+'' ''+RTRIM(LTRIM(emp1.first_name)) As Name, emp1.empstatus, RLE.wrongManager
			FROM dbo.ReportingLine RLE
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=RLE.empnotarget AND RLE.state=5
			where RLE.state=5 and RLE.empnosource=RL.empnosource AND RLE.cycleid=@nextcycleid
		)
		nextevalperiod

		OUTER APPLY (
		SELECT  TOP 1 empnotarget, empnotarget AS empno, RTRIM(LTRIM(emp1.family_name))+'' ''+RTRIM(LTRIM(emp1.first_name)) As Dotted1Name, emp1.empstatus, dot1.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot1
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=dot1.empnotarget AND dot1.state=4
			where dot1.state=4 and dot1.empnosource=RL.empnosource and dot1.cycleid=@currentcycleid
			ORDER BY Rownumber
		)
		Dot1

		OUTER APPLY (
		SELECT  empnotarget AS empno,RTRIM(LTRIM(emp2.family_name))+'' ''+RTRIM(LTRIM(emp2.first_name)) As Dotted2Name, emp2.empstatus, dot2.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot2
			inner JOIN [dbo].[vw_arco_employee] emp2 on emp2.empno=dot2.empnotarget AND dot2.state=4
			where dot2.state=4 and dot2.empnosource=RL.empnosource AND dot2.cycleid=@currentcycleid
			ORDER BY Rownumber
			OFFSET 1 ROW
			FETCH NEXT 1 ROW ONLY
		)
		Dot2
		OUTER APPLY (
		SELECT  empnotarget AS empno,RTRIM(LTRIM(emp3.family_name))+'' ''+RTRIM(LTRIM(emp3.first_name)) As Dotted3Name, emp3.empstatus, dot3.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot3
			inner JOIN [dbo].[vw_arco_employee] emp3 on emp3.empno=dot3.empnotarget AND dot3.state=3
			where dot3.state=3 and dot3.empnosource=RL.empnosource AND dot3.cycleid=@currentcycleid
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
		)
		Dot3
		OUTER APPLY (
		SELECT  TOP 1 ndot1.empnotarget, ndot1.empnotarget AS empno, RTRIM(LTRIM(dotemp1.family_name))+'' ''+RTRIM(LTRIM(dotemp1.first_name)) As DottedName, dotemp1.empstatus, ndot1.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot1.empnotarget) AS Rownumber
			FROM ReportingLine ndot1
			inner JOIN [dbo].[vw_arco_employee] dotemp1 on dotemp1.empno=ndot1.empnotarget AND ndot1.state=4
			where ndot1.state=4 and ndot1.empnosource=RL.empnosource AND ndot1.cycleid=@nextcycleid
			ORDER BY Rownumber
		)
		nextDot1
		OUTER APPLY (
		SELECT ndot2.empnotarget, ndot2.empnotarget AS empno, RTRIM(LTRIM(dotemp2.family_name))+'' ''+RTRIM(LTRIM(dotemp2.first_name)) As DottedName, dotemp2.empstatus, ndot2.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot2.empnotarget) AS Rownumber
			FROM ReportingLine ndot2
			inner JOIN [dbo].[vw_arco_employee] dotemp2 on dotemp2.empno=ndot2.empnotarget AND ndot2.state=4
			where ndot2.state=4 and ndot2.empnosource=RL.empnosource AND ndot2.cycleid=@nextcycleid
			ORDER BY Rownumber
			OFFSET 1 ROW
			FETCH NEXT 1 ROW ONLY
		)
		nextDot2
		OUTER APPLY (
		SELECT ndot3.empnotarget, ndot3.empnotarget AS empno, RTRIM(LTRIM(dotemp3.family_name))+'' ''+RTRIM(LTRIM(dotemp3.first_name)) As DottedName, dotemp3.empstatus, ndot3.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot3.empnotarget) AS Rownumber
			FROM ReportingLine ndot3
			inner JOIN [dbo].[vw_arco_employee] dotemp3 on dotemp3.empno=ndot3.empnotarget AND ndot3.state=4
			where ndot3.state=4 and ndot3.empnosource=RL.empnosource AND ndot3.cycleid=@nextcycleid
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
		)
		nextDot3
	    WHERE RL.State=5 and RL.cycleid=@currentcycleid
	   '
 		SET @ParmDefinition = N'@empid NVARCHAR(5), @evaluatorid NVARCHAR(5), @dottedid NVARCHAR(5), @projectcode NVARCHAR(5),@wrongmanager varchar(2), @isactive varchar(3)'

 		--main filters
 		IF @empid IS NOT NULL AND @empid <> ''
 		BEGIN
 			 SELECT @sql = @sql + ' AND (RL.empnosource=@empid or emp.family_name like ''%'+@empid+'%'' or emp.first_name like ''%'+@empid+'%'') '
 		END

 		IF @evaluatorid IS NOT NULL AND @evaluatorid<>''
 		BEGIN
 			 SELECT @sql = @sql + ' AND (RL.empnotarget=@evaluatorid or nextevalperiod.empno=@evaluatorid)'
 		END
 		IF @dottedid IS NOT NULL AND  @dottedid<>''
 		BEGIN
			  SELECT @sql = @sql + ' AND (dot1.empno =@dottedid or dot2.empno=@dottedid or dot3.empno=@dottedid 
			  or nextDot1.empno=@dottedid or nextDot2.empno=@dottedid or nextDot3.empno=@dottedid)'
 		END
 		IF @projectcode IS NOT NULL AND @projectcode<>''
 		BEGIN
 			 SELECT @sql = @sql + ' AND emp.pay_cs=@projectcode'
 		END

		IF @isactive IS NOT NULL AND @isactive <> ''
 		BEGIN
 			 SELECT @sql = CASE WHEN @isactive='Yes' THEN
			 @sql + ' AND emp.empstatus=''A'''
			ELSE
			 @sql + ' AND emp.empstatus=''I'''
 			END
 		END

		IF @wrongmanager IS NOT NULL AND @wrongmanager <> ''
 		BEGIN
 			 SELECT @sql = @sql + ' AND (RL.wrongmanager=@wrongmanager or dot1.wrongmanager=@wrongmanager or dot2.wrongmanager=@wrongmanager or dot3.wrongmanager=@wrongmanager or nextevalperiod.wrongmanager=@wrongmanager)'
 		END
 		SELECT @sql= @sql+'  ORDER BY RL.empnosource asc';
 		EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @empid =@empid, @evaluatorid=@evaluatorid, @dottedid=@dottedid, @projectcode=@projectcode,@wrongmanager=@wrongmanager, @isactive=@isactive
	 
		 ";
 	$query = $this->connection->prepare($queryString);
 	$query->bindValue(':empid', $filters['empid'], PDO::PARAM_STR);
 	$query->bindValue(':evaluatorid', $filters['evaluatorid'], PDO::PARAM_STR);
 	$query->bindValue(':dottedid', $filters['dottedid'], PDO::PARAM_STR);
 	$query->bindValue(':projectcode', $filters['projectcode'], PDO::PARAM_STR);
 	$query->bindValue(':wrongmanager', $filters['wrongmanager'], PDO::PARAM_STR);
	$query->bindValue(':isactive', $filters['isactive'], PDO::PARAM_STR);
 	$result["success"] = $query->execute();
 	$result["errorMessage"] = $query->errorInfo();
 	$query->setFetchMode(PDO::FETCH_ASSOC);
 	$result["reportingLine"] = $query->fetchAll();
	return $result;
	}

	/*****
	 *	Get User Evaluations.
	 *
	 */
	public function getEmployeeEvaluations($empid)
	{
		$queryString="
		Declare @currentcycleid int, @nextcycleid int;
		SELECT @currentcycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		SELECT @nextcycleid = ID FROM EvaluationsCycle WHERE status=0 and questionaireInputStatus=0 and goalsInputStatus=1;
		SELECT E.EvaluationID, EC.ID, EC.CycleDescription as 'EvaluationPeriod', E.State, SR.StateDescription, CONVERT(DATETIME2(0),E.StateDate) as StateDate, E.ManagesTeam, E.UserID AS CreatedByID,
		RTRIM(LTRIM(u.family_name))+' '+RTRIM(LTRIM(u.first_name)) As CreatedByName, Goals.noOfGoalsSet, CurrentStateAnswers.count as currentStateAnswersCount
		FROM dbo.EvaluationsCycle EC
		LEFT JOIN dbo.Evaluations E ON e.CycleID=EC.ID  AND e.EmployeeID=:empid
		LEFT JOIN dbo.vw_arco_employee emp ON e.EmployeeID=emp.empno
		LEFT JOIN dbo.StateRef SR ON SR.State=E.State
		LEFT JOIN dbo.vw_arco_employee u ON u.empno=e.UserID
		OUTER APPLY(
		SELECT COUNT(*) AS noOfGoalsSet FROM GOALS WHERE EvaluationID=e.EvaluationID
		)Goals
		OUTER APPLY(
		SELECT COUNT(*) AS count FROM dbo.Answers WHERE EvaluationID=e.EvaluationID AND State=e.State
		)CurrentStateAnswers
		WHERE EC.ID in (@currentcycleid,@nextcycleid)
		ORDER BY EC.ID ASC
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':empid', $empid, PDO::PARAM_STR);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result["employeeEvaluations"] = $query->fetchAll();
		return $result;
	}

	/*****
	 *	Reset Last State.
	 *
	 */
	public function resetLastState($evalid, $userid)
	{
		$queryString="
		DECLARE @evalid as int=:evalid, @userid as varchar(5)=:userid, @empid as  varchar(5), @currentState as int, @newState as int;
		
		--Define New State
		SELECT @empid=E.EmployeeID, @currentState=E.State, 
		@newState=CASE
		WHEN E.empGrade<4 AND E.State=5 THEN 2 --Sent Directly to Goals Intial Step for evaluator
		WHEN E.State in (1,2,3,4,5,6,7) THEN E.State-1
		END
	  	FROM dbo.Evaluations E WHERE E.EvaluationID=@evalid

		--Delete Current State Data
		DELETE FROM dbo.Answers WHERE EvaluationID = @evalid AND State=@currentState;
		DELETE FROM dbo.DevelopmentPlan WHERE EvaluationID = @evalid AND State=@currentState;
		DELETE FROM dbo.EvaluationScores WHERE EvaluationID = @evalid AND State=@currentState;
			
		--Update Evaluation State, and asnwers finished new state
		UPDATE dbo.Evaluations SET State = @newState
		WHERE EvaluationID = @evalid

		UPDATE dbo.Answers Set Finished=0
		WHERE EvaluationID = @evalid AND State=@newState

		-- create audit log 3 is step back
		INSERT INTO AuditEvals values (@evalid, @empid, @userid, 3, getdate(), @currentState, @newState )
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':userid', $userid, PDO::PARAM_STR);
		$query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
		if (!$query->execute()){
			$result["success"] = false;
			$result["errorMessage"] = $query->errorInfo();
			return $result;
		}
		
		$queryString="
		Declare @currentcycleid int, @nextcycleid int, @empid as varchar(5), @evalid as int=:evalid;
		SELECT TOP 1 @empid=EmployeeID FROM AuditEvals WHERE EvaluationID=@evalid 
		SELECT @evalid=CASE WHEN (SELECT COUNT(*) FROM dbo.Evaluations WHERE EvaluationID=@evalid)=0 THEN 0 ELSE @evalid END
		SELECT @currentcycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		SELECT @nextcycleid = ID FROM EvaluationsCycle WHERE status=0 and questionaireInputStatus=0 and goalsInputStatus=1;
		SELECT E.EvaluationID, EC.ID, EC.CycleDescription as 'EvaluationPeriod', E.State, SR.StateDescription, CONVERT(DATETIME2(0),E.StateDate)  as StateDate, E.ManagesTeam, E.UserID AS CreatedByID,
		RTRIM(LTRIM(u.family_name))+' '+RTRIM(LTRIM(u.first_name)) As CreatedByName, Goals.noOfGoalsSet, CurrentStateAnswers.count as currentStateAnswersCount
		 FROM dbo.EvaluationsCycle EC
		LEFT JOIN dbo.Evaluations E ON e.CycleID=EC.ID  AND e.EmployeeID=@empid
		LEFT JOIN dbo.vw_arco_employee emp ON e.EmployeeID=emp.empno
		LEFT JOIN dbo.StateRef SR ON SR.State=E.State
		LEFT JOIN dbo.vw_arco_employee u ON u.empno=e.UserID
		OUTER APPLY(
		SELECT COUNT(*) AS noOfGoalsSet FROM GOALS WHERE EvaluationID=e.EvaluationID
		)Goals
		OUTER APPLY(
		SELECT COUNT(*) AS count FROM dbo.Answers WHERE EvaluationID=e.EvaluationID AND State=e.State
		)CurrentStateAnswers
		WHERE ISNULL(e.EvaluationID,0) = ISNULL(@evalid,0)
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result["evaluation"] = $query->fetch();
		return $result;
	}


	/*****
	 *	Reset Evaluations.
	 *
	 */
	public function resetEmployeeEvaluation($evalid, $userid, $resetgoals)
	{
		$queryString="
		DECLARE @evalid as int=:evalid, @userid as varchar(5)=:userid, @resetgoals as int=:resetgoals, @empid as  varchar(5), @currentState as int, @newState as int;
		
		SELECT @empid=EmployeeID, @currentState=State FROM dbo.Evaluations WHERE EvaluationID=@evalid

		DELETE FROM dbo.Answers WHERE EvaluationID = @evalid;
		DELETE FROM dbo.DevelopmentPlan WHERE EvaluationID = @evalid
		DELETE FROM dbo.EvaluationScores WHERE EvaluationID = @evalid

		IF @resetgoals=1
		BEGIN
			DELETE FROM dbo.Goals WHERE EvaluationID = @evalid
			DELETE FROM dbo.Evaluations WHERE EvaluationID=@evalid
			Select @newState=0;
		END

		IF @resetgoals<>1
		BEGIN
			UPDATE dbo.Evaluations SET @newState = State = CASE WHEN empGrade<5 Then 5 ELSE 2 END
			WHERE EvaluationID = @evalid
		END
		-- create audit for evaluations
		INSERT INTO AuditEvals values (@evalid, @empid, @userid, @resetgoals, getdate(), @currentState, @newState )
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':userid', $userid, PDO::PARAM_STR);
		$query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
		$query->bindValue(':resetgoals', $resetgoals, PDO::PARAM_INT);
		if (!$query->execute()){
			$result["success"] = false;
			$result["errorMessage"] = $query->errorInfo();
			return $result;
		}
		
		$queryString="
		Declare @currentcycleid int, @nextcycleid int, @empid as varchar(5), @evalid as int=:evalid;
		SELECT TOP 1 @empid=EmployeeID FROM AuditEvals WHERE EvaluationID=@evalid 
		SELECT @evalid=CASE WHEN (SELECT COUNT(*) FROM dbo.Evaluations WHERE EvaluationID=@evalid)=0 THEN 0 ELSE @evalid END
		SELECT @currentcycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		SELECT @nextcycleid = ID FROM EvaluationsCycle WHERE status=0 and questionaireInputStatus=0 and goalsInputStatus=1;
		SELECT E.EvaluationID, EC.ID, EC.CycleDescription as 'EvaluationPeriod', E.State, SR.StateDescription, CONVERT(DATETIME2(0),E.StateDate)  as StateDate, E.ManagesTeam, E.UserID AS CreatedByID,
		RTRIM(LTRIM(u.family_name))+' '+RTRIM(LTRIM(u.first_name)) As CreatedByName, Goals.noOfGoalsSet, CurrentStateAnswers.count as currentStateAnswersCount
		 FROM dbo.EvaluationsCycle EC
		LEFT JOIN dbo.Evaluations E ON e.CycleID=EC.ID  AND e.EmployeeID=@empid
		LEFT JOIN dbo.vw_arco_employee emp ON e.EmployeeID=emp.empno
		LEFT JOIN dbo.StateRef SR ON SR.State=E.State
		LEFT JOIN dbo.vw_arco_employee u ON u.empno=e.UserID
		OUTER APPLY(
		SELECT COUNT(*) AS noOfGoalsSet FROM GOALS WHERE EvaluationID=e.EvaluationID
		)Goals
		OUTER APPLY(
		SELECT COUNT(*) AS count FROM dbo.Answers WHERE EvaluationID=e.EvaluationID AND State=e.State
		)CurrentStateAnswers
		WHERE ISNULL(e.EvaluationID,0) = ISNULL(@evalid,0)
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result["evaluation"] = $query->fetch();
		return $result;
	}

	/*****
	 *	Delete Employee from reporting line.
	 *
	 */
	public function removeEmployeeFromApp($filters)
	{
		$queryString="
		DECLARE @empno as varchar(5)=:empno, @userid as varchar(5)=:userid;
		Declare @currentcycleid int;
		DECLARE @evalid as int = (SELECT EvaluationID FROM dbo.Evaluations WHERE EmployeeID=@empno AND cycleid=@currentcycleid)
		SELECT @currentcycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		--delete user from all tables.
		DELETE FROM dbo.Answers WHERE EvaluationID = @evalid;
		DELETE FROM dbo.DevelopmentPlan WHERE EvaluationID = @evalid;
		DELETE FROM dbo.EvaluationScores WHERE EvaluationID = @evalid;
		DELETE FROM dbo.Goals WHERE EvaluationID = @evalid;
		DELETE FROM dbo.GoalsHistory WHERE EvaluationID = @evalid;
		DELETE FROM dbo.Evaluations WHERE EmployeeID=@empno AND EvaluationID = @evalid ;
		DELETE FROM dbo.ReportingLine WHERE empnosource=@empno AND cycleid=@currentcycleid;
		-- create audit for evaluations
		INSERT INTO AuditEvals values (0, @empno, @userid, 2, getdate(), 0, 0)
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':userid', $filters['userid'], PDO::PARAM_STR);
		$query->bindValue(':empno', $filters['EmpNo'], PDO::PARAM_STR);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		return $result;
	}


	/*****
     *	Get pending evaluations based on reporting line
     *
     */
    public function getLocalUsers ($filters)
	{
        $queryString="
		DECLARE @sql NVARCHAR(max);
 		DECLARE @ParmDefinition NVARCHAR(max);
 		DECLARE @empid NVARCHAR(5)=:empid, @islocal int=:islocal
		--DECLARE @empid NVARCHAR(5)='', @islocal INT=0
		SELECT @sql=N'
		SELECT emp.empno, RTRIM(LTRIM(emp.family_name))+'' ''+RTRIM(LTRIM(emp.first_name)) As EmpName, emp.job_desc as PositionDesc, emp.grade,emp.empstatus as EmployeeAhrisStatus,
		emp.emailaddress, ad.sAMAccountName AS directoryAccount, u.email AS localUserAccountName, isnull(u.isInactive,0) as arcopmAcountStatus
		FROM dbo.vw_arco_employee emp
		LEFT JOIN dbo.Users u ON emp.empno=u.empno
		LEFT JOIN dbo.vw_ADUsers ad ON ad.EmployeeID=emp.empno
		WHERE emp.empstatus=''A''
	   '
 		SET @ParmDefinition = N'@empid NVARCHAR(5)'

 		--main filters
 		IF @empid IS NOT NULL AND @empid <> ''
 		BEGIN
		    SELECT @sql = @sql + ' AND (emp.empno=@empid or emp.family_name like ''%'+@empid+'%'' or emp.first_name like ''%'+@empid+'%'') '
 		END
		IF @islocal IS NOT NULL AND @islocal =1
 		BEGIN
 			 SELECT @sql = @sql + ' AND isnull(u.email,'''')<>'''''
 		END
 		SELECT @sql= @sql+'  ORDER BY emp.empno asc';
 		EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @empid =@empid
 	";
 	$query = $this->connection->prepare($queryString);
 	$query->bindValue(':empid', $filters['empid'], PDO::PARAM_STR);
	$query->bindValue(':islocal', $filters['islocal'], PDO::PARAM_INT);
 	$result["success"] = $query->execute();
 	$result["errorMessage"] = $query->errorInfo();
 	$query->setFetchMode(PDO::FETCH_ASSOC);
 	$result["localUsersList"] = $query->fetchAll();
	return $result;
	}

	/*****
	*	Update Local Users. Either insert or update arcopm user table.
	*
	*/
	public function updateUsers($empno, $userID, $Password, $loggedinid, $isinactive )
   {

		   $queryString = "
		   Declare @userid as varchar(100)=:userid, @empno as varchar(5)=:empno, @loggedinid as varchar(5)=:loggedinid, @isinactive as int=:isinactive;
		   UPDATE Users SET email=@userid,";
		   if($Password!=='')
		    	{
					$queryString.="password=HashBytes('SHA1', '".$Password."'), ";
				}
		   $queryString.="
		   isInactive=@isinactive
   		   WHERE empno=@empno;

		   IF @@ROWCOUNT = 0
				BEGIN
					INSERT INTO dbo.Users VALUES(@empno, @userid,HashBytes('SHA1', '".$Password."'), @loggedinid, @isinactive);
				END
		   ";
		   $query = $this->connection->prepare($queryString);
		   $query->bindValue(':empno', $empno, PDO::PARAM_STR);
		   $query->bindValue(':loggedinid', $loggedinid, PDO::PARAM_STR);
		   $query->bindValue(':userid', $userID, PDO::PARAM_STR);
		   $query->bindValue(':isinactive', $isinactive, PDO::PARAM_INT);
		   $result["success"] = $query->execute();
		   $result["errorMessage"] = $query->errorInfo();
		   return $result;
   }


	/*****
	 *	Update Reporting line
	 *
	 */
	public function updateReportingLine($settings)
	{

		//validate: check if employee codes sent exist
		$queryString = "
		Declare @empid varchar(5) = :empid;
		Declare @currenteval varchar(5) = :currenteval;
		Declare @nexteval varchar(5) = :nexteval;
		Declare @dot1 varchar(5) = :dot1;
		Declare @dot2 varchar(5) = :dot2;
		Declare @dot3 varchar(5) = :dot3;
		Declare @ndot1 varchar(5) = :ndot1;
		Declare @ndot2 varchar(5) = :ndot2;
		Declare @ndot3 varchar(5) = :ndot3;
		--validation of employee numbers provided
		DECLARE @wrongemp as varchar(100) = '';
		IF (SELECT COUNT(*) FROM dbo.vw_arco_employee WHERE empno=@empid) =0
		BEGIN
			SELECT @wrongemp+=' '+@empid
		END
		IF (SELECT COUNT(*) FROM dbo.vw_arco_employee WHERE empno=@currenteval) =0 AND @currenteval<>''
		BEGIN
			SELECT @wrongemp+=' '+@currenteval
		END
		IF (SELECT COUNT(*) FROM dbo.vw_arco_employee WHERE empno=@nexteval) =0 AND @nexteval<>''
		BEGIN
			SELECT @wrongemp+=' '+@nexteval
		END
		IF (SELECT COUNT(*) FROM dbo.vw_arco_employee WHERE empno=@dot1) =0 AND @dot1<>''
		BEGIN
			SELECT @wrongemp+=' '+@dot1
		END
		IF (SELECT COUNT(*) FROM dbo.vw_arco_employee WHERE empno=@dot2) =0 AND @dot2<>''
		BEGIN
			SELECT @wrongemp+=' '+@dot2
		END
		IF (SELECT COUNT(*) FROM dbo.vw_arco_employee WHERE empno=@dot3) =0 AND @dot3<>''
		BEGIN
			SELECT @wrongemp+=' '+@dot3
		END
		IF (SELECT COUNT(*) FROM dbo.vw_arco_employee WHERE empno=@ndot1) =0 AND @ndot1<>''
		BEGIN
			SELECT @wrongemp+=' '+@ndot1
		END
		IF (SELECT COUNT(*) FROM dbo.vw_arco_employee WHERE empno=@ndot2) =0 AND @ndot2<>''
		BEGIN
			SELECT @wrongemp+=' '+@ndot2
		END
		IF (SELECT COUNT(*) FROM dbo.vw_arco_employee WHERE empno=@ndot3) =0 AND @ndot3<>''
		BEGIN
			SELECT @wrongemp+=' '+@ndot3
		END
		SELECT @wrongemp as 'error';
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':empid', $settings["EmpNo"], PDO::PARAM_STR);
		$query->bindValue(':currenteval', $settings["EvaluatorNumber"], PDO::PARAM_STR);
		$query->bindValue(':nexteval', $settings["NextEvaluationEvaluatorNumber"], PDO::PARAM_STR);
		$query->bindValue(':dot1', $settings["Dotted1Empno"], PDO::PARAM_STR);
		$query->bindValue(':dot2', $settings["Dotted2Empno"], PDO::PARAM_STR);
		$query->bindValue(':dot3', $settings["Dotted3Empno"], PDO::PARAM_STR);
		$query->bindValue(':ndot1', $settings["NextDotted1Empno"], PDO::PARAM_STR);
		$query->bindValue(':ndot2', $settings["NextDotted2Empno"], PDO::PARAM_STR);
		$query->bindValue(':ndot3', $settings["NextDotted3Empno"], PDO::PARAM_STR);
		if (!$query->execute()){
			$result["success"] = false;
			$result["errorMessage"] = $query->errorInfo();
			return $result;
		}
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$error = $query->fetch();
		if ($error["error"]<>''){
	        $result["success"] = false;
	        $result["errorMessage"] = $query->errorInfo();
	        $result["message"] = 'The following employee codes do not exist in AHRIS:'.$error["error"];
	        return $result;
	    }

		//validate: check if there are evaluations or goals started and at which state so to allow the update.
		$queryString = "
		Declare @empid varchar(5) = :empid;
		Declare @currenteval varchar(5) = :currenteval;
		Declare @nexteval varchar(5) = :nexteval;
		Declare @dot1 varchar(5) = :dot1;
		Declare @dot2 varchar(5) = :dot2;
		Declare @dot3 varchar(5) = :dot3;
		Declare @ndot1 varchar(5) = :ndot1;
		Declare @ndot2 varchar(5) = :ndot2;
		Declare @ndot3 varchar(5) = :ndot3;
		Declare @currentcycleid int, @nextcycleid int;
		Declare @currentcycleDes varchar(10), @nextcycleDes varchar(10);
		SELECT @currentcycleid = ID, @currentcycleDes=CycleDescription FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		SELECT @nextcycleid = ID, @nextcycleDes=CycleDescription FROM EvaluationsCycle WHERE status=0 and questionaireInputStatus=0 and goalsInputStatus=1;

		DECLARE @errormsg AS varchar(700) = '';
		--Change evaluator fist period
		DECLARE @evaluatorfirstp AS varchar(5), @evaluationfirstpState as int, @createdByFirstp as varchar(5);

		SELECT @evaluatorfirstp = RL.empnotarget,  @evaluationfirstpState = ISNULL(E.State,0), @createdByFirstp=ISNULL(UserID, '')
		FROM dbo.ReportingLine RL 
		LEFT JOIN Evaluations E on RL.empnosource=E.EmployeeID AND E.CycleID=@currentcycleid
		WHERE RL.empnosource=@empid AND Rl.state=5 
		AND RL.excludeFromCycles <> @currentcycleid;

		
		IF (@evaluatorfirstp <> @currenteval AND @evaluationfirstpState>0) OR (@evaluationfirstpState=0 AND @createdByFirstp <> '' AND @createdByFirstp<>@empid) 
		BEGIN
			SELECT @errormsg+=' <li>'+RTRIM(@currentcycleDes)+' - conflict on evaluator update, you need to reset it back to the initial stage the Goals Settings & Configuration</li>'
		END

		--Change Dotted first period
		Declare @noofdottedFound as int;
		Declare @noofdottedtoUpdate as int;
		SELECT @noofdottedFound=COUNT(*), @noofdottedtoUpdate=CASE WHEN isnull(@dot1, '')='' THEN 0 ELSE 1 END + CASE WHEN isnull(@dot2, '')='' THEN 0 ELSE 1 END 
		+ CASE WHEN isnull(@dot3, '')='' THEN 0 ELSE 1 END 
		 FROM ReportingLine Where (@dot1=empnotarget or @dot2=empnotarget or @dot3=empnotarget) and empnosource=@empid and state=4 AND excludeFromCycles <> @currentcycleid
		
		IF (@evaluationfirstpState>0 AND @noofdottedFound<>@noofdottedtoUpdate)  
		BEGIN
			SELECT @errormsg+=' <li>'+RTRIM(@currentcycleDes)+' - conflict on dotted line managers update, you need to reset back to the initial stage the Goals Settings & Configuration</li>'
		END


		--Change evaluator next period
		DECLARE @evaluatorNextp AS varchar(5), @evaluationNextpState as int, @createdByNextp as varchar(5);
		
		SELECT @evaluatorNextp = RL.empnotarget,  @evaluationNextpState = ISNULL(E.State,0), @createdByNextp=ISNULL(UserID, '')
		FROM dbo.ReportingLine RL 
		LEFT JOIN Evaluations E on RL.empnosource=E.EmployeeID AND E.CycleID=@nextcycleid
		WHERE RL.empnosource=@empid AND Rl.state=5 
		AND RL.cycleid = @currentcycleid
		--Check if there is ecxeption
		
		SELECT @evaluatorNextp = RL.empnotarget,  @evaluationNextpState = ISNULL(E.State,0), @createdByNextp=ISNULL(UserID, '')
		FROM dbo.ReportingLine RL 
		LEFT JOIN Evaluations E on RL.empnosource=E.EmployeeID AND E.CycleID=@nextcycleid
		WHERE RL.empnosource=@empid AND Rl.state=5 
		AND RL.cycleid = @nextcycleid

		IF (@evaluatorNextp <> @nexteval AND @evaluationNextpState>0) OR (@evaluationNextpState=0 AND @createdByNextp <> '' AND @createdByNextp<>@empid) 
		BEGIN
			SELECT @errormsg+=' <li>'+RTRIM(@nextcycleDes)+' - conflict on evaluator update, you need to reset back to the initial stage the Goals Settings & Configuration</li>'
		END

		--Change dotted next period
		SELECT @noofdottedFound=COUNT(*), @noofdottedtoUpdate=CASE WHEN isnull(@ndot1, '')='' THEN 0 ELSE 1 END + CASE WHEN isnull(@ndot2, '')='' THEN 0 ELSE 1 END 
		+ CASE WHEN isnull(@ndot3, '')='' THEN 0 ELSE 1 END 
		 FROM ReportingLine Where (@ndot1=empnotarget or @ndot2=empnotarget or @ndot3=empnotarget) and empnosource=@empid and state=4  AND cycleid = @currentcycleid
		--check if there is exception
		 SELECT @noofdottedFound=COUNT(*), @noofdottedtoUpdate=CASE WHEN isnull(@ndot1, '')='' THEN 0 ELSE 1 END + CASE WHEN isnull(@ndot2, '')='' THEN 0 ELSE 1 END 
		 + CASE WHEN isnull(@ndot3, '')='' THEN 0 ELSE 1 END 
		  FROM ReportingLine Where (@ndot1=empnotarget or @ndot2=empnotarget or @ndot3=empnotarget) and empnosource=@empid and state=4  AND cycleid = @nextcycleid
		  
		IF (@evaluationNextpState>0 AND @noofdottedFound<>@noofdottedtoUpdate)  
		BEGIN
			SELECT @errormsg+=' <li>'+RTRIM(@nextcycleDes)+' - conflict on dotted line managers update, you need to reset back to the initial stage the Goals Settings & Configuration</li>'
		END
		
		-- Select the error
		SELECT @errormsg as 'error';
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':empid', $settings["EmpNo"], PDO::PARAM_STR);
		$query->bindValue(':currenteval', $settings["EvaluatorNumber"], PDO::PARAM_STR);
		$query->bindValue(':nexteval', $settings["NextEvaluationEvaluatorNumber"], PDO::PARAM_STR);
		$query->bindValue(':dot1', $settings["Dotted1Empno"], PDO::PARAM_STR);
		$query->bindValue(':dot2', $settings["Dotted2Empno"], PDO::PARAM_STR);
		$query->bindValue(':dot3', $settings["Dotted3Empno"], PDO::PARAM_STR);
		$query->bindValue(':ndot1', $settings["NextDotted1Empno"], PDO::PARAM_STR);
		$query->bindValue(':ndot2', $settings["NextDotted2Empno"], PDO::PARAM_STR);
		$query->bindValue(':ndot3', $settings["NextDotted3Empno"], PDO::PARAM_STR);
		if (!$query->execute()){
			$result["success"] = false;
			$result["errorMessage"] = $query->errorInfo();
			return $result;
		}
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$error = $query->fetch();
		if ($error["error"]<>''){
	        $result["success"] = false;
	        $result["errorMessage"] = $query->errorInfo();
	        $result["message"] = 'Your update failed due to the following reasons:<br/><ul>'.$error["error"].'</ul>';
	        return $result;
		}
		
		//do the update
		$queryString = "
		Declare @empid varchar(5) = :empid;
		Declare @currenteval varchar(5) = :currenteval;
		Declare @nexteval varchar(5) = :nexteval;
		Declare @dot1 varchar(5) = ISNULL(:dot1,'');
		Declare @dot2 varchar(5) = ISNULL(:dot2,'');
		Declare @dot3 varchar(5) = ISNULL(:dot3,'');
		Declare @ndot1 varchar(5) = ISNULL(:ndot1,'');
		Declare @ndot2 varchar(5) = ISNULL(:ndot2,'');
		Declare @ndot3 varchar(5) = ISNULL(:ndot3,'');
		Declare @excludecycle int = :cycleexclude;
		Declare @userid varchar(5) = :userid;
		Declare @currentcycleid int, @nextcycleid int;
		SELECT @currentcycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		SELECT @nextcycleid = ID FROM EvaluationsCycle WHERE status=0 and questionaireInputStatus=0 and goalsInputStatus=1;

		--Start the update of the reportingLineTable. --Update, if no record insert.
		--1st update reporting line evaluator. current cycle
		IF @excludecycle <> @currentcycleid
		BEGIN 
			UPDATE ReportingLine set empnotarget=@currenteval, wrongmanager=0, updatedBy=@userid, date=getdate()
			WHERE empnosource=@empid AND State=5 AND cycleid=@currentcycleid
			IF @@ROWCOUNT = 0
				BEGIN
					INSERT INTO dbo.ReportingLine VALUES(@empid, @currenteval,5, @currentcycleid,0, 0, @userid, getdate());
				END

			--2nd Create Dotted. First delete all dotted and then create one by one.
			--Make sure you delete dotted and reset.
			DELETE FROM ReportingLine WHERE empnosource=@empid and State=4 and cycleid=@currentcycleid
			IF isnull(@dot1,'')<>''
				BEGIN
					INSERT INTO dbo.ReportingLine VALUES(@empid, @dot1,4, @currentcycleid, 0, 0, @userid, getdate());
				END
			IF isnull(@dot2,'')<>''
				BEGIN
					INSERT INTO dbo.ReportingLine VALUES(@empid, @dot2,4, @currentcycleid, 0, 0, @userid, getdate());
				END
			IF isnull(@dot3,'')<>''
				BEGIN
					INSERT INTO dbo.ReportingLine VALUES(@empid, @dot3,4, @currentcycleid, 0, 0, @userid, getdate());
				END
		END

		--1st update reporting line evaluator. next cycle
		IF @excludecycle <> @nextcycleid
		BEGIN 
			UPDATE ReportingLine set empnotarget=@nexteval, wrongmanager=0, updatedBy=@userid, date=getdate()
			WHERE empnosource=@empid AND State=5 AND cycleid=@nextcycleid
			IF @@ROWCOUNT = 0
				BEGIN
					INSERT INTO dbo.ReportingLine VALUES(@empid, @nexteval,5, @nextcycleid,0, 0, @userid, getdate());
				END

			--2nd Create Dotted. First delete all dotted and then create one by one.
			--Make sure you delete dotted and reset.
			DELETE FROM ReportingLine WHERE empnosource=@empid and State=4 and cycleid=@nextcycleid
			IF isnull(@dot1,'')<>''
				BEGIN
					INSERT INTO dbo.ReportingLine VALUES(@empid, @ndot1,4, @nextcycleid, 0, 0, @userid, getdate());
				END
			IF isnull(@dot2,'')<>''
				BEGIN
					INSERT INTO dbo.ReportingLine VALUES(@empid, @ndot2,4, @nextcycleid, 0, 0, @userid, getdate());
				END
			IF isnull(@dot3,'')<>''
				BEGIN
					INSERT INTO dbo.ReportingLine VALUES(@empid, @ndot3,4, @nextcycleid, 0, 0, @userid, getdate());
				END
		END
		
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':empid', $settings["EmpNo"], PDO::PARAM_STR);
		$query->bindValue(':currenteval', $settings["EvaluatorNumber"], PDO::PARAM_STR);
		$query->bindValue(':nexteval', $settings["NextEvaluationEvaluatorNumber"], PDO::PARAM_STR);
		$query->bindValue(':dot1', $settings["Dotted1Empno"], PDO::PARAM_STR);
		$query->bindValue(':dot2', $settings["Dotted2Empno"], PDO::PARAM_STR);
		$query->bindValue(':dot3', $settings["Dotted3Empno"], PDO::PARAM_STR);
		$query->bindValue(':ndot1', $settings["NextDotted1Empno"], PDO::PARAM_STR);
		$query->bindValue(':ndot2', $settings["NextDotted2Empno"], PDO::PARAM_STR);
		$query->bindValue(':ndot3', $settings["NextDotted3Empno"], PDO::PARAM_STR);
		$query->bindValue(':cycleexclude', $settings["CycleExclude"], PDO::PARAM_INT);
		$query->bindValue(':userid', $settings["userid"], PDO::PARAM_STR);
		if (!$query->execute()){
			$result["success"] = false;
			$result["errorMessage"] = $query->errorInfo();
			return $result;
			}
		
			//return udpated reporting line 
		$queryString="
		-- Retrieve Updated record in order to provide it back to the client after the update.
		Declare @currentcycleid int, @nextcycleid int;
		SELECT @currentcycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		SELECT @nextcycleid = ID FROM EvaluationsCycle WHERE status=0 and questionaireInputStatus=0 and goalsInputStatus=1;
		SELECT emp.empno as EmpNo, RTRIM(LTRIM(emp.family_name))+'' ''+RTRIM(LTRIM(emp.first_name)) As EmpName,
		emp.empCategory as Category, emp.empstatus as EmployeeAhrisStatus,  emp.family_code as FamilyCode, emp.family_desc as FamilyDesc,
		emp.section_code as SectionCode,emp.section_desc as SectionDesc,
		emp.post_title_code as PositionCode, emp.job_desc as PositionDesc , emp.region, emp.pay_cs as ProjectCode, emp.site_desc as ProjectDesc, emp.grade, emp.groupYears,

		CASE WHEN nextevalperiod.empno = '' '' THEN @nextcycleid ELSE '' '' END AS CycleExclude, 
		CASE WHEN nextevalperiod.empno = '' '' THEN @nextcycleDesc ELSE '' '' END AS CycleDescription,
		
		eval.empno AS EvaluatorNumber,
		RTRIM(LTRIM(eval.family_name))+'' ''+RTRIM(LTRIM(eval.first_name)) AS EvaluatorName,
		eval.empstatus AS EvaluatorAhrisStatus,
		RL.wrongManager AS ReportedWrongEvaluator,

		nextevalperiod.empno AS NextEvaluationEvaluatorNumber,
		nextevalperiod.Name AS NextEvaluationEvaluatorName,
		nextevalperiod.wrongmanager AS NextEvaluationReportedWrongEvaluator,

		Dot1.empno AS Dotted1Empno, 
		Dot1.Dotted1Name AS Dotted1Name, 
		Dot1.empstatus AS dotted1AhrisStatus,
		Dot1.wrongManager AS ReportedWrongDot1 ,

		nextDot1.empno AS NextDotted1Empno, 
		nextDot1.DottedName AS NextDotted1Name, 
		nextDot1.empstatus AS NextDotted1empstatus, 
		nextDot1.wrongManager AS NextReportedWrongDot1, 

		Dot2.empno AS Dotted2Empno, 
		Dot2.Dotted2Name AS Dotted2Name, 
		Dot2.empstatus AS dotted2AhrisStatus,
		Dot2.wrongManager AS ReportedWrongDot2 ,

		nextDot2.empno AS NextDotted2Empno, 
		nextDot2.DottedName AS NextDotted2Name, 
		nextDot2.empstatus AS NextDotted2empstatus, 
		nextDot2.wrongManager AS NextReportedWrongDot2, 

		Dot3.empno AS Dotted3Empno, 
		Dot3.Dotted3Name AS Dotted3Name, 
		Dot3.empstatus AS dotted3AhrisStatus,
		Dot3.wrongManager AS ReportedWrongDot3 ,

		nextDot3.empno AS NextDotted3Empno, 
		nextDot3.DottedName AS NextDotted3Name, 
		nextDot3.empstatus AS NextDotted3empstatus, 
		nextDot3.wrongManager AS NextReportedWrongDot3 
		FROM dbo.ReportingLine RL
		INNER JOIN [dbo].[vw_arco_employee] emp on emp.empno=RL.empnosource 
		INNER JOIN [dbo].[vw_arco_employee] eval on eval.empno=RL.empnotarget

		OUTER APPLY (
		SELECT  TOP 1 empnotarget, empnotarget AS empno, RTRIM(LTRIM(emp1.family_name))+'' ''+RTRIM(LTRIM(emp1.first_name)) As Name, emp1.empstatus, RLE.wrongManager
			FROM dbo.ReportingLine RLE
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=RLE.empnotarget AND RLE.state=5
			where RLE.state=5 and RLE.empnosource=RL.empnosource AND RLE.cycleid=@nextcycleid
		)
		nextevalperiod

		OUTER APPLY (
		SELECT  TOP 1 empnotarget, empnotarget AS empno, RTRIM(LTRIM(emp1.family_name))+'' ''+RTRIM(LTRIM(emp1.first_name)) As Dotted1Name, emp1.empstatus, dot1.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot1
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=dot1.empnotarget AND dot1.state=4
			where dot1.state=4 and dot1.empnosource=RL.empnosource and dot1.cycleid=@currentcycleid
			ORDER BY Rownumber
		)
		Dot1

		OUTER APPLY (
		SELECT  empnotarget AS empno,RTRIM(LTRIM(emp2.family_name))+'' ''+RTRIM(LTRIM(emp2.first_name)) As Dotted2Name, emp2.empstatus, dot2.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot2
			inner JOIN [dbo].[vw_arco_employee] emp2 on emp2.empno=dot2.empnotarget AND dot2.state=4
			where dot2.state=4 and dot2.empnosource=RL.empnosource AND dot2.cycleid=@currentcycleid
			ORDER BY Rownumber
			OFFSET 1 ROW
			FETCH NEXT 1 ROW ONLY
		)
		Dot2
		OUTER APPLY (
		SELECT  empnotarget AS empno,RTRIM(LTRIM(emp3.family_name))+'' ''+RTRIM(LTRIM(emp3.first_name)) As Dotted3Name, emp3.empstatus, dot3.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot3
			inner JOIN [dbo].[vw_arco_employee] emp3 on emp3.empno=dot3.empnotarget AND dot3.state=3
			where dot3.state=3 and dot3.empnosource=RL.empnosource AND dot3.cycleid=@currentcycleid
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
		)
		Dot3
		OUTER APPLY (
		SELECT  TOP 1 ndot1.empnotarget, ndot1.empnotarget AS empno, RTRIM(LTRIM(dotemp1.family_name))+'' ''+RTRIM(LTRIM(dotemp1.first_name)) As DottedName, dotemp1.empstatus, ndot1.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot1.empnotarget) AS Rownumber
			FROM ReportingLine ndot1
			inner JOIN [dbo].[vw_arco_employee] dotemp1 on dotemp1.empno=ndot1.empnotarget AND ndot1.state=4
			where ndot1.state=4 and ndot1.empnosource=RL.empnosource AND ndot1.cycleid=@nextcycleid
			ORDER BY Rownumber
		)
		nextDot1
		OUTER APPLY (
		SELECT ndot2.empnotarget, ndot2.empnotarget AS empno, RTRIM(LTRIM(dotemp2.family_name))+'' ''+RTRIM(LTRIM(dotemp2.first_name)) As DottedName, dotemp2.empstatus, ndot2.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot2.empnotarget) AS Rownumber
			FROM ReportingLine ndot2
			inner JOIN [dbo].[vw_arco_employee] dotemp2 on dotemp2.empno=ndot2.empnotarget AND ndot2.state=4
			where ndot2.state=4 and ndot2.empnosource=RL.empnosource AND ndot2.cycleid=@nextcycleid
			ORDER BY Rownumber
			OFFSET 1 ROW
			FETCH NEXT 1 ROW ONLY
		)
		nextDot2
		OUTER APPLY (
		SELECT ndot3.empnotarget, ndot3.empnotarget AS empno, RTRIM(LTRIM(dotemp3.family_name))+'' ''+RTRIM(LTRIM(dotemp3.first_name)) As DottedName, dotemp3.empstatus, ndot3.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot3.empnotarget) AS Rownumber
			FROM ReportingLine ndot3
			inner JOIN [dbo].[vw_arco_employee] dotemp3 on dotemp3.empno=ndot3.empnotarget AND ndot3.state=4
			where ndot3.state=4 and ndot3.empnosource=RL.empnosource AND ndot3.cycleid=@nextcycleid
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
		)
		nextDot3
	    WHERE RL.State=5 AND RL.cycleid=@currentcycleid AND RL.empnosource=:empid";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':empid', $settings["EmpNo"], PDO::PARAM_STR);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result["empReportingLine"] = $query->fetch();
		return $result;
	}


} // END OF CLASS

?>
