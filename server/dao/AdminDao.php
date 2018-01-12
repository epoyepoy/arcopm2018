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

		--DECLARE @empid NVARCHAR(5)='', @evaluatorid NVARCHAR(5)='', @dottedid NVARCHAR(5)='', @projectcode NVARCHAR(5)='',
		--@wrongmanager VARCHAR(2) ='',@isactive varchar(3) ='No'

		SELECT @sql=N'
		Declare @currentcycleid int, @nextcycleid int;
		SELECT @currentcycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		SELECT @nextcycleid = ID FROM EvaluationsCycle WHERE status=0 and questionaireInputStatus=0 and goalsInputStatus=1;
	    SELECT emp.empno as EmpNo, RTRIM(LTRIM(emp.family_name))+'' ''+RTRIM(LTRIM(emp.first_name)) As EmpName,
		emp.empCategory as Category, emp.empstatus as EmployeeAhrisStatus,  emp.family_code as FamilyCode, emp.family_desc as FamilyDesc,
		emp.section_code as SectionCode,emp.section_desc as SectionDesc,
		emp.post_title_code as PositionCode, emp.job_desc as PositionDesc , emp.region, emp.pay_cs as ProjectCode, emp.site_desc as ProjectDesc, emp.grade, emp.groupYears,

		RL.excludeFromCycles AS CycleExclude, ISNULL(EC.CycleDescription, '' '') AS CycleDescription,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN eval.empno ELSE '''' END AS EvaluatorNumber,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN RTRIM(LTRIM(eval.family_name))+'' ''+RTRIM(LTRIM(eval.first_name)) ELSE '' '' END AS EvaluatorName,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN eval.empstatus ELSE '' '' END AS EvaluatorAhrisStatus,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN RL.wrongManager ELSE '' '' END AS ReportedWrongEvaluator,

		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid THEN eval.empno ELSE '''' END ELSE nextevalperiod.empno END AS NextEvaluationEvaluatorNumber,
		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid THEN RTRIM(LTRIM(eval.family_name))+'' ''+RTRIM(LTRIM(eval.first_name)) ELSE '''' END ELSE nextevalperiod.Name END AS NextEvaluationEvaluatorName,
		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid THEN RL.wrongManager ELSE '''' END ELSE nextevalperiod.wrongmanager END AS NextEvaluationReportedWrongEvaluator,

		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot1.empno ELSE '''' END AS Dotted1Empno, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot1.Dotted1Name ELSE '''' END AS Dotted1Name, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot1.empstatus ELSE '''' END AS dotted1AhrisStatus,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot1.wrongManager ELSE '''' END AS ReportedWrongDot1 ,

		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot1.empno ELSE '''' END ELSE nextDot1.empno END AS NextDotted1Empno, 
		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot1.Dotted1Name ELSE '''' END ELSE nextDot1.DottedName END AS NextDotted1Name, 
		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot1.empstatus ELSE '''' END ELSE nextDot1.empstatus END AS NextDotted1empstatus, 
		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot1.wrongManager ELSE '''' END ELSE nextDot1.wrongManager END AS NextReportedWrongDot1, 

		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot2.empno ELSE '''' END AS Dotted2Empno, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot2.Dotted2Name ELSE '''' END AS Dotted2Name, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot2.empstatus ELSE '''' END AS dotted2AhrisStatus,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot2.wrongManager ELSE '''' END AS ReportedWrongDot2 ,

		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot2.empno ELSE '''' END ELSE nextDot2.empno END AS NextDotted2Empno, 
		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot2.Dotted2Name ELSE '''' END ELSE nextDot2.DottedName END AS NextDotted2Name, 
		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot2.empstatus ELSE '''' END ELSE nextDot2.empstatus END AS NextDotted2empstatus, 
		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot2.wrongManager ELSE '''' END ELSE nextDot2.wrongManager END AS NextReportedWrongDot2, 

		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot3.empno ELSE '''' END AS Dotted3Empno, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot3.Dotted3Name ELSE '''' END AS Dotted3Name, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot3.empstatus ELSE '''' END AS dotted3AhrisStatus,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot3.wrongManager ELSE '''' END AS ReportedWrongDot3 ,

		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot3.empno ELSE '''' END ELSE nextDot3.empno END AS NextDotted3Empno, 
		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot3.Dotted3Name ELSE '''' END ELSE nextDot3.DottedName END AS NextDotted3Name, 
		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot3.empstatus ELSE '''' END ELSE nextDot3.empstatus END AS NextDotted3empstatus, 
		CASE WHEN ISNULL(nextevalperiod.empno, '''')='''' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot3.wrongManager ELSE '''' END ELSE nextDot3.wrongManager END AS NextReportedWrongDot3 
		FROM dbo.ReportingLine RL
		INNER JOIN [dbo].[vw_arco_employee] emp on emp.empno=RL.empnosource
		INNER JOIN [dbo].[vw_arco_employee] eval on eval.empno=RL.empnotarget
		LEFT JOIN  EvaluationsCycle EC ON EC.ID=RL.excludeFromCycles
		OUTER APPLY (
		SELECT  TOP 1 empnotarget, empnotarget AS empno, RTRIM(LTRIM(emp1.family_name))+'' ''+RTRIM(LTRIM(emp1.first_name)) As Name, emp1.empstatus, RLE.wrongManager
			FROM dbo.ReportingLineExceptions RLE
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=RLE.empnotarget AND RLE.state=4
			where RLE.state=4 and RLE.empnosource=RL.empnosource
		)
		nextevalperiod
		OUTER APPLY (
		SELECT  TOP 1 empnotarget, empnotarget AS empno, RTRIM(LTRIM(emp1.family_name))+'' ''+RTRIM(LTRIM(emp1.first_name)) As Dotted1Name, emp1.empstatus, dot1.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot1
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=dot1.empnotarget AND dot1.state=3
			where dot1.state=3 and dot1.empnosource=RL.empnosource
			ORDER BY Rownumber
		)
		Dot1
		OUTER APPLY (
		SELECT  empnotarget AS empno,RTRIM(LTRIM(emp2.family_name))+'' ''+RTRIM(LTRIM(emp2.first_name)) As Dotted2Name, emp2.empstatus, dot2.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot2
			inner JOIN [dbo].[vw_arco_employee] emp2 on emp2.empno=dot2.empnotarget AND dot2.state=3
			where dot2.state=3 and dot2.empnosource=RL.empnosource
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
			where dot3.state=3 and dot3.empnosource=RL.empnosource
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
		)
		Dot3
		OUTER APPLY (
		SELECT  TOP 1 ndot1.empnotarget, ndot1.empnotarget AS empno, RTRIM(LTRIM(dotemp1.family_name))+'' ''+RTRIM(LTRIM(dotemp1.first_name)) As DottedName, dotemp1.empstatus, ndot1.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot1.empnotarget) AS Rownumber
			FROM ReportingLineExceptions ndot1
			inner JOIN [dbo].[vw_arco_employee] dotemp1 on dotemp1.empno=ndot1.empnotarget AND ndot1.state=3
			where ndot1.state=3 and ndot1.empnosource=RL.empnosource
			ORDER BY Rownumber
		)
		nextDot1
		OUTER APPLY (
		SELECT ndot2.empnotarget, ndot2.empnotarget AS empno, RTRIM(LTRIM(dotemp2.family_name))+'' ''+RTRIM(LTRIM(dotemp2.first_name)) As DottedName, dotemp2.empstatus, ndot2.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot2.empnotarget) AS Rownumber
			FROM ReportingLineExceptions ndot2
			inner JOIN [dbo].[vw_arco_employee] dotemp2 on dotemp2.empno=ndot2.empnotarget AND ndot2.state=3
			where ndot2.state=3 and ndot2.empnosource=RL.empnosource
			ORDER BY Rownumber
			OFFSET 1 ROW
			FETCH NEXT 1 ROW ONLY
		)
		nextDot2
		OUTER APPLY (
		SELECT ndot3.empnotarget, ndot3.empnotarget AS empno, RTRIM(LTRIM(dotemp3.family_name))+'' ''+RTRIM(LTRIM(dotemp3.first_name)) As DottedName, dotemp3.empstatus, ndot3.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot3.empnotarget) AS Rownumber
			FROM ReportingLineExceptions ndot3
			inner JOIN [dbo].[vw_arco_employee] dotemp3 on dotemp3.empno=ndot3.empnotarget AND ndot3.state=3
			where ndot3.state=3 and ndot3.empnosource=RL.empnosource
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
		)
		nextDot3
	    WHERE RL.State=4
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
		WHEN E.empGrade<4 AND E.State=4 THEN 1 --Sent Directly to Goals Intial Step for evaluator
		WHEN E.empGrade>3 AND E.State=4 AND (SELECT COUNT(*) FROM Answers WHERE EvaluationID=E.EvaluationID AND State=3)=0 THEN E.State-2 -- check if dotted was in process if not send back 2 steps
		WHEN E.State in (1,2,3,4,5,6) THEN E.State-1
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
			UPDATE dbo.Evaluations SET @newState = State = CASE WHEN empGrade<4 Then 4 ELSE 2 END
			WHERE EvaluationID = @evalid
			--SELECT @newState=CASE WHEN empGrade<4 Then 4 ELSE 2 END
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
		--delete user from all tables.
		DELETE FROM dbo.Answers WHERE EvaluationID in (SELECT EvaluationID FROM dbo.Evaluations WHERE EmployeeID=@empno);
		DELETE FROM dbo.DevelopmentPlan WHERE EvaluationID in (SELECT EvaluationID FROM dbo.Evaluations WHERE EmployeeID=@empno);
		DELETE FROM dbo.EvaluationScores WHERE EvaluationID in (SELECT EvaluationID FROM dbo.Evaluations WHERE EmployeeID=@empno);
		DELETE FROM dbo.Goals WHERE EvaluationID in (SELECT EvaluationID FROM dbo.Evaluations WHERE EmployeeID=@empno);
		DELETE FROM dbo.Evaluations WHERE EmployeeID=@empno;
		DELETE FROM dbo.ReportingLine WHERE empnosource=@empno;
		DELETE FROM dbo.ReportingLineExceptions WHERE empnosource=@empno;
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
		WHERE RL.empnosource=@empid AND Rl.state=4 
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
		 FROM ReportingLine Where (@dot1=empnotarget or @dot2=empnotarget or @dot3=empnotarget) and empnosource=@empid and state=3 AND excludeFromCycles <> @currentcycleid
		
		IF (@evaluationfirstpState>0 AND @noofdottedFound<>@noofdottedtoUpdate)  
		BEGIN
			SELECT @errormsg+=' <li>'+RTRIM(@currentcycleDes)+' - conflict on dotted line managers update, you need to reset back to the initial stage the Goals Settings & Configuration</li>'
		END


		--Change evaluator next period
		DECLARE @evaluatorNextp AS varchar(5), @evaluationNextpState as int, @createdByNextp as varchar(5);
		
		SELECT @evaluatorNextp = RL.empnotarget,  @evaluationNextpState = ISNULL(E.State,0), @createdByNextp=ISNULL(UserID, '')
		FROM dbo.ReportingLine RL 
		LEFT JOIN Evaluations E on RL.empnosource=E.EmployeeID AND E.CycleID=@nextcycleid
		WHERE RL.empnosource=@empid AND Rl.state=4 
		AND RL.excludeFromCycles <> @nextcycleid
		--Check if there is ecxeption
		
		SELECT @evaluatorNextp = RL.empnotarget,  @evaluationNextpState = ISNULL(E.State,0), @createdByNextp=ISNULL(UserID, '')
		FROM dbo.ReportingLineExceptions RL 
		LEFT JOIN Evaluations E on RL.empnosource=E.EmployeeID AND E.CycleID=@nextcycleid
		WHERE RL.empnosource=@empid AND Rl.state=4 
		AND RL.goalCycle = @nextcycleid

		IF (@evaluatorNextp <> @nexteval AND @evaluationNextpState>0) OR (@evaluationNextpState=0 AND @createdByNextp <> '' AND @createdByNextp<>@empid) 
		BEGIN
			SELECT @errormsg+=' <li>'+RTRIM(@nextcycleDes)+' - conflict on evaluator update, you need to reset back to the initial stage the Goals Settings & Configuration</li>'
		END

		--Change dotted next period
		SELECT @noofdottedFound=COUNT(*), @noofdottedtoUpdate=CASE WHEN isnull(@ndot1, '')='' THEN 0 ELSE 1 END + CASE WHEN isnull(@ndot2, '')='' THEN 0 ELSE 1 END 
		+ CASE WHEN isnull(@ndot3, '')='' THEN 0 ELSE 1 END 
		 FROM ReportingLine Where (@ndot1=empnotarget or @ndot2=empnotarget or @ndot3=empnotarget) and empnosource=@empid and state=3  AND excludeFromCycles <> @nextcycleid
		--check if there is exception
		 SELECT @noofdottedFound=COUNT(*), @noofdottedtoUpdate=CASE WHEN isnull(@ndot1, '')='' THEN 0 ELSE 1 END + CASE WHEN isnull(@ndot2, '')='' THEN 0 ELSE 1 END 
		 + CASE WHEN isnull(@ndot3, '')='' THEN 0 ELSE 1 END 
		  FROM ReportingLineExceptions Where (@ndot1=empnotarget or @ndot2=empnotarget or @ndot3=empnotarget) and empnosource=@empid and state=3  AND goalCycle = @nextcycleid
		  
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
		--1st update reporting line evaluator.
		UPDATE ReportingLine set empnotarget=CASE WHEN isnull(@currenteval,'')='' THEN @nexteval ELSE @currenteval END, wrongmanager=0, excludeFromCycles=@excludecycle, updatedBy=@userid, date=getdate()
		WHERE empnosource=@empid AND State=4
		IF @@ROWCOUNT = 0
			BEGIN
				INSERT INTO dbo.ReportingLine VALUES(@empid, CASE WHEN isnull(@currenteval,'')='' THEN @nexteval ELSE @currenteval END,4, @excludecycle, 0, @userid, getdate());
			END

		--2nd Create Dotted. First delete all dotted and then create one by one.
		--Make sure you delete dotted and reset.
		DELETE FROM ReportingLine WHERE empnosource=@empid and State=3
		IF isnull(@dot1,'')<>''
			BEGIN
				INSERT INTO dbo.ReportingLine VALUES(@empid, @dot1,3, @excludecycle, 0, @userid, getdate());
			END
		IF isnull(@dot2,'')<>''
			BEGIN
				INSERT INTO dbo.ReportingLine VALUES(@empid, @dot2,3, @excludecycle, 0, @userid, getdate());
			END
		IF isnull(@dot3,'')<>''
			BEGIN
				INSERT INTO dbo.ReportingLine VALUES(@empid, @dot3,3, @excludecycle, 0, @userid, getdate());
			END

		--Delete from exceptions and then reset.
		DELETE FROM ReportingLineExceptions WHERE empnosource=@empid;

		--Create flag to see if there is a difference on the dotted lines
		DECLARE @diffDotted as int;
		SELECT @diffDotted= CASE WHEN 
		   ( RTRIM(LTRIM(@ndot1)) NOT IN (RTRIM(LTRIM(@dot1)), RTRIM(LTRIM(@dot2)), RTRIM(LTRIM(@dot3)) )) 
		OR ( RTRIM(LTRIM(@ndot2)) NOT IN (RTRIM(LTRIM(@dot1)), RTRIM(LTRIM(@dot2)), RTRIM(LTRIM(@dot3)) )) 
		OR ( RTRIM(LTRIM(@ndot3)) NOT IN (RTRIM(LTRIM(@dot1)), RTRIM(LTRIM(@dot2)), RTRIM(LTRIM(@dot3)) ))
		OR ( RTRIM(LTRIM(@dot1)) NOT IN (RTRIM(LTRIM(@ndot1)), RTRIM(LTRIM(@ndot2)), RTRIM(LTRIM(@ndot3)) ))
		OR ( RTRIM(LTRIM(@dot2)) NOT IN (RTRIM(LTRIM(@ndot1)), RTRIM(LTRIM(@ndot2)), RTRIM(LTRIM(@ndot3)) ))
		OR ( RTRIM(LTRIM(@dot3)) NOT IN (RTRIM(LTRIM(@ndot1)), RTRIM(LTRIM(@ndot2)), RTRIM(LTRIM(@ndot3)) ))
		THEN 1 ELSE 0 END;
		
		--Check in case the next period evaluator is different than the current and create an exception. The program should not do the insert if the exclusion for next period is set as its not required.
		IF (@currenteval<>@nexteval AND @excludecycle=0) OR (@diffDotted=1 AND @excludecycle=0)
			BEGIN
				--Start the update of the Exception. Insert evaluator.
				--1st update exception evaluator.
				--Check if same evaluator and with different dotted.
				IF @currenteval<>@nexteval
					BEGIN
						INSERT INTO dbo.ReportingLineExceptions VALUES(@empid, @nexteval, 4,  @nextcycleid, 0, @userid, getdate());
					END
				ELSE -- insert the current evaluator in case the only difference is on the dotted
					BEGIN
						INSERT INTO dbo.ReportingLineExceptions VALUES(@empid, @currenteval, 4,  @nextcycleid, 0, @userid, getdate());
					END
				--2nd Create Exception Dotted.
				IF isnull(@ndot1,'')<>''
					BEGIN
						INSERT INTO dbo.ReportingLineExceptions VALUES(@empid, @ndot1,3, @nextcycleid, 0, @userid, getdate());
					END
				IF isnull(@ndot2,'')<>''
					BEGIN
						INSERT INTO dbo.ReportingLineExceptions VALUES(@empid, @ndot2,3, @nextcycleid, 0, @userid, getdate());
					END
				IF isnull(@ndot3,'')<>''
					BEGIN
						INSERT INTO dbo.ReportingLineExceptions VALUES(@empid, @ndot3,3, @nextcycleid, 0, @userid, getdate());
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
		SELECT emp.empno as EmpNo, RTRIM(LTRIM(emp.family_name))+' '+RTRIM(LTRIM(emp.first_name)) As EmpName,
		RL.excludeFromCycles AS CycleExclude, ISNULL(EC.CycleDescription, '') AS CycleDescription,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN eval.empno ELSE '' END AS EvaluatorNumber,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN RTRIM(LTRIM(eval.family_name))+' '+RTRIM(LTRIM(eval.first_name)) ELSE ' ' END AS EvaluatorName,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN eval.empstatus ELSE '' END AS EvaluatorAhrisStatus,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN RL.wrongManager ELSE '' END AS ReportedWrongEvaluator,

		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid THEN eval.empno ELSE '' END ELSE nextevalperiod.empno END AS NextEvaluationEvaluatorNumber,
		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid THEN RTRIM(LTRIM(eval.family_name))+' '+RTRIM(LTRIM(eval.first_name)) ELSE '' END ELSE nextevalperiod.Name END AS NextEvaluationEvaluatorName,
		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid THEN RL.wrongManager ELSE '' END ELSE nextevalperiod.wrongmanager END AS NextEvaluationReportedWrongEvaluator,

		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot1.empno ELSE '' END AS Dotted1Empno, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot1.Dotted1Name ELSE '' END AS Dotted1Name, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot1.empstatus ELSE '' END AS dotted1AhrisStatus,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot1.wrongManager ELSE '' END AS ReportedWrongDot1 ,

		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot1.empno ELSE '' END ELSE nextDot1.empno END AS NextDotted1Empno, 
		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot1.Dotted1Name ELSE '' END ELSE nextDot1.DottedName END AS NextDotted1Name, 
		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot1.empstatus ELSE '' END ELSE nextDot1.empstatus END AS NextDotted1empstatus, 
		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot1.wrongManager ELSE '' END ELSE nextDot1.wrongManager END AS NextReportedWrongDot1, 

		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot2.empno ELSE '' END AS Dotted2Empno, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot2.Dotted2Name ELSE '' END AS Dotted2Name, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot2.empstatus ELSE '' END AS dotted2AhrisStatus,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot2.wrongManager ELSE '' END AS ReportedWrongDot2,

		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot2.empno ELSE '' END ELSE nextDot2.empno END AS NextDotted2Empno, 
		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot2.Dotted2Name ELSE '' END ELSE nextDot2.DottedName END AS NextDotted2Name, 
		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot2.empstatus ELSE '' END ELSE nextDot2.empstatus END AS NextDotted2empstatus, 
		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot2.wrongManager ELSE '' END ELSE nextDot2.wrongManager END AS NextReportedWrongDot2, 

		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot3.empno ELSE '' END AS Dotted3Empno, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot3.Dotted3Name ELSE '' END AS Dotted3Name, 
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot3.empstatus ELSE '' END AS dotted3AhrisStatus,
		CASE WHEN RL.excludeFromCycles<>@currentcycleid THEN Dot3.wrongManager ELSE '' END AS ReportedWrongDot3,

		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot3.empno ELSE '' END ELSE nextDot3.empno END AS NextDotted3Empno, 
		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot3.Dotted3Name ELSE '' END ELSE nextDot3.DottedName END AS NextDotted3Name, 
		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot3.empstatus ELSE '' END ELSE nextDot3.empstatus END AS NextDotted3empstatus, 
		CASE WHEN ISNULL(nextevalperiod.empno, '')='' THEN CASE WHEN RL.excludeFromCycles<>@nextcycleid then dot3.wrongManager ELSE '' END ELSE nextDot3.wrongManager END AS NextReportedWrongDot3 
		FROM dbo.ReportingLine RL
		INNER JOIN [dbo].[vw_arco_employee] emp on emp.empno=RL.empnosource
		INNER JOIN [dbo].[vw_arco_employee] eval on eval.empno=RL.empnotarget
		LEFT JOIN  EvaluationsCycle EC ON EC.ID=RL.excludeFromCycles
		OUTER APPLY (
		SELECT  TOP 1 empnotarget, empnotarget AS empno, RTRIM(LTRIM(emp1.family_name))+' '+RTRIM(LTRIM(emp1.first_name)) As Name, emp1.empstatus, RLE.wrongManager
			FROM dbo.ReportingLineExceptions RLE
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=RLE.empnotarget AND RLE.state=4
			where RLE.state=4 and RLE.empnosource=RL.empnosource
		)
		nextevalperiod
		OUTER APPLY (
		SELECT  TOP 1 empnotarget, empnotarget AS empno, RTRIM(LTRIM(emp1.family_name))+' '+RTRIM(LTRIM(emp1.first_name)) As Dotted1Name, emp1.empstatus, dot1.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot1
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=dot1.empnotarget AND dot1.state=3
			where dot1.state=3 and dot1.empnosource=RL.empnosource
			ORDER BY Rownumber
		)
		Dot1
		OUTER APPLY (
		SELECT  empnotarget AS empno,RTRIM(LTRIM(emp2.family_name))+' '+RTRIM(LTRIM(emp2.first_name)) As Dotted2Name, emp2.empstatus, dot2.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot2
			inner JOIN [dbo].[vw_arco_employee] emp2 on emp2.empno=dot2.empnotarget AND dot2.state=3
			where dot2.state=3 and dot2.empnosource=RL.empnosource
			ORDER BY Rownumber
			OFFSET 1 ROW
			FETCH NEXT 1 ROW ONLY
		)
		Dot2
		OUTER APPLY (
		SELECT  empnotarget AS empno,RTRIM(LTRIM(emp3.family_name))+' '+RTRIM(LTRIM(emp3.first_name)) As Dotted3Name, emp3.empstatus, dot3.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot3
			inner JOIN [dbo].[vw_arco_employee] emp3 on emp3.empno=dot3.empnotarget AND dot3.state=3
			where dot3.state=3 and dot3.empnosource=RL.empnosource
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
		)
		Dot3
		OUTER APPLY (
		SELECT  TOP 1 ndot1.empnotarget, ndot1.empnotarget AS empno, RTRIM(LTRIM(dotemp1.family_name))+' '+RTRIM(LTRIM(dotemp1.first_name)) As DottedName, dotemp1.empstatus, ndot1.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot1.empnotarget) AS Rownumber
			FROM ReportingLineExceptions ndot1
			inner JOIN [dbo].[vw_arco_employee] dotemp1 on dotemp1.empno=ndot1.empnotarget AND ndot1.state=3
			where ndot1.state=3 and ndot1.empnosource=RL.empnosource
			ORDER BY Rownumber
		)
		nextDot1
		OUTER APPLY (
		SELECT ndot2.empnotarget, ndot2.empnotarget AS empno, RTRIM(LTRIM(dotemp2.family_name))+' '+RTRIM(LTRIM(dotemp2.first_name)) As DottedName, dotemp2.empstatus, ndot2.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot2.empnotarget) AS Rownumber
			FROM ReportingLineExceptions ndot2
			inner JOIN [dbo].[vw_arco_employee] dotemp2 on dotemp2.empno=ndot2.empnotarget AND ndot2.state=3
			where ndot2.state=3 and ndot2.empnosource=RL.empnosource
			ORDER BY Rownumber
			OFFSET 1 ROW
			FETCH NEXT 1 ROW ONLY
		)
		nextDot2
		OUTER APPLY (
		SELECT ndot3.empnotarget, ndot3.empnotarget AS empno, RTRIM(LTRIM(dotemp3.family_name))+' '+RTRIM(LTRIM(dotemp3.first_name)) As DottedName, dotemp3.empstatus, ndot3.wrongmanager,
			ROW_NUMBER() OVER (ORDER BY ndot3.empnotarget) AS Rownumber
			FROM ReportingLineExceptions ndot3
			inner JOIN [dbo].[vw_arco_employee] dotemp3 on dotemp3.empno=ndot3.empnotarget AND ndot3.state=3
			where ndot3.state=3 and ndot3.empnosource=RL.empnosource
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
		)
		nextDot3
		WHERE RL.State=4 AND RL.empnosource=:empid";
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
