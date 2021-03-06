<?php

class EvaluationsDAO{

	private $connection = NULL;


	public function __construct($conn)
	{
		$this->connection = $conn;
	}


    /*****
     *	Get pending evaluations based on reporting line
     *
     */
    public function getPendingEvaluations($user)
	{
        $queryString="
		DECLARE @cycleid as INT=(SELECT ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1), @userid as varchar(5)=:userid;
		
		SELECT Ev.EvaluationID, HR.empno as 'EmployeeID',  rtrim(ltrim(HR.family_name))+' '+rtrim(ltrim(HR.first_name)) as 'employeeName', hr.grade , hr.job_desc,
	   	CASE 
			WHEN SR.StateDescription IS NULL AND	CASE 
														WHEN isnull(Ev.empGrade,-1)=-1 THEN Hr.grade 
														ELSE Ev.empGrade 
													END>3 
				THEN 'Goal Setting By Employee'
			WHEN (SR.StateDescription IS NULL or Ev.State=0) AND HR.grade<4 
				THEN 'Configuration By Evaluator' 
			ELSE SR.StateDescription 
		END as 'StateDescription',
	   	CASE 
			WHEN isnull(Ev.State,0)=0 
				THEN 0 
			ELSE Ev.State 
		END as 'State', CONVERT(DATETIME2(0),Ev.StateDate) as StateDate,
		onBehalf.flag as onBehalfFlag, isnull(finished.flag,0) as finishedFlag, Ev.ManagesTeam, isnull(resumeFlag.Section, 0) as resumeSection, editBy.editBy,
		ISNULL(yourNextAction.yourAction, 'No Action') yourAction, isnull(yourNextAction.wrongManager,0) as wrongManager,
		yourNextAction.nstate AS yourActionState,  Ev.UploadedFile, CONVERT(DATETIME2(0),Ev.UploadedDate) AS UploadedDate,
		CASE -- check if you are evaluator and give either optional or actual action
			WHEN (ISNULL(Ev.State,0) in (0,1,2,3,5,6) AND yourEvalAction.estate=5 AND
				CASE 
					WHEN ISNULL(ev.State,0) = 6 
						THEN 5 
					ELSE ISNULL(ev.State,0) 
				END <=yourEvalAction.estate
				AND onBehalf.flag=0)
			THEN 
				CASE
					WHEN	(ISNULL(Ev.State,0) in (0,3) AND isnull(resumeFlag.Section, 0)=0 
								AND CASE
										WHEN isnull(Ev.empGrade,-1)=-1 THEN Hr.grade
										ELSE Ev.empGrade
									END >3 
							)
						THEN 2
					ELSE 1
				END
			WHEN -- For doted give action
				yourNextAction.nstate=ISNULL(Ev.State,0)  AND onBehalf.flag=0
			THEN 1
		END AS  isForAction, 
		HasDotted.HasDottedFlag
		
		FROM dbo.ReportingLine RL
		INNER JOIN  dbo.vw_arco_employee HR on HR.empno=RL.empnosource
		LEFT JOIN   dbo.Evaluations Ev on Ev.EmployeeID=RL.empnosource AND Ev.CycleID=@cycleid
		LEFT JOIN   dbo.EvaluationsCycle EC on EC.ID=Ev.CycleID AND EC.ID=@cycleid AND EC.questionaireInputStatus=1
		LEFT JOIN   dbo.Answers A on A.EvaluationID=Ev.EvaluationID AND  a.State=ev.State
		LEFT JOIN   dbo.StateRef SR on SR.State = Ev.State
		
		OUTER APPLY(
		SELECT COUNT(*) AS HasDottedFlag 
		FROM dbo.ReportingLine 
		WHERE empnosource=rl.empnosource AND state=4 AND cycleid=RL.cycleid
		)HasDotted

		OUTER APPLY(
		SELECT case when count(distinct(UA.userid)) >0 then 1 else 0 end as 'flag' FROM Answers UA
		INNER JOIN Evaluations E on E.EvaluationID=UA.EvaluationID and E.EvaluationID=Ev.EvaluationID
		WHERE UA.State=2 AND E.State=2 AND UA.UserID=E.EmployeeID AND E.CycleID=@cycleid
		)onBehalf -- if at state 2 there is at least one answer from the emploee dont allow to do on behalf.
		
		OUTER APPLY(
		SELECT distinct(A.Finished) as 'flag' FROM Answers A
		WHERE A.State=Ev.State AND A.UserID=@userid AND A.EvaluationID=Ev.EvaluationID
		)finished

		OUTER APPLY(
		SELECT TOP 1 QS.ID as Section FROM Answers A
		INNER JOIN Questions Q on Q.ID=A.QuestionID
		INNER JOIN QuestionSections QS on QS.ID=Q.SectionID
		WHERE A.Finished=0 AND A.UserID=@userid AND A.EvaluationID=Ev.EvaluationID and A.State=Ev.State
		ORDER BY A.Date DESC
		)resumeFlag
		
		OUTER APPLY(
		SELECT empnotarget as editBy FROM ReportingLine WHERE empnotarget=@userid AND empnosource=ev.EmployeeID AND cycleid=RL.cycleid
		AND ( State=isnull(Ev.State,0) or (isnull(Ev.State,0)=3 and state=5) or (isnull(Ev.State,0)=6 and state=5))
		)editBy

		OUTER APPLY(
		SELECT TOP 1  CASE WHEN state=4 THEN 'Complete as Dotted Line Manager'
		WHEN state=5 THEN CASE WHEN Ev.State=6 THEN 'Revise / Finalize as Evaluator' ELSE 'Complete as Evaluator' END
		END as yourAction, isnull(wrongManager,0) as wrongManager, isnull(state,0) as nstate
		FROM ReportingLine WHERE
		State>=
		CASE
		WHEN finished.flag=1 THEN ISNULL(Ev.State,0) + 1
		WHEN Ev.State=6 THEN ISNULL(Ev.State,0) -1 -- for reviewer.
		ELSE ISNULL(Ev.State,0)
		END
		AND
		empnotarget=@userid and empnosource=HR.empno AND cycleid=RL.cycleid
		ORDER BY state asc
		)yourNextAction

		OUTER APPLY(
		SELECT isnull(state,0) as estate
		FROM ReportingLine WHERE
		State=5
		AND
		empnotarget=@userid and empnosource=HR.empno AND cycleid=RL.cycleid
		)yourEvalAction
		
		WHERE RL.empnotarget=@userid AND RL.cycleid=@cycleid
		GROUP BY Ev.EvaluationID, HR.empno, Hr.grade, Ev.empGrade, HR.family_name, HR.first_name, hr.grade,hr.job_desc, SR.StateDescription,
		Ev.State, Ev.StateDate, onBehalf.flag, Ev.ManagesTeam, resumeFlag.Section, RL.empnotarget, editBy.editBy, yourNextAction.yourAction, 
		yourNextAction.wrongManager, yourNextAction.nstate, finished.flag,
		yourEvalAction.estate,Ev.UploadedFile, Ev.UploadedDate, HasDotted.HasDottedFlag
		ORDER BY HR.grade
        ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':userid', $user, PDO::PARAM_STR);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$result["evaluations"] = $query->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}

    /*****
     *	Get logged in user's evaluation pending and completed.
     *
     */
     public function myEvaluations($user)
	{
	 $queryString = "
	 	DECLARE @cycleid as INT = (SELECT ID FROM EvaluationsCycle WHERE  questionaireInputStatus=1), @userid as VARCHAR(5)=:userid;
		
		SELECT Ev.EvaluationID, Hr.empno as 'EmployeeID',  rtrim(ltrim(HR.family_name))+' '+rtrim(ltrim(HR.first_name)) as 'employeeName', Ev.ManagesTeam, hr.job_desc,
		HR.grade, SR.StateDescription, isnull(Ev.State,0) as State, CONVERT(DATETIME2(0),Ev.StateDate) as StateDate, onBehalf.NoAsnwers as onBehalfFlag,  isnull(resumeFlag.Section, 0) as resumeSection
		
		FROM dbo.vw_arco_employee HR
		INNER JOIN dbo.ReportingLine RL ON RL.empnosource=HR.empno AND RL.state=5
        LEFT JOIN dbo.Evaluations Ev on HR.empno=Ev.EmployeeID AND Ev.cycleid=@cycleid
		LEFT JOIN dbo.StateRef SR on SR.State = isnull(Ev.State,0)
		
		-- on behalf flag to not allow employee to change eval in case the evaluator has clicked on behalf
		OUTER APPLY(
		SELECT case when count(*) >0 then 1 else 0 end as 'NoAsnwers' FROM Answers A
		INNER JOIN Evaluations E on E.EvaluationID=A.EvaluationID and E.EvaluationID=Ev.EvaluationID
		WHERE A.State=3 AND E.State=3 AND A.UserID<>E.EmployeeID
		)onBehalf 
		
		--Resume on section of evaluation
		OUTER APPLY(
        SELECT TOP 1  QS.ID as Section FROM Answers A
		INNER JOIN Questions Q on Q.ID=A.QuestionID
		INNER JOIN QuestionSections QS on QS.ID=Q.SectionID
        WHERE A.Finished=0 AND A.UserID=HR.empno AND A.EvaluationID=Ev.EvaluationID
		ORDER BY A.Date DESC
        )resumeFlag
		
		WHERE HR.empno=@userid AND RL.cycleid=@cycleid
		ORDER BY Ev.State ASC, Ev.StateDate, HR.grade DESC, HR.family_name ASC
        ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':userid', $user, PDO::PARAM_STR);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$result["myevaluations"] = $query->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}

    /*****
     *	Get Questions in Evaluation based on user grade, saved answers etc.
     *
     */
    public function getQuestions($evalID, $userid, $state)
	{
	 $queryString = "
		DECLARE @CycleID AS INT, @grade AS INT, @hasGoals AS INT, @evalid AS INT=:evalid,@userid as varchar(5)=:userid,@state AS INT=:state, @isManager AS INT,@empEval AS VARCHAR(5); 
		
		SELECT	@CycleID=E.CycleID, @empEval=RL.empnotarget, @hasGoals=HasGoals.flag, @isManager=E.ManagesTeam, @grade =CASE 
							WHEN E.empGrade >=10 
								THEN 10
							WHEN E.empGrade <=3 
								THEN 1
							ELSE 4
						END
		FROM  EVALUATIONS E
		INNER JOIN dbo.ReportingLine RL ON RL.empnosource=E.EmployeeID AND RL.state=5 AND RL.cycleid=E.CycleID

		OUTER APPLY(
		SELECT CASE WHEN count (*)>0 THEN 1 ELSE 0 END  AS flag FROM Goals WHERE EvaluationID=E.EvaluationID
		)HasGoals
		WHERE EvaluationID=@evalid
		

		SELECT Q.SectionID, QS.SectionNo, ISNULL(QS.SectionSuffix, '') as SectionSuffix, QS.SectionDescription, Q.ID as QuestionID, ROW_NUMBER() OVER(PARTITION BY Q.SectionID Order By Q.SectionID) as QuestionOrder, Q.Title,
        Q.QuestionDescripton, QG.AppliedGrade, QG.ExcelCellEndYear, QG.ExcelCellHalfYear, Q.QuestionTypeID,
        QT.Description, QT.TypeValues, Q.Fillinby, QG.isRequired,
		A.Answer as answer,
		PAEmp.Answer as EmpAnswer,
		PADot.Answer AS DottedAvgAnswer,
		PAEval.Answer as EvalAnswer,
		PARiv.Answer as EvalRevision,
		isnull(Q.NumberingOff,0) as NumberingOff

		FROM Questions Q
        INNER JOIN QuestionTypes QT on QT.ID=Q.QuestionTypeID
        INNER JOIN QuestionSections QS ON QS.ID=Q.SectionID
        INNER JOIN QuestionConfig QG ON QG.QuestionID=Q.ID
		INNER JOIN dbo.QuestionConfigCycle QCC ON QCC.QuestionConfigID=QG.ID AND QCC.CycleID=@CycleID --get questions available to specific cycle only
		LEFT JOIN Answers A ON A.QuestionID=Q.ID AND A.EvaluationID=@evalid AND  A.State=@state AND  UserID=@userid
		
		OUTER APPLY(
		SELECT TOP 1 Answer, State FROM Answers WHERE State=3 AND EvaluationID=@evalid and QuestionID=q.ID ORDER BY Date DESC
		)PAEmp
		
		OUTER APPLY(
		SELECT CAST(ROUND(AVG(CAST(AD.Answer AS DECIMAL(3,2))),0) AS INT) AS Answer, AD.State FROM Answers AD
		INNER JOIN dbo.Evaluations ED ON ED.EvaluationID=AD.EvaluationID
		INNER JOIN dbo.Questions QD ON AD.QuestionID=QD.ID
		WHERE
		QD.QuestionTypeID=1 AND --Get only answers with number in order to get the average.
		AD.State=4 AND AD.EvaluationID=@evalid and AD.QuestionID=q.ID AND
		
			(--For evaluator to see all scores before the evaluation is complete but also forbit the employee to see before completion
			1= CASE WHEN ED.State>4 AND @userid=@empEval THEN 1 END 
			OR 
			-- When Complete to see Average of all Scores
			1= CASE WHEN ED.State=7 THEN 1 END 
			OR 
			--For dotted to see only their score before its compelte
			AD.UserID=@userid AND ED.State<7
			)
		GROUP BY AD.QuestionID, AD.State
		)PADot

		OUTER APPLY(
		SELECT TOP 1 AE.Answer, AE.State FROM Answers AE
		INNER JOIN dbo.Evaluations EE ON EE.EvaluationID=AE.EvaluationID
		WHERE
		AE.State=5 AND AE.EvaluationID=@evalid and AE.QuestionID=q.ID
		AND EE.EmployeeID<>@userid --this is in order not to retrieve the evaluator's answer if you are the employee
		ORDER BY Date DESC
		)PAEval

		OUTER APPLY(
		SELECT TOP 1 Answer, State FROM Answers WHERE State=6 AND EvaluationID=@evalid and QuestionID=q.ID ORDER BY Date DESC
		)PARiv
        
		WHERE QG.AppliedGrade=@grade  
			 AND QS.ID NOT IN (SELECT SectionID FROM QuestionSectionsConfig WHERE state=@state) 
			 AND (Q.SectionID NOT IN (CASE WHEN @hasGoals=1 THEN '' ELSE 3 END, CASE WHEN @isManager=1 THEN '' ELSE 5 END)) --validate for goals section and for leadership section
        ORDER BY Q.SectionID, Q.QuestionOrder
        ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
        $query->bindValue(':userid', $userid, PDO::PARAM_STR);
        $query->bindValue(':state', $state, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["questions"] = $query->fetchAll();
		return $result;
	}

	/*****
     *	Get Dotted lines all answers to display in form after state 4
     *

     */
    public function getDottedAnswers($evalID)
	{
	 $queryString = "
		SELECT A.QuestionID, Q.SectionID, A.Answer, A.GoalID, rtrim(ltrim(HR.family_name))+' '+rtrim(ltrim(HR.first_name)) as 'DotteLineName', HR.empno as UserID
		FROM Answers A
		LEFT JOIN Questions Q on A.QuestionID=Q.ID
		LEFT JOIN QuestionSections QS on QS.ID=Q.SectionID
		INNER JOIN vw_arco_employee HR on HR.empno=A.UserID
		WHERE A.State=4 and A.EvaluationID=:evalid
        ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["dottedAnswers"] = $query->fetchAll();
		return $result;
	}

	/*****
     *	Get Dotted lines all scores to display in form after state 4
     *

     */
    public function getDottedScores($evalID)
	{
	 $queryString = "
			DECLARE @evalid as int=:evalid, @hasgGoals as INT, @empEval AS VARCHAR(5),  @state AS INT; 
			SELECT @state=E.State, @hasgGoals = CASE WHEN count (G.GoalID)>0 THEN 1 ELSE 0 END,  @empEval=RL.empnotarget
			FROM Goals G 
			INNER JOIN dbo.Evaluations E ON E.EvaluationID=G.EvaluationID
			INNER JOIN dbo.ReportingLine RL ON RL.empnosource=E.EmployeeID AND RL.state=5 AND RL.cycleid=E.CycleID
			WHERE G.EvaluationID=@evalid
			GROUP BY RL.empnotarget, e.State;

	 		SELECT E.EvaluationID, 2 AS SectionID, QS.SectionDescription, QSW.weight AS ScoreWeight,dot1.*, dot2.*, dot3.*

			FROM  dbo.Evaluations E
			INNER JOIN QuestionSectionWeights QSW on QSW.sectionid=2 AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
			INNER JOIN dbo.QuestionSections QS ON QS.ID=QSW.sectionid
			
			OUTER APPLY(
			SELECT  TOP 1 empnotarget AS Dot1Empno, RTRIM(LTRIM(emp1.family_name))+' '+RTRIM(LTRIM(emp1.first_name)) As Dot1Name, ESD1.PScore AS Dot1Score, ESD1.PSDescription AS Dot1Description,
			ESD1.PWeight AS Dot1Weight, ESD1.PWeightedScore AS Dot1WeightedScore, 
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot1rl
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=dot1rl.empnotarget AND dot1rl.state=4
			LEFT JOIN	dbo.EvaluationScores ESD1 ON ESD1.UserID=dot1rl.empnotarget AND ESD1.State=4 AND ESD1.EvaluationID=E.EvaluationID
			where dot1rl.state=4 and dot1rl.empnosource=E.EmployeeID AND dot1rl.cycleid=E.CycleID
			ORDER BY Rownumber
			)Dot1
			
			OUTER APPLY (
			SELECT  empnotarget AS Dot2Empno,RTRIM(LTRIM(emp2.family_name))+' '+RTRIM(LTRIM(emp2.first_name)) As Dot2Name, ESD2.PScore AS Dot2Score, ESD2.PSDescription AS Dot2Description,
			ESD2.PWeight AS Dot2Weight, ESD2.PWeightedScore AS Dot2WeightedScore, 
				ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
				FROM ReportingLine dot2rl
				inner JOIN [dbo].[vw_arco_employee] emp2 on emp2.empno=dot2rl.empnotarget AND dot2rl.state=4
				LEFT JOIN	dbo.EvaluationScores ESD2 ON ESD2.UserID=dot2rl.empnotarget AND ESD2.State=4 AND ESD2.EvaluationID=E.EvaluationID
				where dot2rl.state=4 and dot2rl.empnosource=E.EmployeeID AND dot2rl.cycleid=E.CycleID
				ORDER BY Rownumber
				OFFSET 1 ROW
				FETCH NEXT 1 ROW ONLY
			)Dot2
			
			OUTER APPLY (
			SELECT  empnotarget AS Dot3Empno,RTRIM(LTRIM(emp3.family_name))+' '+RTRIM(LTRIM(emp3.first_name)) As Dot3Name, ESD3.PScore AS Dot3Score, ESD3.PSDescription AS Dot3Description,
			ESD3.PWeight AS Dot3Weight, ESD3.PWeightedScore AS Dot3WeightedScore, 
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot3rl
			inner JOIN [dbo].[vw_arco_employee] emp3 on emp3.empno=dot3rl.empnotarget AND dot3rl.state=4
			LEFT JOIN	dbo.EvaluationScores ESD3 ON ESD3.UserID=dot3rl.empnotarget AND ESD3.State=4 AND ESD3.EvaluationID=E.EvaluationID
			where dot3rl.state=4 and dot3rl.empnosource=E.EmployeeID AND dot3rl.cycleid=E.CycleID
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
			)Dot3

			WHERE E.EvaluationID=@evalid 
			
			UNION

			SELECT E.EvaluationID, 3 AS SectionID, QS.SectionDescription, QSW.weight AS ScoreWeight,dot1.*, dot2.*, dot3.*

			FROM  dbo.Evaluations E
			INNER JOIN QuestionSectionWeights QSW on QSW.sectionid=3 AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
			INNER JOIN dbo.QuestionSections QS ON QS.ID=QSW.sectionid
			
			OUTER APPLY(
			SELECT  TOP 1 empnotarget AS Dot1Empno, RTRIM(LTRIM(emp1.family_name))+' '+RTRIM(LTRIM(emp1.first_name)) As Dot1Name, ESD1.GScore AS Dot1Score, ESD1.GSDescription AS Dot1Description,
			ESD1.GWeight AS Dot1Weight, ESD1.GWeightedScore AS Dot1WeightedScore, 
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot1rl
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=dot1rl.empnotarget AND dot1rl.state=4
			LEFT JOIN	dbo.EvaluationScores ESD1 ON ESD1.UserID=dot1rl.empnotarget AND ESD1.State=4 AND ESD1.EvaluationID=E.EvaluationID
			where dot1rl.state=4 and dot1rl.empnosource=E.EmployeeID AND dot1rl.cycleid=E.CycleID
			ORDER BY Rownumber
			)Dot1
			
			OUTER APPLY (
			SELECT  empnotarget AS Dot2Empno,RTRIM(LTRIM(emp2.family_name))+' '+RTRIM(LTRIM(emp2.first_name)) As Dot2Name, ESD2.GScore AS Dot2Score, ESD2.GSDescription AS Dot2Description,
			ESD2.GWeight AS Dot2Weight, ESD2.GWeightedScore AS Dot2WeightedScore, 
				ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
				FROM ReportingLine dot2rl
				inner JOIN [dbo].[vw_arco_employee] emp2 on emp2.empno=dot2rl.empnotarget AND dot2rl.state=4
				LEFT JOIN	dbo.EvaluationScores ESD2 ON ESD2.UserID=dot2rl.empnotarget AND ESD2.State=4 AND ESD2.EvaluationID=E.EvaluationID
				where dot2rl.state=4 and dot2rl.empnosource=E.EmployeeID AND dot2rl.cycleid=E.CycleID
				ORDER BY Rownumber
				OFFSET 1 ROW
				FETCH NEXT 1 ROW ONLY
			)Dot2
			
			OUTER APPLY (
			SELECT  empnotarget AS Dot3Empno,RTRIM(LTRIM(emp3.family_name))+' '+RTRIM(LTRIM(emp3.first_name)) As Dot3Name, ESD3.GScore AS Dot3Score, ESD3.GSDescription AS Dot3Description,
			ESD3.GWeight AS Dot3Weight, ESD3.GWeightedScore AS Dot3WeightedScore, 
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot3rl
			inner JOIN [dbo].[vw_arco_employee] emp3 on emp3.empno=dot3rl.empnotarget AND dot3rl.state=4
			LEFT JOIN	dbo.EvaluationScores ESD3 ON ESD3.UserID=dot3rl.empnotarget AND ESD3.State=4 AND ESD3.EvaluationID=E.EvaluationID
			where dot3rl.state=4 and dot3rl.empnosource=E.EmployeeID AND dot3rl.cycleid=E.CycleID
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
			)Dot3

			WHERE E.EvaluationID=@evalid 

			UNION

			SELECT E.EvaluationID, 4 AS SectionID, QS.SectionDescription, QSW.weight AS ScoreWeight,dot1.*, dot2.*, dot3.*

			FROM  dbo.Evaluations E
			INNER JOIN QuestionSectionWeights QSW on QSW.sectionid=4 AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
			INNER JOIN dbo.QuestionSections QS ON QS.ID=QSW.sectionid
			
			OUTER APPLY(
			SELECT  TOP 1 empnotarget AS Dot1Empno, RTRIM(LTRIM(emp1.family_name))+' '+RTRIM(LTRIM(emp1.first_name)) As Dot1Name, ESD1.CScore AS Dot1Score, ESD1.CSDescription AS Dot1Description,
			ESD1.CWeight AS Dot1Weight, ESD1.CWeightedScore AS Dot1WeightedScore, 
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot1rl
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=dot1rl.empnotarget AND dot1rl.state=4
			LEFT JOIN	dbo.EvaluationScores ESD1 ON ESD1.UserID=dot1rl.empnotarget AND ESD1.State=4 AND ESD1.EvaluationID=E.EvaluationID
			where dot1rl.state=4 and dot1rl.empnosource=E.EmployeeID AND dot1rl.cycleid=E.CycleID
			ORDER BY Rownumber
			)Dot1
			
			OUTER APPLY (
			SELECT  empnotarget AS Dot2Empno,RTRIM(LTRIM(emp2.family_name))+' '+RTRIM(LTRIM(emp2.first_name)) As Dot2Name, ESD2.CScore AS Dot2Score, ESD2.CSDescription AS Dot2Description,
			ESD2.CWeight AS Dot2Weight, ESD2.CWeightedScore AS Dot2WeightedScore, 
				ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
				FROM ReportingLine dot2rl
				inner JOIN [dbo].[vw_arco_employee] emp2 on emp2.empno=dot2rl.empnotarget AND dot2rl.state=4
				LEFT JOIN	dbo.EvaluationScores ESD2 ON ESD2.UserID=dot2rl.empnotarget AND ESD2.State=4 AND ESD2.EvaluationID=E.EvaluationID
				where dot2rl.state=4 and dot2rl.empnosource=E.EmployeeID AND dot2rl.cycleid=E.CycleID
				ORDER BY Rownumber
				OFFSET 1 ROW
				FETCH NEXT 1 ROW ONLY
			)Dot2
			
			OUTER APPLY (
			SELECT  empnotarget AS Dot3Empno,RTRIM(LTRIM(emp3.family_name))+' '+RTRIM(LTRIM(emp3.first_name)) As Dot3Name, ESD3.CScore AS Dot3Score, ESD3.CSDescription AS Dot3Description,
			ESD3.CWeight AS Dot3Weight, ESD3.CWeightedScore AS Dot3WeightedScore, 
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot3rl
			inner JOIN [dbo].[vw_arco_employee] emp3 on emp3.empno=dot3rl.empnotarget AND dot3rl.state=4
			LEFT JOIN	dbo.EvaluationScores ESD3 ON ESD3.UserID=dot3rl.empnotarget AND ESD3.State=4 AND ESD3.EvaluationID=E.EvaluationID
			where dot3rl.state=4 and dot3rl.empnosource=E.EmployeeID AND dot3rl.cycleid=E.CycleID
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
			)Dot3

			WHERE E.EvaluationID=@evalid 

			UNION

			SELECT E.EvaluationID, 5 AS SectionID, QS.SectionDescription, QSW.weight AS ScoreWeight,dot1.*, dot2.*, dot3.*

			FROM  dbo.Evaluations E
			INNER JOIN QuestionSectionWeights QSW on QSW.sectionid=5 AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
			INNER JOIN dbo.QuestionSections QS ON QS.ID=QSW.sectionid
			
			OUTER APPLY(
			SELECT  TOP 1 empnotarget AS Dot1Empno, RTRIM(LTRIM(emp1.family_name))+' '+RTRIM(LTRIM(emp1.first_name)) As Dot1Name, ESD1.LScore AS Dot1Score, ESD1.LSDescription AS Dot1Description,
			ESD1.LWeight AS Dot1Weight, ESD1.LWeightedScore AS Dot1WeightedScore, 
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot1rl
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=dot1rl.empnotarget AND dot1rl.state=4
			LEFT JOIN	dbo.EvaluationScores ESD1 ON ESD1.UserID=dot1rl.empnotarget AND ESD1.State=4 AND ESD1.EvaluationID=E.EvaluationID
			where dot1rl.state=4 and dot1rl.empnosource=E.EmployeeID AND dot1rl.cycleid=E.CycleID
			ORDER BY Rownumber
			)Dot1
			
			OUTER APPLY (
			SELECT  empnotarget AS Dot2Empno,RTRIM(LTRIM(emp2.family_name))+' '+RTRIM(LTRIM(emp2.first_name)) As Dot2Name, ESD2.LScore AS Dot2Score, ESD2.LSDescription AS Dot2Description,
			ESD2.LWeight AS Dot2Weight, ESD2.LWeightedScore AS Dot2WeightedScore, 
				ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
				FROM ReportingLine dot2rl
				inner JOIN [dbo].[vw_arco_employee] emp2 on emp2.empno=dot2rl.empnotarget AND dot2rl.state=4
				LEFT JOIN	dbo.EvaluationScores ESD2 ON ESD2.UserID=dot2rl.empnotarget AND ESD2.State=4 AND ESD2.EvaluationID=E.EvaluationID
				where dot2rl.state=4 and dot2rl.empnosource=E.EmployeeID AND dot2rl.cycleid=E.CycleID
				ORDER BY Rownumber
				OFFSET 1 ROW
				FETCH NEXT 1 ROW ONLY
			)Dot2
			
			OUTER APPLY (
			SELECT  empnotarget AS Dot3Empno,RTRIM(LTRIM(emp3.family_name))+' '+RTRIM(LTRIM(emp3.first_name)) As Dot3Name, ESD3.LScore AS Dot3Score, ESD3.LSDescription AS Dot3Description,
			ESD3.LWeight AS Dot3Weight, ESD3.LWeightedScore AS Dot3WeightedScore, 
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot3rl
			inner JOIN [dbo].[vw_arco_employee] emp3 on emp3.empno=dot3rl.empnotarget AND dot3rl.state=4
			LEFT JOIN	dbo.EvaluationScores ESD3 ON ESD3.UserID=dot3rl.empnotarget AND ESD3.State=4 AND ESD3.EvaluationID=E.EvaluationID
			where dot3rl.state=4 and dot3rl.empnosource=E.EmployeeID AND dot3rl.cycleid=E.CycleID
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
			)Dot3

			WHERE E.EvaluationID=@evalid 

			UNION

			SELECT E.EvaluationID, 10 AS SectionID, 'Overall', 1 ScoreWeight, dot1.*, dot2.*, dot3.*

			FROM  dbo.Evaluations E
			OUTER APPLY(
			SELECT  TOP 1 empnotarget AS Dot1Empno, RTRIM(LTRIM(emp1.family_name))+' '+RTRIM(LTRIM(emp1.first_name)) As Dot1Name, ESD1.OverallScore AS Dot1Score, ESD1.OSDescription AS Dot1Description,
			1 AS Dot1Weight, ESD1.OverallScore AS Dot1WeightedScore, 
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot1rl
			inner JOIN [dbo].[vw_arco_employee] emp1 on emp1.empno=dot1rl.empnotarget AND dot1rl.state=4
			LEFT JOIN	dbo.EvaluationScores ESD1 ON ESD1.UserID=dot1rl.empnotarget AND ESD1.State=4 AND ESD1.EvaluationID=E.EvaluationID
			where dot1rl.state=4 and dot1rl.empnosource=E.EmployeeID AND dot1rl.cycleid=E.CycleID
			ORDER BY Rownumber
			)Dot1
			
			OUTER APPLY (
			SELECT  empnotarget AS Dot2Empno,RTRIM(LTRIM(emp2.family_name))+' '+RTRIM(LTRIM(emp2.first_name)) As Dot2Name, ESD2.OverallScore AS Dot2Score, ESD2.OSDescription AS Dot2Description,
			1 AS Dot2Weight, ESD2.OverallScore AS Dot2WeightedScore, 
				ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
				FROM ReportingLine dot2rl
				inner JOIN [dbo].[vw_arco_employee] emp2 on emp2.empno=dot2rl.empnotarget AND dot2rl.state=4
				LEFT JOIN	dbo.EvaluationScores ESD2 ON ESD2.UserID=dot2rl.empnotarget AND ESD2.State=4 AND ESD2.EvaluationID=E.EvaluationID
				where dot2rl.state=4 and dot2rl.empnosource=E.EmployeeID AND dot2rl.cycleid=E.CycleID
				ORDER BY Rownumber
				OFFSET 1 ROW
				FETCH NEXT 1 ROW ONLY
			)Dot2
			
			OUTER APPLY (
			SELECT  empnotarget AS Dot3Empno,RTRIM(LTRIM(emp3.family_name))+' '+RTRIM(LTRIM(emp3.first_name)) As Dot3Name, ESD3.OverallScore AS Dot3Score, ESD3.OSDescription AS Dot3Description,
			1 AS Dot3Weight, ESD3.OverallScore AS Dot3WeightedScore, 
			ROW_NUMBER() OVER (ORDER BY empnotarget) AS Rownumber
			FROM ReportingLine dot3rl
			inner JOIN [dbo].[vw_arco_employee] emp3 on emp3.empno=dot3rl.empnotarget AND dot3rl.state=4
			LEFT JOIN	dbo.EvaluationScores ESD3 ON ESD3.UserID=dot3rl.empnotarget AND ESD3.State=4 AND ESD3.EvaluationID=E.EvaluationID
			where dot3rl.state=4 and dot3rl.empnosource=E.EmployeeID AND dot3rl.cycleid=E.CycleID
			ORDER BY Rownumber
			OFFSET 2 ROW
			FETCH NEXT 1 ROW ONLY
			)Dot3

			WHERE E.EvaluationID=@evalid 
        ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["dottedScores"] = $query->fetchAll();
		return $result;
	}
     /*****
     *	Get Employee Details in Evaluation Form
     *
     */
     public function getQuestionaireSections($evalID, $userid, $state)
	{
	 $queryString = "
		DECLARE @state as INT=:state, @userid as varchar(5)=:userid, @evalid as INT=:evalid, @hasgGoals as int;
		SELECT @hasgGoals=CASE WHEN count (*)>0 THEN 1 ELSE 0 END FROM Goals WHERE EvaluationID=@evalid;
		SELECT DISTINCT Q.SectionID, QS.SectionNo, ISNULL(QS.SectionSuffix, '') as SectionSuffix, QS.SectionDescription,
		CASE 
			WHEN Q.SectionID=3 AND @state<>4 
				THEN GoalsStatus.countGoals-GoalsStatus.countAnswers 
				ELSE SectionStatus.requiredCount-SectionStatus.answersCount 
		END AS PendingAnswers,
		CASE 
			WHEN Q.SectionID=3 AND @state<>4 
				THEN  GoalsStatus.countGoals 
			ELSE SectionStatus.requiredCount 
		END AS RequiredAnswers,
		DENSE_RANK() OVER (ORDER BY  Q.SectionID)  as SectionOrder
		
		FROM Questions Q
        INNER JOIN QuestionTypes QT on QT.ID=Q.QuestionTypeID
        INNER JOIN QuestionSections QS ON QS.ID=Q.SectionID
        INNER JOIN QuestionConfig QG ON QG.QuestionID=Q.ID
		
		OUTER APPLY(
		SELECT
		CASE
			WHEN E.empGrade >=10 
				THEN 10
			WHEN E.empGrade <=3 
				THEN 1
			ELSE 4
		END
		AS empGrade, E.ManagesTeam
		FROM  EVALUATIONS E
		WHERE E.EvaluationID=@evalid
		)eval

		OUTER APPLY(
		SELECT COUNT(SQ.ID) AS requiredCount, COUNT(SA.ID) AS answersCount 
		
		FROM Questions SQ
		INNER JOIN QuestionConfig SQC on SQC.QuestionID=SQ.ID
		LEFT JOIN Answers SA on SA.QuestionID=SQ.ID and SA.State=@state AND SA.EvaluationID=@evalid AND SA.UserID=@userid
		
		WHERE SQ.SectionID= Q.SectionID AND SQC.isRequired=1
		AND SQC.AppliedGrade=
			(SELECT CASE 
					WHEN SE.empGrade >=10 
						THEN 10 
					WHEN SE.empGrade <=3 
						THEN 1 
					ELSE 4 
					END 
			FROM  EVALUATIONS SE
			WHERE (SE.EvaluationID=@evalid AND SE.ManagesTeam=0 AND SQ.SectionID NOT IN (6)) OR( SE.EvaluationID=@evalid AND SE.ManagesTeam=1)
			)
		AND 1 = 
			CASE WHEN ((@state=3 AND (SQ.Fillinby like '%emp%' or SQ.Fillinby like '%eval%')) or (@state in (5,6) AND SQ.Fillinby like '%eval%') or (@state=4 AND SQ.Fillinby like '%dot%'))
					THEN 1
				ELSE 0
			END
		)SectionStatus

		OUTER APPLY(
		SELECT COUNT(SG.GoalID) AS countGoals, COUNT(SGA.Answer) AS countAnswers FROM Goals SG
		LEFT JOIN Answers SGA on SGA.EvaluationID=@evalid AND SG.GoalID=ISNULL(SGA.GoalID,0) AND SGA.State=@state AND SGA.UserID=@userid
		WHERE SG.EvaluationID=@evalid and ISNULL(SGA.State,0)<>4 and SG.State=2 --so that we always get the last goals for the questionaire
		)GoalsStatus

		WHERE
		QG.AppliedGrade=eval.empGrade AND ((eval.ManagesTeam=0 AND Q.SectionID NOT IN (5)) OR (eval.ManagesTeam=1)) AND ( Q.SectionID NOT IN (CASE WHEN @hasgGoals=1 THEN '' ELSE 3 END))
		AND QS.ID NOT IN (SELECT SectionID FROM QuestionSectionsConfig WHERE state=@state)
		ORDER BY 7 ASC
        ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
        $query->bindValue(':userid', $userid, PDO::PARAM_STR);
        $query->bindValue(':state', $state, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["sections"] = $query->fetchAll();
		return $result;
	}

	/*****
	*	Get Evaluation History
	*
	*/
	public function getEmployeeHistory($evalID)
   {
	$queryString = "
		DECLARE @emp as varchar(5), @evalid as int=:evalid, @cycleid as INT;
		SELECT @cycleid=cycleid, @emp=EmployeeID FROM dbo.Evaluations WHERE EvaluationID=@evalid 

		SELECT TOP 3 EC.ID, EC.CycleDescription, ES.* FROM dbo.EvaluationScores ES 
		INNER JOIN dbo.Evaluations E ON ES.EvaluationID=E.EvaluationID
		INNER JOIN dbo.EvaluationsCycle EC ON EC.ID=E.CycleID
		WHERE ES.State=6 AND e.State=7 AND E.CycleID<>@cycleid AND E.EmployeeID=@emp
		ORDER BY 1 DESC
	   ";
	   $query = $this->connection->prepare($queryString);
	   $query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
	   $result["success"] = $query->execute();
	   $result["errorMessage"] = $query->errorInfo();
	   $query->setFetchMode(PDO::FETCH_ASSOC);
	   $result["empHistory"] = $query->fetchAll();
	   return $result;
   }

	/*****
	*	Get Evaluation Scores
	*
	*/
	public function getEvaluationScores($evalID, $userid, $state)
   {
	$queryString = "
		DECLARE @userid as varchar(5)=:userid, @evalid as int=:evalid, @hasgGoals as INT, @empEval AS VARCHAR(5),  @state AS INT; 
		SELECT @state=E.State, @hasgGoals = CASE WHEN count (G.GoalID)>0 THEN 1 ELSE 0 END,  @empEval=RL.empnotarget
		FROM Goals G 
		INNER JOIN dbo.Evaluations E ON E.EvaluationID=G.EvaluationID
		INNER JOIN dbo.ReportingLine RL ON RL.empnosource=E.EmployeeID AND RL.state=5 AND RL.cycleid=E.CycleID
		WHERE G.EvaluationID=@evalid
		GROUP BY RL.empnotarget, e.State;

		IF @state<7
		BEGIN
			--First Get the response from all Dotted Scores for goals
			WITH CTE_GScores (UserID,Score)
				AS (	
					SELECT 
					A2.UserID, CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(g2.Weight AS DECIMAL(5,2)) / 100)* cast(A2.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2)) AS WeightedScore
					FROM dbo.Answers A2
					INNER JOIN dbo.Goals G2 on G2.GoalID=A2.GoalID
					WHERE A2.EvaluationID=@evalid AND A2.State=4 AND A2.Answer <> '0' --AND A2.UserID=@userid
					GROUP BY a2.UserID
					)

			SELECT DISTINCT	E.EvaluationID, 3 AS SectionID, QS.SectionDescription, QSW.weight as ScoreWeight,
	 		EmpAnswers.WeightedScore AS EmpScore,
			CASE WHEN E.State>4 THEN DotAnswersGScore.GScore ELSE DotAnswers.WeightedScore END AS DotScore,
			EvalAnswers.WeightedScore AS EvalScore,
			RevAnswers.WeightedScore AS RevScore
			FROM dbo.Answers A
			INNER JOIN dbo.Goals G ON G.GoalID=A.GoalID
			INNER JOIN dbo.QuestionSections QS ON QS.ID=3
			INNER JOIN Evaluations E on E.EvaluationID=A.EvaluationID
			INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
		
			OUTER APPLY(
			SELECT 
			CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(g2.Weight AS DECIMAL(5,2)) / 100)* cast(A2.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2)) AS WeightedScore
			FROM dbo.Answers A2
			INNER JOIN dbo.Goals G2 on G2.GoalID=A2.GoalID
			WHERE A2.EvaluationID=E.EvaluationID AND A2.State=3
			)EmpAnswers
		
			OUTER APPLY(
			SELECT CAST(AVG (CTE_GScores.Score) AS DECIMAL(5,2)) AS WeightedScore 
			FROM CTE_GScores
			WHERE 
			--For evaluator to see all scores before the evaluation is complete but also forbit the employee to see before completion
			1= CASE WHEN E.State>4 AND @userid = @empEval THEN 1 END
			OR 
			-- When Complete to see Average of all Scores
			1= CASE WHEN E.State=7 THEN 1 END 
			OR 
			--For dotted to see only their score before its compelte
			CTE_GScores.UserID =@userid AND E.State<7
			)DotAnswers

			OUTER APPLY(
			SELECT CAST(AVG ( ES2.GScore) AS DECIMAL(5,2)) AS GScore 
			FROM dbo.EvaluationScores ES2
			WHERE 
			ES2.EvaluationID=E.EvaluationID AND ES2.State=4 AND ISNULL(ES2.GScore,0.00) <> 0.00 AND
				(
				--For evaluator to see all scores before the evaluation is complete but also forbit the employee to see before completion
				1= CASE WHEN E.State>4 AND @userid = @empEval THEN 1 END
				OR 
				-- When Complete to see Average of all Scores
				1= CASE WHEN E.State=7 THEN 1 END 
				OR 
				--For dotted to see only their score before its compelte
				ES2.UserID =@userid AND E.State<7 
				)
			)DotAnswersGScore

			OUTER APPLY(
			SELECT 
			CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(g2.Weight AS DECIMAL(5,2)) / 100)* cast(A2.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2)) AS WeightedScore
			FROM dbo.Answers A2
			INNER JOIN dbo.Goals G2 on G2.GoalID=A2.GoalID
			WHERE A2.EvaluationID=E.EvaluationID AND A2.State=5
			AND E.EmployeeID<>@userid --this is in order NOT to retrieve the evaluator's answer if you are the employee
			)EvalAnswers
		
			OUTER APPLY(
			SELECT 
			CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(g2.Weight AS DECIMAL(5,2)) / 100)* cast(A2.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2)) AS WeightedScore
			FROM dbo.Answers A2
			INNER JOIN dbo.Goals G2 on G2.GoalID=A2.GoalID
			WHERE A2.EvaluationID=E.EvaluationID AND A2.State=6 AND (E.EmployeeID<>@userid OR (E.State=7 AND A2.Finished=1)) --this is in order NOT to retrieve the evaluator's answer if you are the employee
			)RevAnswers
		
			WHERE A.EvaluationID=@evalid
		
			UNION
		
			SELECT DISTINCT	E.EvaluationID, Q.SectionID, QS.SectionDescription,QSW.weight as ScoreWeight, 
			EmpAnswers.Score AS EmpScore, 
			CASE WHEN E.State>4 THEN 
									CASE WHEN Q.SectionID=2 
											THEN DotAnswersPScore.PScore
										WHEN Q.SectionID=4 
											THEN DotAnswersCScore.CScore
										WHEN Q.SectionID=5
											THEN DotAnswersLScore.LScore
									END	
								 ELSE DotAnswers.Score END AS DotScore, 
			EvalAnswers.Score AS EvalScore,
			RevAnswers.Score AS RevScore
			FROM dbo.Answers A
			INNER JOIN dbo.Questions Q ON Q.ID=A.QuestionID
			INNER JOIN dbo.QuestionSections QS ON QS.ID=Q.SectionID
			INNER JOIN Evaluations E on E.EvaluationID=A.EvaluationID
			INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
		
			OUTER APPLY(
			SELECT CAST(SUM(CAST(A2.Answer AS decimal(5,2)))/COUNT(A2.Answer) as decimal(5,2)) AS Score FROM Answers A2
			INNER JOIN Questions Q2 on (Q2.ID=A2.QuestionID AND Q2.QuestionTypeID=1 AND isnull(A2.GoalID,0)=0)
			WHERE A2.EvaluationID=E.EvaluationID AND A2.State=3 AND	Q2.SectionID=Q.SectionID
			)EmpAnswers
		
			OUTER APPLY (
			SELECT CAST(SUM(CAST(A2.Answer AS decimal(5,2)))/COUNT(A2.Answer) as decimal(5,2)) AS Score FROM Answers A2
			INNER JOIN Questions Q2 on (Q2.ID=A2.QuestionID AND Q2.QuestionTypeID=1 AND isnull(A2.GoalID,0)=0)
			WHERE A2.EvaluationID=E.EvaluationID AND A2.State=4 AND	Q2.SectionID=Q.SectionID
			AND 
				(   
				--For evaluator to see all scores before the evaluation is complete but also forbit the employee to see before completion
				1= CASE WHEN E.State>4 AND @userid =@empEval THEN 1 END 
				OR 
				-- When Complete to see Average of all Scores
				1= CASE WHEN E.State=7 THEN 1 END 
				OR 
				--For dotted to see only their score before its compelte
				a2.UserID=@userid AND E.State<7
				)
			)DotAnswers
			
			OUTER APPLY(
			SELECT CAST(AVG (ES2.PScore ) AS DECIMAL(5,2)) AS PScore
			FROM dbo.EvaluationScores ES2
			WHERE 
			ES2.EvaluationID=E.EvaluationID AND ES2.State=4 AND ISNULL(ES2.PScore,0.00)<>0.00 AND
				(
				--For evaluator to see all scores before the evaluation is complete but also forbit the employee to see before completion
				1= CASE WHEN E.State>4 AND @userid = @empEval THEN 1 END
				OR 
				-- When Complete to see Average of all Scores
				1= CASE WHEN E.State=7 THEN 1 END 
				OR 
				--For dotted to see only their score before its compelte
				ES2.UserID =@userid AND E.State<7 
				)
			)DotAnswersPScore

			OUTER APPLY(
			SELECT CAST(AVG (ES2.CScore) AS DECIMAL(5,2)) AS CScore
			FROM dbo.EvaluationScores ES2
			WHERE 
			ES2.EvaluationID=E.EvaluationID AND ES2.State=4 AND ISNULL(ES2.CScore,0.00)<>0.00 AND
				(
				--For evaluator to see all scores before the evaluation is complete but also forbit the employee to see before completion
				1= CASE WHEN E.State>4 AND @userid = @empEval THEN 1 END
				OR 
				-- When Complete to see Average of all Scores
				1= CASE WHEN E.State=7 THEN 1 END 
				OR 
				--For dotted to see only their score before its compelte
				ES2.UserID =@userid AND E.State<7 
				)
			)DotAnswersCScore

			OUTER APPLY(
			SELECT 
			CAST(AVG (ES2.LScore) AS DECIMAL(5,2)) AS LScore  
			FROM dbo.EvaluationScores ES2
			WHERE 
			ES2.EvaluationID=E.EvaluationID AND ES2.State=4 AND ISNULL(ES2.LScore,0.00)<>0.00 AND
				(
				--For evaluator to see all scores before the evaluation is complete but also forbit the employee to see before completion
				1= CASE WHEN E.State>4 AND @userid = @empEval THEN 1 END
				OR 
				-- When Complete to see Average of all Scores
				1= CASE WHEN E.State=7 THEN 1 END 
				OR 
				--For dotted to see only their score before its compelte
				ES2.UserID =@userid AND E.State<7 
				)
			)DotAnswersLScore

			OUTER APPLY (
			SELECT CAST(SUM(CAST(A2.Answer AS decimal(5,2)))/COUNT(A2.Answer) as decimal(5,2)) AS Score FROM Answers A2
			INNER JOIN Questions Q2 on (Q2.ID=A2.QuestionID AND Q2.QuestionTypeID=1 AND isnull(A2.GoalID,0)=0)
			WHERE A2.EvaluationID=E.EvaluationID AND A2.State=5 AND	Q2.SectionID=Q.SectionID
			AND E.EmployeeID<>@userid --this is in order NOT to retrieve the evaluator's answer if you are the employee
			)EvalAnswers
		
			OUTER APPLY (
			SELECT CAST(SUM(CAST(A2.Answer AS decimal(5,2)))/COUNT(A2.Answer) as decimal(5,2)) AS Score FROM Answers A2
			INNER JOIN Questions Q2 on (Q2.ID=A2.QuestionID AND Q2.QuestionTypeID=1 AND isnull(A2.GoalID,0)=0)
			WHERE A2.EvaluationID=E.EvaluationID AND A2.State=6 AND	Q2.SectionID=Q.SectionID
			AND 1 = CASE WHEN E.EmployeeID=@userid AND E.State=7 THEN 1 WHEN E.EmployeeID<>@userid THEN 1 ELSE 0 END
			--AND E.EmployeeID<>@userid --this is in order NOT to retrieve the evaluator's answer if you are the employee
			)RevAnswers
		
			WHERE QS.HasScore=1 AND A.EvaluationID=@evalid;
		END
		ELSE
		BEGIN
			SELECT E.EvaluationID, 2 AS SectionID, QS.SectionDescription, QSW.weight AS ScoreWeight,
			ESE.PScore AS EmpScore,
			DotScores.avgScore AS DotScore,
			ESEV.PScore AS EvalScore,
			ESR.PScore AS RevScore

			FROM  dbo.Evaluations E
			INNER JOIN QuestionSectionWeights QSW on QSW.sectionid=2 AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
			INNER JOIN dbo.QuestionSections QS ON QS.ID=QSW.sectionid
			LEFT JOIN dbo.EvaluationScores ESE ON ESE.EvaluationID=E.EvaluationID AND ESE.State=3
			LEFT JOIN dbo.EvaluationScores ESEV ON ESEV.EvaluationID=E.EvaluationID AND ESEV.State=5
			LEFT JOIN dbo.EvaluationScores ESR ON ESR.EvaluationID=E.EvaluationID AND ESR.State=6

			OUTER APPLY(
			SELECT CAST(AVG(PScore) AS DECIMAL(5,2)) AS avgScore FROM dbo.EvaluationScores WHERE EvaluationID=@evalid AND State=4
			)DotScores

			WHERE E.EvaluationID=@evalid 
			
			UNION

			SELECT E.EvaluationID, 3 AS SectionID, QS.SectionDescription, QSW.weight AS ScoreWeight,
			ESE.GScore AS EmpScore,
			DotScores.avgScore AS DotScore,
			ESEV.GScore AS EvalScore,
			ESR.GScore AS RevScore

			FROM  dbo.Evaluations E
			INNER JOIN QuestionSectionWeights QSW on QSW.sectionid=3 AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
			INNER JOIN dbo.QuestionSections QS ON QS.ID=QSW.sectionid
			LEFT JOIN dbo.EvaluationScores ESE ON ESE.EvaluationID=E.EvaluationID AND ESE.State=3
			LEFT JOIN dbo.EvaluationScores ESEV ON ESEV.EvaluationID=E.EvaluationID AND ESEV.State=5
			LEFT JOIN dbo.EvaluationScores ESR ON ESR.EvaluationID=E.EvaluationID AND ESR.State=6

			OUTER APPLY(
			SELECT CAST(AVG(GScore) AS DECIMAL(5,2)) AS avgScore FROM dbo.EvaluationScores WHERE EvaluationID=@evalid AND State=4
			)DotScores

			WHERE E.EvaluationID=@evalid 

			UNION

			SELECT E.EvaluationID, 4 AS SectionID, QS.SectionDescription, QSW.weight AS ScoreWeight,
			ESE.CScore AS EmpScore,
			DotScores.avgScore AS DotScore,
			ESEV.CScore AS EvalScore,
			ESR.CScore AS RevScore

			FROM  dbo.Evaluations E
			INNER JOIN QuestionSectionWeights QSW on QSW.sectionid=4 AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
			INNER JOIN dbo.QuestionSections QS ON QS.ID=QSW.sectionid
			LEFT JOIN dbo.EvaluationScores ESE ON ESE.EvaluationID=E.EvaluationID AND ESE.State=3
			LEFT JOIN dbo.EvaluationScores ESEV ON ESEV.EvaluationID=E.EvaluationID AND ESEV.State=5
			LEFT JOIN dbo.EvaluationScores ESR ON ESR.EvaluationID=E.EvaluationID AND ESR.State=6

			OUTER APPLY(
			SELECT CAST(AVG(CScore) AS DECIMAL(5,2)) AS avgScore FROM dbo.EvaluationScores WHERE EvaluationID=@evalid AND State=4
			)DotScores

			WHERE E.EvaluationID=@evalid 

			UNION

			SELECT E.EvaluationID, 5 AS SectionID, QS.SectionDescription, QSW.weight AS ScoreWeight,
			ESE.LScore AS EmpScore,
			DotScores.avgScore AS DotScore,
			ESEV.LScore AS EvalScore,
			ESR.LScore AS RevScore

			FROM  dbo.Evaluations E
			INNER JOIN QuestionSectionWeights QSW on QSW.sectionid=5 AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
			INNER JOIN dbo.QuestionSections QS ON QS.ID=QSW.sectionid
			LEFT JOIN dbo.EvaluationScores ESE ON ESE.EvaluationID=E.EvaluationID AND ESE.State=3
			LEFT JOIN dbo.EvaluationScores ESEV ON ESEV.EvaluationID=E.EvaluationID AND ESEV.State=5
			LEFT JOIN dbo.EvaluationScores ESR ON ESR.EvaluationID=E.EvaluationID AND ESR.State=6

			OUTER APPLY(
			SELECT CAST(AVG(LScore) AS DECIMAL(5,2)) AS avgScore FROM dbo.EvaluationScores WHERE EvaluationID=@evalid AND State=4
			)DotScores

			WHERE E.EvaluationID=@evalid 
		END
	   ";
	   $query = $this->connection->prepare($queryString);
	   $query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
	   $query->bindValue(':userid', $userid, PDO::PARAM_STR);
	   $query->bindValue(':state', $state, PDO::PARAM_INT);
	   $result["success"] = $query->execute();
	   $result["errorMessage"] = $query->errorInfo();
	   $query->setFetchMode(PDO::FETCH_ASSOC);
	   $result["evalScores"] = $query->fetchAll();
	   return $result;
   }

   /*****
   *	Get Evaluation Scores Scales
   *
   */
   public function getScoreScales($evalID)
  {
   $queryString = "
	   Declare @evalid as int =:evalid;
	   Declare @hasgGoals as int = (SELECT CASE WHEN count (*)>0 THEN 1 ELSE 0 END FROM Goals WHERE EvaluationID=@evalid) ;

		SELECT SG.GroupDesc, SC.ScaleDesc1, 0.00 as ScaleFrom1, SC.Scale1 as ScaleTo1,
		SC.ScaleDesc2, SC.Scale1+0.01 as ScaleFrom2, SC.Scale2 as ScaleTo2,
		SC.ScaleDesc3, SC.Scale2+0.01 as ScaleFrom3, SC.Scale3 as ScaleTo3,
		SC.ScaleDesc4, SC.Scale3+0.01 as ScaleFrom4, SC.Scale4 as ScaleTo4
		FROM Evaluations E
		INNER JOIN ScoreGroups SG on SG.gradeLessThan4= CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND SG.forManager=E.ManagesTeam AND SG.withGoals=@hasgGoals
		INNER JOIN ScoreScales SC on SC.GroupID=SG.scoreScaleID
		WHERE E.EvaluationID=@evalid
	  ";
	  $query = $this->connection->prepare($queryString);
	  $query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
	  $result["success"] = $query->execute();
	  $result["errorMessage"] = $query->errorInfo();
	  $query->setFetchMode(PDO::FETCH_ASSOC);
	  $result["scoreScales"] = $query->fetchAll();
	  return $result;
  }


    /*****
     *	Get Employee Details in Evaluation Form
     *
     */
    public function getEmpDetails($evalID)
	{
        /*,
        VEV.empno as 'evalNo', rtrim(ltrim(VEV.family_name))+' - '+rtrim(ltrim(VEV.first_name)) as 'evalName', VEV.job_desc as 'evalPosition',
        VEV.family_desc as 'evalDepartment', VEV.pay_cs as 'evalSite', VEV.site_desc as 'evalSiteDesc',
        VD.empno as 'dotedNo', rtrim(ltrim(VD.family_name))+' - '+rtrim(ltrim(VD.first_name)) as 'dotedEval', VD.job_desc as 'dotedPosition',
        VD.family_desc as 'dotedDepartment', VD.pay_cs as 'dotedSite', VD.site_desc as 'dotedSiteDesc'*/
		$queryString = "
		SELECT REPLACE(REPLACE(CONVERT(VARCHAR,EC.PeriodStart,106), ' ','-'), ',','') as 'startDate', REPLACE(REPLACE(CONVERT(VARCHAR,EC.PeriodEnd,106), ' ','-'), ',','')  as 'endDate' , E.empGrade, VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc', cast(VEM.empAge as int) as empAge, VEM.groupYears, VEM.empCategory, rtrim(ltrim(VEM.family_name)) as familyName, rtrim(ltrim(VEM.first_name)) as firstName, E.State, E.ManagesTeam,
		SelfEval.UserID AS SelfEvalUser
		FROM Evaluations E
		LEFT JOIN EvaluationsCycle EC on EC.ID=E.CycleID
		LEFT JOIN vw_arco_employee VEM on VEM.empno=E.EmployeeID
		OUTER APPLY(
		SELECT DISTINCT A.UserID FROM dbo.Answers A
		WHERE A.EvaluationID=E.EvaluationID AND A.State=3
		) SelfEval
		WHERE E.EvaluationID=:evalID
        ";
        $query = $this->connection->prepare($queryString);
        //$query->bindValue(':empno', $grade, PDO::PARAM_INT);E.EmployeeID=:empno AND
        $query->bindValue(':evalID', $evalID, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["empDetails"] = $query->fetch();
		return $result;
	}

	/*****
     *	Get Evaluations Available for input.
     *
     */
	public function getEvaluationCycles()
	{
		$queryString="
		SELECT ID AS CycleID, CycleDescription FROM EvaluationsCycle WHERE questionaireInputStatus=1
		";
		$query = $this->connection->prepare($queryString);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result["activeGoalCycles"] = $query->fetchAll();
		return $result;
	}
    /*****
     *	Required in evaluation form to define rights
     *
     */
    public function getUserRole($evalID, $userID)
	{
	 $queryString = "
	 	DECLARE @utype nvarchar(6)= (SELECT 'emp' as userType FROM Evaluations WHERE EmployeeID=:userID and EvaluationID=:evalID)
		IF ISNULL(@utype,'')=''
		  BEGIN
			  SELECT  @utype = CASE
							WHEN RL.state=4 THEN 'dotted'
							WHEN RL.state=5 THEN 'eval'
						END
				FROM ReportingLine RL
				INNER JOIN Evaluations E on E.EmployeeID=RL.empnosource
				WHERE E.EvaluationID=:evalID1 and rl.empnotarget=:userID1 AND rl.state=CASE WHEN e.State in (3,6) THEN 5 ELSE e.State END AND RL.cycleid=E.CycleID
				ORDER BY RL.state ASC
		 	END
		  SELECT @utype as 'userType';
        ";
        $query = $this->connection->prepare($queryString);
        //$query->bindValue(':empno', $grade, PDO::PARAM_INT);E.EmployeeID=:empno AND
        $query->bindValue(':evalID', $evalID, PDO::PARAM_INT);
        $query->bindValue(':userID', $userID, PDO::PARAM_STR);
        $query->bindValue(':evalID1', $evalID, PDO::PARAM_INT);
        $query->bindValue(':userID1', $userID, PDO::PARAM_STR);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["userRole"] = $query->fetch();
		return $result;
	}
    /*****
     *	This is for the evaluation form to show the steps of the evaluation
     *
     */
    public function getReportingLine($evalID)
	{

	 $queryString = "
	 DECLARE @evalid INT =:evalID, @cycleid AS int;
	 SELECT @cycleid = cycleID FROM dbo.Evaluations WHERE EvaluationID=@evalid;
	 SELECT RL.state, CASE WHEN RL.state=5 THEN 'EMPLOYEE''S EVALUATOR'
	 WHEN RL.state=4 THEN 'EMPLOYEE''S DOTTED LINE MANAGER'
	 END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
	 VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
	 FROM ReportingLine RL
	 INNER JOIN Evaluations E on E.EmployeeID=RL.empnosource
	 LEFT JOIN EvaluationsCycle EC on EC.ID=E.CycleID
	 LEFT JOIN vw_arco_employee EMP on EMP.empno=E.EmployeeID
	 LEFT JOIN vw_arco_employee VEM on VEM.empno=RL.empnotarget
	 WHERE E.EvaluationID=@evalid AND RL.cycleid=E.cycleID
	 UNION
	 SELECT 6, 'EMPLOYEE''S REVIEWER' AS 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
	 VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
	 FROM ReportingLine RL
	 LEFT JOIN vw_arco_employee EMP on EMP.empno=RL.empnosource
	 LEFT JOIN vw_arco_employee VEM on VEM.empno=RL.empnotarget
	 WHERE RL.empnosource =(SELECT RL2.empnotarget FROM dbo.ReportingLine RL2
	 INNER JOIN dbo.Evaluations E2 ON E2.EmployeeID=RL2.empnosource AND RL2.state=5 and RL2.cycleid=RL.cycleid
	 WHERE E2.EvaluationID=@evalid) AND RL.state=5 AND RL.cycleid=@cycleid
	 ORDER BY 1 ASC
        ";
        $query = $this->connection->prepare($queryString);
        //$query->bindValue(':empno', $grade, PDO::PARAM_INT);E.EmployeeID=:empno AND
        $query->bindValue(':evalID', $evalID, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		
        $result["reportingLine"] = $query->fetchAll();
		return $result;
	}
    /*****
     *	This is for the dialog to get the reporting line of the evaluee at the evaluation and goal section.
     *
     */
    public function getUserReportingLine($empno, $cycleid)
	{
		$queryString = "
		 DECLARE @empno as varchar(5) = :empno, @CycleID AS INT =:cycleid;
		 DECLARE @evaluator as varchar(5) = (
		   SELECT TOP 1 VEM.empno
		   FROM  vw_arco_employee VEM
		   LEFT JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@empno
		   WHERE Rl.empnosource=@empno AND RL.state=5 AND RL.cycleid=@CycleID
		) -- end of delclration to get evaluator of the employee

		SELECT RL.STATE, CASE WHEN RL.STATE=5 THEN 'EMPLOYEE''S EVALUATOR'
					WHEN RL.STATE=4 THEN 'EMPLOYEE''S DOTTED LINE MANAGER'
				END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
		FROM  vw_arco_employee VEM
		LEFT JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@empno 
		WHERE Rl.empnosource=@empno AND RL.state in (4,5) AND RL.cycleid=@CycleID
		-- union to get reviwer
		UNION
		 SELECT 6, 'REVIEWER' as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
		FROM  vw_arco_employee VEM
		LEFT JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@evaluator
		WHERE Rl.empnosource=@evaluator AND RL.state=5 AND RL.cycleid=@CycleID
		ORDER BY 1 DESC
	 ";
	//  $queryString = "
	//  	DECLARE @empno as varchar(5) = :empno;
	// 	DECLARE @CycleID AS INT =:cycleid;
	// 	Declare @evaluator as varchar(5) = (
	// 	   SELECT TOP 1 VEM.empno
	// 	   FROM  vw_arco_employee VEM
	// 	   LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnotarget AND RLE.empnosource=@empno AND RLE.goalCycle=@CycleID
	// 	   LEFT JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@empno AND RL.empnosource NOT IN
	// 	   (SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource=@empno AND goalCycle=@CycleID)
	// 	   WHERE (COALESCE(RLE.empnosource, Rl.empnosource)=@empno) AND (COALESCE(RLE.State, RL.state) =5)
	// 	) -- end of delclration to get evaluator of the employee
	// 	SELECT COALESCE(RLE.STATE,RL.STATE), CASE WHEN COALESCE(RLE.STATE,RL.STATE)=5 THEN 'EMPLOYEE''S EVALUATOR'
	// 				WHEN COALESCE(RLE.STATE,RL.STATE)=4 THEN 'EMPLOYEE''S DOTTED LINE MANAGER'
	// 			END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
	// 	VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
	// 	FROM  vw_arco_employee VEM
	// 	LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnotarget AND RLE.empnosource=@empno AND RLE.goalCycle=@CycleID
	// 	LEFT JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@empno AND RL.empnosource NOT IN
	// 	(SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource=@empno AND goalCycle=@CycleID)
	// 	WHERE (COALESCE(RLE.empnosource, Rl.empnosource)=@empno) AND (COALESCE(RLE.State, RL.state) in (4,5))
	// 	-- union to get reviwer
	// 	UNION
	// 	 SELECT 2, 'REVIEWER' as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
	// 	VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
	// 	FROM  vw_arco_employee VEM
	// 	LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnotarget AND RLE.empnosource=@evaluator AND RLE.goalCycle=@CycleID
	// 	LEFT JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@evaluator AND RL.empnosource NOT IN
	// 	(SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource=@evaluator AND goalCycle=@CycleID)
	// 	WHERE (COALESCE(RLE.empnosource, Rl.empnosource)=@evaluator) AND (COALESCE(RLE.State, RL.state) =5)
	//  ";
	//  $queryString = "
	// 	 Declare @empno as varchar(5) = :empno;
	// 	 Declare @evaluator as varchar(5) = (SELECT empnotarget FROM ReportingLine WHERE empnosource=@empno AND state=4) ;
	// 	 SELECT RL.STATE, CASE WHEN RL.STATE=4 THEN 'EMPLOYEE''S EVALUATOR'
	// 				 WHEN RL.STATE=3 THEN 'EMPLOYEE''S DOTTED LINE MANAGER'
	// 			 END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
	// 	 VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
	// 	 FROM ReportingLine RL
	// 	 LEFT JOIN vw_arco_employee VEM on VEM.empno=RL.empnotarget
	// 	 WHERE RL.empnosource= @empno AND RL.State in (4,5)
	// 	 UNION
	// 	 SELECT 2, 'REVIEWER' as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
	// 	 VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
	// 	 FROM ReportingLine RL
	// 	 LEFT JOIN vw_arco_employee VEM on VEM.empno=RL.empnotarget
	// 	 WHERE RL.empnosource= @evaluator AND RL.State =4
	// 	 ORDER BY RL.STATE DESC
    //     ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':empno', $empno, PDO::PARAM_STR);
		$query->bindValue(':cycleid', $cycleid, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["empReportingLine"] = $query->fetchAll();
		return $result;
	}

	/*****
     *	This is for statistics page
     *
     */
	public function getEvalPendingComplete($user){
        $queryString="
        DECLARE @cycleid as INT=(SELECT ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1), @userid as varchar(5)=:userid;
		
		SELECT G.gradeRange, SUM(G.GoalConfiguration) as GoalConfiguration, SUM(G.PendingEvaluator) as PendingEvaluator, SUM(G.PendingEmployee) as PendingEmployee, SUM(g.PendingDoted) as PendingDoted,
		sum(g.Complete) as Completed
		FROM
		  (	SELECT Distinct RL.empnosource, CASE
			WHEN isnull(EMP.grade,0) between 0 and 3 THEN '0-3'
			WHEN EMP.grade between 4 and 9 THEN '4-9'
			WHEN EMP.grade >= 10 THEN '10'
			ELSE 'others' END AS gradeRange,
			CASE WHEN isnull(E.State,0) in (0,1) THEN 1 ELSE 0 END as GoalConfiguration,
			CASE
				WHEN isnull(E.State,0)=4 AND RL.State=4  THEN 1
				WHEN E.State=0 AND RL.State=3  THEN 1
				WHEN E.State=3 AND RL.State=3  THEN 1
			 ELSE 0
			END as PendingEvaluator,
			CASE WHEN E.State=3  THEN 1 ELSE 0 END as PendingEmployee,
			CASE WHEN E.State=4  THEN 1 ELSE 0 END as PendingDoted,
			--CASE WHEN E.State=6  THEN 1 ELSE 0 END as PendingReview,
			CASE WHEN E.State=6  THEN 1 ELSE 0 END as Complete
		  FROM  ReportingLine RL
		  INNER JOIN vw_arco_employee EMP ON EMP.empno=RL.empnosource
		  LEFT JOIN Evaluations E ON E.EmployeeID=RL.empnosource AND E.CycleID=@cycleid
		  WHERE RL.empnotarget=@userid AND isnull(EMP.grade,0)>=0 AND RL.cycleid=@cycleid
		  ) G
		  GROUP BY G.gradeRange
        ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':userid', $user, PDO::PARAM_STR);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$result["evaluationPerGrade"] = $query->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}

    /*****
     *	Save Answers On Next button in Evaluation Form
     *
     */
    public function saveAnswers($answers, $evalID, $state, $userID, $finished, $pause)
	{

	//Validate if already answered by other user onBehalf, dont do this check if you are saving as dotted.
	if($state<>4) 
		{
			$queryString = "
			DECLARE @evalid AS INT = :evalid, @userid AS varchar(5) = :userid, @state AS INT = :state;
			SELECT COUNT(distinct a.userid) AS cnt FROM ANSWERS A
			INNER JOIN Evaluations E on E.EvaluationID=A.EvaluationID
			WHERE E.EvaluationID=@evalid AND A.UserID<>@userid AND a.State=@state
			";
			$query = $this->connection->prepare($queryString);
			$query->bindValue(':userid', $userID, PDO::PARAM_STR);
			$query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
			$query->bindValue(':state',  $state, PDO::PARAM_INT);
			if (!$query->execute()){
				$result["success"] = false;
				$result["errorMessage"] = $query->errorInfo();
				return $result;
			}
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$countAns = $query->fetch();
		    if ($countAns["cnt"]>0){
		        $result["success"] = false;
		        $result["errorMessage"] = $query->errorInfo();
		        $result["message"] = 'PLease contact the administrator as at this state the evaluation is being answered by another user.';
		        return $result;
		    }
		 }
	//Validate user
	$queryString = "
	DECLARE @evalid int = :evalid, @userid varchar(5) = :userid, @state int = :state;
	SELECT count(*) as cnt 
	FROM ReportingLine RL
	INNER JOIN Evaluations E on E.EmployeeID=RL.empnosource
	WHERE E.EvaluationID=@evalid AND RL.cycleid=E.cycleid
	AND ((RL.state=CASE WHEN @state=3 or @state=6 THEN 5 ELSE @state END AND RL.empnotarget=@userid)
	OR (Rl.empnosource=@userid AND E.State=3))";
	$query = $this->connection->prepare($queryString);
	$query->bindValue(':userid', $userID, PDO::PARAM_STR);
	$query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
	$query->bindValue(':state',  $state, PDO::PARAM_INT);
	if (!$query->execute()){
		$result["success"] = false;
		$result["errorMessage"] = $query->errorInfo();
		return $result;
	}
	$query->setFetchMode(PDO::FETCH_ASSOC);
	$count = $query->fetch();
	if ($count["cnt"]==0){
        $result["success"] = false;
        $result["errorMessage"] = $query->errorInfo();
        $result["message"] = 'PLease contact the administrator as you seem to not have the appropriate rights to edit this evaluation!';
        return $result;
    }
	//validate if state has changed
	$queryString = "
	Declare @evalid int = :evalid, @userid varchar(5) = :userid, @state int = :state;
	SELECT count(*) as cnt FROM dbo.Evaluations WHERE EvaluationID=@evalid and State=@state";
	$query = $this->connection->prepare($queryString);
	$query->bindValue(':userid', $userID, PDO::PARAM_STR);
	$query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
	$query->bindValue(':state',  $state, PDO::PARAM_INT);
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
        $result["message"] = 'PLease contact the administrator the evaluation you are trying to edit has changed state!';
        return $result;
    }


    foreach ($answers as &$answer) {


				//Update answer
                $queryString = "
                Declare @answer nvarchar(max) = :Answer;
                Declare @evalid int = :evalid;
                Declare @userid varchar(5) = :userid;
                Declare @state int = :state;
                Declare @goalid int = :goalid;
				Declare @questiontype int = :QuestionType;
				Declare @pause int = :pause;
				Declare @finished int = :finished;
                IF ( (isnull(@answer, '0')<>'0') OR (isnull(@goalid,'0')<>'0') OR (@questiontype = 2 AND @pause = 1) OR (@state=4 AND @finished =1))
                BEGIN
                    UPDATE dbo.Answers SET Answer=@answer, UserID=@userid, Date=getdate()    WHERE  (EvaluationID=@evalid and QuestionID=:QuestionID and UserID=@userid and state=@state) or (EvaluationID=@evalid AND GoalID=@goalid AND isnull(GoalID, 0)<>0  AND UserID=@userid AND state=@state) ;
                      IF @@ROWCOUNT = 0
                      BEGIN
                          INSERT INTO dbo.Answers VALUES(@evalid, :QuestionID1, @answer, @state, @userid, @goalid, getdate(), 0);
                      END
                END";
                $query = $this->connection->prepare($queryString);
                $query->bindValue(':Answer', $answer["answer"], PDO::PARAM_STR);
				$query->bindValue(':QuestionType', $answer["QuestionTypeID"], PDO::PARAM_INT);
                $query->bindValue(':userid', $userID, PDO::PARAM_STR);
                $query->bindValue(':QuestionID', $answer["QuestionID"], PDO::PARAM_INT);
                $query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
                $query->bindValue(':sectionid', $resumeSection, PDO::PARAM_INT);
                $query->bindValue(':QuestionID1', $answer["QuestionID"], PDO::PARAM_INT);
                $query->bindValue(':state',  $state, PDO::PARAM_INT);
				$query->bindValue(':pause',  $pause, PDO::PARAM_INT);
				// so that we save at least one answer if the dotted clicks finished. that will allow the program to move to next step below in case the dotted didnt reply to any answers.
				$query->bindValue(':finished',  $finished, PDO::PARAM_INT);
                $query->bindValue(':goalid', $answer["GoalID"], PDO::PARAM_STR);

                if (!$query->execute()){
                    $result["success"] = false;
                    $result["errorMessage"] = $query->errorInfo();
                    return $result;
                }
            /*}*/
		}
        if($finished==1)
        {

           $queryString = "
            Declare @state int=:state;
			Declare @nextState int=:nextState;
			Declare @evalid int =:evalid;
			Declare @userid varchar(5) =:userid;
			Declare @hasGoals int = (SELECT CASE WHEN count (*)>0 THEN 1 ELSE 0 END FROM Goals WHERE EvaluationID=@evalid and state=2);
			DECLARE @SectionID AS INT, @score AS DECIMAL(5,2), @weight AS	DECIMAL(5,2), @wscore AS DECIMAL(5,2),@scoreDesc AS VARCHAR(200);
            UPDATE dbo.Answers SET Finished=1 WHERE EvaluationID=@evalid AND State=@state AND UserID=@userid;

			--Check if we are at state 6 (review step). if the user clicks on submit revision, check if he answered any questions and copy the rest from state 5
			IF @state = 6
				BEGIN
					INSERT INTO dbo.Answers
					(EvaluationID,QuestionID,Answer,State,UserID,GoalID,Date,Finished)
					SELECT  EvaluationID, QuestionID, Answer, 6, @userid, GoalID, Date, Finished FROM dbo.Answers
					WHERE EvaluationID=@evalid AND State=5 AND
					(ISNULL(QuestionID,'')<>'' AND QuestionID NOT IN (SELECT QuestionID FROM dbo.Answers WHERE state=6 and EvaluationID=@evalid AND ISNULL(QuestionID,'')<>'')
					)
					union
					SELECT  EvaluationID, QuestionID, Answer, 6, @userid, GoalID, Date, Finished FROM dbo.Answers
					WHERE EvaluationID=@evalid AND State=5 AND
					(
					ISNULL(GoalID,'')<>'' AND GoalID NOT IN (SELECT GoalID FROM dbo.Answers WHERE state=6 and EvaluationID=@evalid AND ISNULL(GoalID,'')<>'')
					)
				END


				-- check if there is record in the evaluation scores otherwise create it
				UPDATE dbo.EvaluationScores SET EvaluationID=@evalid, Date=GETDATE()  WHERE  EvaluationID=@evalid AND state=@state AND UserID=@userid;
					IF @@ROWCOUNT = 0
					BEGIN
						INSERT INTO dbo.EvaluationScores VALUES(@evalid, @userid, @state,0, '', 0, 0,0, '', 0, 0,0, '', 0, 0,0, '',  0, 0,0,'', GETDATE() );
					END
				
				
				 --check for any case if we less sections asnwered so that to recalculate the section weights
				 DECLARE @SectionsRemainingWeight AS DECIMAL(5,2);
				 WITH CTE_SectionWeights
					 AS
					 (
					 SELECT DISTINCT Q.SectionID, QSW.weight
					 FROM dbo.Evaluations E
					 INNER JOIN dbo.Answers A ON A.EvaluationID=E.EvaluationID
					 INNER JOIN Questions Q on Q.ID=A.QuestionID
					 INNER JOIN dbo.QuestionSections QS ON QS.ID=Q.SectionID
					 INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasGoals
					 WHERE E.EvaluationID=@evalid AND A.State=4 AND QS.HasScore=1 AND Q.QuestionTypeID=1 AND isnull(A.GoalID,0)=0 AND A.userID=@userid
					 UNION
					 SELECT  DISTINCT QS.ID, QSW.weight
					 FROM dbo.Evaluations E
					 INNER JOIN dbo.Answers A ON A.EvaluationID=E.EvaluationID
					 INNER JOIN dbo.Goals G on G.GoalID=A.GoalID
					 INNER JOIN dbo.QuestionSections QS ON QS.ID=3
					 INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasGoals
					 WHERE E.EvaluationID=@evalid AND A.State=4 AND A.userID=@userid AND ISNULL(A.Answer,'')<>''
					 )
				 
				 SELECT @SectionsRemainingWeight= 1-SUM(CTE_SectionWeights.weight)
				 FROM CTE_SectionWeights
				 
				 -- now go and update the scores
				 DECLARE sectionWithScores CURSOR LOCAL STATIC FORWARD_ONLY
					 FOR
					 SELECT DISTINCT QS.ID FROM dbo.Answers A
					 INNER JOIN	dbo.Questions Q ON Q.ID=A.QuestionID
					 INNER JOIN dbo.QuestionSections QS ON QS.ID=Q.SectionID OR QS.ID=(SELECT CASE WHEN COUNT(*)>0 THEN 3 ELSE 0 END FROM dbo.Answers WHERE EvaluationID=@evalid AND ISNULL(GoalID,0)>0)
					 WHERE ISNULL(QS.HasScore,0)>0 AND A.EvaluationID=@evalid AND A.State=@state

					 OPEN sectionWithScores

					 FETCH NEXT FROM sectionWithScores INTO @SectionID

					 WHILE (@@FETCH_STATUS = 0)
					 BEGIN
					 --reset scores so that if dotted didnt answer to not save the last one
					 SELECT @score=0, @weight=0, @wscore=0, @scoreDesc='';
							 IF @SectionID=3 --goals scoring
							 BEGIN
							 
							 -- GET First requirements for goals
							 DECLARE @GoalsSet AS INT =(SELECT COUNT(*) FROM dbo.Goals WHERE EvaluationID=@evalid AND State=2)
							 DECLARE @GoalsAnswered AS INT, @GoalsAnsweredWeight AS DECIMAL(5,2), @GoalRemainingWeight AS DECIMAL(5,2)
							 SELECT @GoalsAnswered=COUNT(*), @GoalsAnsweredWeight=SUM(G2.Weight), @GoalRemainingWeight=100-SUM(G2.Weight)
							 FROM dbo.Answers A2 
							 INNER JOIN dbo.Goals G2 ON G2.GoalID=A2.GoalID
							 WHERE A2.EvaluationID=@evalid AND A2.State=4 AND ISNULL(A2.GoalID,'')<>'' AND ISNULL(A2.Answer,'') <> '' AND A2.UserID=@userid
							
							 --Now update goals
							 	SELECT
								 	@score=CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(
									CASE 
										WHEN @GoalsSet-@GoalsAnswered = 0 
											THEN G.Weight 
											ELSE G.Weight + (G.Weight * @GoalRemainingWeight / @GoalsAnsweredWeight) 
									END 
									AS DECIMAL(5,2)) / 100)* CAST(A.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2))
									, 
									@weight=CAST(CASE 
										WHEN @SectionsRemainingWeight=0 
											THEN QSW.weight 
										ELSE QSW.weight + (QSW.weight * @SectionsRemainingWeight / (1-@SectionsRemainingWeight))
									END AS DECIMAL(5,2))
									,
									@wscore=CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(
									CASE 
										WHEN @GoalsSet-@GoalsAnswered = 0 
											THEN G.Weight 
											ELSE G.Weight + (G.Weight * @GoalRemainingWeight / @GoalsAnsweredWeight) 
									END 
									AS DECIMAL(5,2)) / 100)* CAST(A.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) 
									*
									CAST(CASE 
										WHEN @SectionsRemainingWeight=0 
											THEN QSW.weight 
										ELSE QSW.weight + (QSW.weight * @SectionsRemainingWeight / (1-@SectionsRemainingWeight))
									END AS DECIMAL(5,2)) AS DECIMAL(5,2)) 
									,
									@scoreDesc=[dbo].[ConvertScoreToTextGoals](CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(
									CASE 
										WHEN @GoalsSet-@GoalsAnswered = 0 
											THEN G.Weight 
											ELSE G.Weight + (G.Weight * @GoalRemainingWeight / @GoalsAnsweredWeight) 
									END 
									AS DECIMAL(5,2)) / 100)* CAST(A.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2)))
									FROM dbo.Evaluations E
								 INNER JOIN dbo.Answers A ON A.EvaluationID=E.EvaluationID
								 INNER JOIN dbo.Goals G on G.GoalID=A.GoalID
								 INNER JOIN dbo.QuestionSections QS ON QS.ID=@SectionID
								 INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasGoals
								 WHERE E.EvaluationID=@evalid AND A.State=@state AND A.userID=@userid
								 GROUP BY QSW.weight
								 -- update
								 UPDATE dbo.EvaluationScores SET GScore=@score, GWeight=@weight, GWeightedScore=@wscore, GSDEscription=@scoreDesc
							 	 WHERE  EvaluationID=@evalid AND UserID=@userid AND state=@state
							 END
							 ELSE IF @SectionID=2
							 BEGIN --PerformanceScore
								 SELECT
								 @score=CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)), 
								 
								 @weight=CAST(CASE 
										WHEN @SectionsRemainingWeight=0 
											THEN QSW.weight 
										ELSE QSW.weight + (QSW.weight * @SectionsRemainingWeight / (1-@SectionsRemainingWeight))
									END AS DECIMAL(5,2)),
								 @wscore=
								 CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) * 
								 CAST(CASE 
										WHEN @SectionsRemainingWeight=0 
											THEN QSW.weight 
										ELSE QSW.weight + (QSW.weight * @SectionsRemainingWeight / (1-@SectionsRemainingWeight))
									END AS DECIMAL(5,2))
								 AS DECIMAL(5,2)),
								 @scoreDesc=[dbo].[ConvertScoreToTextPCStandards](CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)))
								 FROM dbo.Evaluations E
								 INNER JOIN dbo.Answers A ON A.EvaluationID=E.EvaluationID
								 INNER JOIN Questions Q on Q.ID=A.QuestionID
								 INNER JOIN dbo.QuestionSections QS ON QS.ID=Q.SectionID
								 INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasGoals
								 WHERE E.EvaluationID=@evalid AND A.State=@state AND QS.HasScore=1 AND QS.ID=@SectionID AND Q.QuestionTypeID=1 AND isnull(A.GoalID,0)=0
								 AND A.userID=@userid
								 GROUP BY QSW.weight
								 -- update
								 UPDATE dbo.EvaluationScores SET PScore=@score, PWeight=@weight, PWeightedScore=@wscore, PSDEscription=@scoreDesc
							 WHERE  EvaluationID=@evalid AND UserID=@userid AND state=@state
							 END
							 ELSE IF @SectionID=4
							 BEGIN --CoreCompetencies Score
								 SELECT
								 @score=CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)), 
								 @weight=CAST(CASE 
								 WHEN @SectionsRemainingWeight=0 
									 THEN QSW.weight 
								 ELSE QSW.weight + (QSW.weight * @SectionsRemainingWeight / (1-@SectionsRemainingWeight))
								 END AS DECIMAL(5,2)),
								 @wscore=CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) * 
								 CAST(CASE 
										WHEN @SectionsRemainingWeight=0 
											THEN QSW.weight 
										ELSE QSW.weight + (QSW.weight * @SectionsRemainingWeight / (1-@SectionsRemainingWeight))
									END AS DECIMAL(5,2)) 
								 AS DECIMAL(5,2)),
								 @scoreDesc=[dbo].[ConvertScoreToTextPCStandards](CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)))
								 FROM dbo.Evaluations E
								 INNER JOIN dbo.Answers A ON A.EvaluationID=E.EvaluationID
								 INNER JOIN Questions Q on Q.ID=A.QuestionID
								 INNER JOIN dbo.QuestionSections QS ON QS.ID=Q.SectionID
								 INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasGoals
								 WHERE E.EvaluationID=@evalid AND A.State=@state AND QS.HasScore=1 AND QS.ID=@SectionID AND Q.QuestionTypeID=1 AND isnull(A.GoalID,0)=0
								 AND A.userID=@userid
								 GROUP BY QSW.weight
								 -- update
								 UPDATE dbo.EvaluationScores SET CScore=@score, CWeight=@weight, CWeightedScore=@wscore, CSDEscription=@scoreDesc
							 	 WHERE  EvaluationID=@evalid AND UserID=@userid AND state=@state
							 END
							 ELSE IF @SectionID=5
							 BEGIN --Leadership Score
								 SELECT
								 @score=CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)), 
								 @weight=CAST(CASE 
										WHEN @SectionsRemainingWeight=0 
											THEN QSW.weight 
										ELSE QSW.weight + (QSW.weight * @SectionsRemainingWeight / (1-@SectionsRemainingWeight))
									END AS DECIMAL(5,2)),
								 @wscore=CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) *
								 CAST(CASE 
										WHEN @SectionsRemainingWeight=0 
											THEN QSW.weight 
										ELSE QSW.weight + (QSW.weight * @SectionsRemainingWeight / (1-@SectionsRemainingWeight))
									END AS DECIMAL(5,2)) 
								 AS DECIMAL(5,2)),
								 @scoreDesc=[dbo].[ConvertScoreToTextPCStandards](CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)))
								 FROM dbo.Evaluations E
								 INNER JOIN dbo.Answers A ON A.EvaluationID=E.EvaluationID
								 INNER JOIN Questions Q on Q.ID=A.QuestionID
								 INNER JOIN dbo.QuestionSections QS ON QS.ID=Q.SectionID
								 INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasGoals
								 WHERE E.EvaluationID=@evalid AND A.State=@state AND QS.HasScore=1 AND QS.ID=@SectionID AND Q.QuestionTypeID=1 AND isnull(A.GoalID,0)=0
								 AND A.userID=@userid
								 GROUP BY QSW.weight
								 -- update
								 UPDATE dbo.EvaluationScores SET LScore=@score, LWeight=@weight, LWeightedScore=@wscore, LSDEscription=@scoreDesc
							 	 WHERE  EvaluationID=@evalid AND UserID=@userid AND state=@state
							 END
						 FETCH NEXT FROM sectionWithScores INTO @SectionID
					 END

				 CLOSE sectionWithScores
				 DEALLOCATE sectionWithScores

				 --update overall score
				 UPDATE ES
				 SET ES.OverallScore=ES.GWeightedScore+ES.PWeightedScore+ES.CWeightedScore+ES.LWeightedScore, ES.OSDescription=CASE WHEN ES.GWeightedScore+ES.PWeightedScore+ES.CWeightedScore+ES.LWeightedScore <SC.Scale1 THEN SC.ScaleDesc1
				 WHEN ES.GWeightedScore+ES.PWeightedScore+ES.CWeightedScore+ES.LWeightedScore<SC.Scale2 THEN SC.ScaleDesc2 WHEN ES.GWeightedScore+ES.PWeightedScore+ES.CWeightedScore+ES.LWeightedScore<SC.Scale3 THEN SC.ScaleDesc3 ELSE SC.ScaleDesc4 END
				 FROM dbo.EvaluationScores ES
				 INNER JOIN dbo.Evaluations E ON E.EvaluationID=ES.EvaluationID
				 INNER JOIN ScoreGroups SG on SG.gradeLessThan4= CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND SG.forManager=E.ManagesTeam AND SG.withGoals=@hasGoals
				 INNER JOIN ScoreScales SC on SC.GroupID=SG.scoreScaleID
				 WHERE ES.EvaluationID=@evalid AND ES.state=@state;

			-- end update scores.

			-- get count of how many people are involved regarding the current state
			DECLARE @evalCount int = (SELECT count(*) 
			FROM ReportingLine RL
            INNER JOIN Evaluations E ON E.EmployeeID=RL.empnosource AND E.EvaluationID=@evalid AND CASE WHEN E.State=6 THEN 5 ELSE E.STATE END=Rl.state AND RL.cycleid=E.CycleID);

			-- get dotted required
			DECLARE @dotedCount int =(SELECT count(*) from ReportingLine RL
            INNER JOIN Evaluations E ON E.EmployeeID=RL.empnosource AND E.EvaluationID=@evalid AND Rl.state=4 AND RL.cycleid=E.CycleID);

			--get how many have answered
            DECLARE @actualCount int = (SELECT count(distinct UserID) from Answers WHERE State =@state and Finished=1 AND EvaluationID=@evalid);

            IF (@actualCount=@evalCount OR @state=3)
                BEGIN
					-- This part was applied so that to move forward the process if dotted is not required for this evaluation.
					IF(@state=3 AND @dotedCount=0)
						BEGIN
						SELECT @nextState=5;
						END
                    UPDATE dbo.Evaluations SET State=@nextState WHERE EvaluationID=@evalid;
                END
           ";
			$query = $this->connection->prepare($queryString);
            $query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
            $query->bindValue(':userid', $userID, PDO::PARAM_STR);
            $query->bindValue(':state',  $state, PDO::PARAM_INT);
            $query->bindValue(':nextState',  ($state+1), PDO::PARAM_INT);

			if (!$query->execute()){
				$result["success"] = false;
				$result["errorMessage"] = $query->errorInfo();
				return $result;
			}
        }
		$result["success"] = true;
		return $result;
	}

	 /*****
     *	Revise Selected Evaluations: revise evaluations of them that have state 6, clone state 5 answers and update evaluations state.
     *
     */
	 public function reviseEvaluations($evaluations, $userid)
	 {

	 foreach ($evaluations as &$evaluation)
	 	{
		 //validate if state has changed
			$queryString = "
			Declare @evalid int = :evalid;
			SELECT count(*) as cnt FROM dbo.Evaluations WHERE EvaluationID=@evalid and State=6";

			$query = $this->connection->prepare($queryString);
			$query->bindValue(':evalid', $evaluation, PDO::PARAM_INT);
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
				$result["message"] = 'Please refresh the page as it seems you are trying to review evaluations which you should not!';
				return $result;
			}
		// Start Cloning
		$queryString = "
		Declare @evalid int = :evalid;
		Declare @userid varchar(5) = :userid;
		--Clone answers
		INSERT INTO dbo.Answers
		(EvaluationID,QuestionID,Answer,State,UserID,GoalID,Date,Finished)
		SELECT  EvaluationID, QuestionID, Answer, 6, @userid, GoalID, Date, Finished FROM dbo.Answers
		WHERE EvaluationID=@evalid AND State=5
		--Clone scores
		INSERT INTO dbo.EvaluationScores
		(EvaluationID,UserID,State,PScore,PSDescription,PWeight,PWeightedScore,GScore,GSDescription,GWeight,
		GWeightedScore,CScore,CSDescription,CWeight,CWeightedScore,LScore,LSDescription,LWeight,LWeightedScore,
		OverallScore,OSDescription, Date)
		SELECT EvaluationID, @userid, 6, PScore, PSDescription, PWeight, PWeightedScore,GScore,GSDescription,GWeight,
		GWeightedScore,CScore,CSDescription,CWeight,CWeightedScore,LScore,LSDescription,LWeight,LWeightedScore,
		OverallScore,OSDescription, GETDATE() FROM dbo.EvaluationScores WHERE EvaluationID=@evalid AND State=5;
		--Update evaluation state
		UPDATE dbo.Evaluations SET State=7, StateDate=GETDATE() WHERE EvaluationID=@evalid
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':evalid', $evaluation, PDO::PARAM_INT);
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
     *	Update Evaluation ManagesTeam. For future half and end year value. Create Evaluation Record if it doesnt exist.
     *
     */
     public function updateEvaluation($empno, $mteam, $userid, $cycleid , $file, $filedate)
	{

            $queryString = "
            DECLARE @cycleid as int =:cycleid, @evalid	as int, @grade as varchar(2), @site as varchar(5), @position as varchar(5);
            -- SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1 to be used later in 2017
            UPDATE dbo.Evaluations SET ManagesTeam=:mteam WHERE State in (0,1) AND EmployeeID=:empno AND CycleID=@cycleid AND UserID=:userid
            IF @@ROWCOUNT = 0
                  BEGIN
                      SELECT @grade=grade, @site=pay_cs, @position=post_title_code from vw_arco_employee where empno=:empno1
					  INSERT INTO dbo.Evaluations
					  OUTPUT Inserted.EvaluationID
					  VALUES(@cycleid, :empno2, @grade, 0, getdate(), :mteam1, :userid1, NULL, NULL, @site, @position);
                  END
		    ";
			$query = $this->connection->prepare($queryString);
            $query->bindValue(':mteam', $mteam, PDO::PARAM_INT);
            $query->bindValue(':cycleid', $cycleid, PDO::PARAM_INT);
			$query->bindValue(':empno', $empno, PDO::PARAM_STR);
            $query->bindValue(':empno1', $empno, PDO::PARAM_STR);
            $query->bindValue(':empno2', $empno, PDO::PARAM_STR);
            $query->bindValue(':userid', $userid, PDO::PARAM_STR);
            $query->bindValue(':userid1', $userid, PDO::PARAM_STR);
            $query->bindValue(':mteam1', $mteam, PDO::PARAM_INT);
			$result["success"] = $query->execute();
			$result["errorMessage"] = $query->errorInfo();
			if ($result["errorMessage"][1]!=null){
				return $result;
			}
			$query->setFetchMode(PDO::FETCH_ASSOC);
			$query->nextRowset();
			$evalid = $query->fetch();
			$result["evalid"]=$evalid["EvaluationID"];
			return $result;
	}

	/*****
	*	Update Evaluation fields for uploaded hard copy
	*
	*/
	public function uploadFile($evalid, $file)
   {

		   $queryString = "
		   UPDATE dbo.Evaluations SET UploadedFile=:file, UploadedDate=getdate()
 	   	   OUTPUT Inserted.UploadedDate, Inserted.UploadedFile
		   WHERE EvaluationID=:evalid;
		   ";
		   $query = $this->connection->prepare($queryString);
		   $query->bindValue(':file', $file, PDO::PARAM_STR);
		   $query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
		   $result["success"] = $query->execute();
		   $result["errorMessage"] = $query->errorInfo();
		   $output = $query->fetch();
		   $result["UploadedDate"]=$output['UploadedDate'];
		   $result["UploadedFile"]=$output['UploadedFile'];
		   return $result;
   }

    /*****
     *	Update state of Evaluation
     *  You may update through goals or through the evaluation therefore we require different arguments
     */
    public function updateState($evalid, $userid, $cycleid, $empno, $onBehalf)
	{
            $queryString = "
			DECLARE @evalid as int = :evalid, @cycleid as int = :cycleid, @userid as varchar(5) =:userid, @empno as varchar(5) =:empno, @grade as int,
			@state as int, @answerCount as int, @actualCount int, @onBehalf as int =:onbehalf, @hasDotted as int=0;

			SELECT @evalid=E.EvaluationID, @state=E.State, @grade=HR.Grade
			FROM Evaluations E
			INNER JOIN dbo.vw_arco_employee HR on HR.empno=E.EmployeeID
			WHERE (E.CycleID=@cycleid AND E.EmployeeID=@empno) or (EvaluationID=@evalid);


			-- This is in case the forward button is pressed and there is no evaluation created.
			IF @state IS NULL
			BEGIN
				SELECT @grade=Grade, @state=0 FROM dbo.vw_arco_employee WHERE empno=@empno;
				INSERT INTO dbo.Evaluations
				(CycleID,EmployeeID, empGrade,State,StateDate, ManagesTeam, UserID, UploadedFile, UploadedDate)
				OUTPUT Inserted.EvaluationID
				VALUES(@cycleid, @empno, @grade, 0, getdate(), 0, @userid, NULL, NULL)

				SELECT @evalid=EvaluationID FROM dbo.Evaluations WHERE EmployeeID=@empno;

			END

			--Define if employee has dotted required only if state < 4
			IF @state<4
			BEGIN
				SELECT @hasDotted = count(*) FROM dbo.ReportingLine
				WHERE empnosource = (SELECT EmployeeID FROM dbo.Evaluations WHERE EvaluationID=@evalid) AND cycleid=@cycleid AND State=4
			END

			-- Update state of the evaluation
			-- extra validation to avoid for state 2 and above moving state while there are no answers.
			SELECT @answerCount=COUNT(*) FROM ANSWERS WHERE EvaluationID=@evalid and state=@state;

			--missing, goals validation to update when state is 1 and goals are required to allow you to go ahead.

			--Go and insert history for goals if state between 0 and 3
			IF @state<4
			BEGIN
				IF @onBehalf = 0
					BEGIN
					INSERT INTO dbo.GoalsHistory
					(GoalID,EvaluationID,GoalDescription,Weight,UserID,AttributeCode,State,Date)
					OUTPUT Inserted.EvaluationID
					SELECT GoalID, EvaluationID, GoalDescription, Weight, UserID, AttributeCode, State, GETDATE() FROM dbo.Goals WHERE State=@state AND UserID=@userid AND EvaluationID=@evalid
					IF @@ROWCOUNT = 0
						BEGIN
							INSERT INTO dbo.GoalsHistory
							(GoalID,EvaluationID,GoalDescription,Weight,UserID,AttributeCode,State,Date)
							OUTPUT Inserted.EvaluationID
							SELECT 0, @evalid, 'No Goals Set', '', @userid, '', @state, GETDATE()
						END
					END
				IF @onBehalf = 1
					BEGIN
					INSERT INTO dbo.GoalsHistory
					(GoalID,EvaluationID,GoalDescription,Weight,UserID,AttributeCode,State,Date)
					OUTPUT Inserted.EvaluationID
					SELECT 1, @evalid, 'Moved Forward', '', @userid, '', @state, GETDATE()
					END


			--get how many dotted have placed goals
			SELECT @actualCount = (SELECT count (distinct UserID) FROM GoalsHistory WHERE State =@state AND EvaluationID=@evalid);
			END

			UPDATE dbo.Evaluations SET State=
			CASE
				WHEN @state = 0 AND @hasDotted>0 THEN 1
				WHEN @state = 0 AND @hasDotted=0 THEN 2
				WHEN @state = 0 AND @grade<4 THEN 5 --Sent Directly to Evaluator, shouldnt have dotted to go to
				WHEN @state = 1 THEN  CASE WHEN @hasDotted = @actualCount or @onBehalf=1 THEN  @state+1 ELSE @state END
				WHEN @state = 2 THEN @state+1
				WHEN @state in (3,4,5,6) AND @answerCount>0 THEN @state+1
				--ELSE @state
			END,
			StateDate= CASE WHEN (@state=1 AND (@hasDotted = @actualCount or @onBehalf=1)) OR @state<>1  THEN  getdate()  ELSE StateDate END
			OUTPUT Inserted.EvaluationID
			WHERE EvaluationID=@evalid;
		    ";
			$query = $this->connection->prepare($queryString);
            $query->bindValue(':userid', $userid, PDO::PARAM_STR);
			$query->bindValue(':empno', $empno, PDO::PARAM_STR);
			$query->bindValue(':cycleid', $cycleid, PDO::PARAM_INT);
			$query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
			$query->bindValue(':onbehalf', $onBehalf, PDO::PARAM_INT);
			$result["success"] = $query->execute();
			$result["errorMessage"] = $query->errorInfo();
            if ($result["errorMessage"][1]!=null){
                return $result;
            }
			$query->setFetchMode(PDO::FETCH_ASSOC);
			//$query->nextRowset();
			$id = $query->fetch();
			$evalid=$id["EvaluationID"];
		// 	$queryString = "
		// 	Declare @empnotarget as varchar(5) =:userid;
        //     SELECT E.EvaluationID, E.State, CONVERT(DATETIME2(0),E.StateDate) as StateDate, S.StateDescription, yourAction.nstate as yourActionState, isnull(yourAction.yourAction, 'No Action') as yourActionStateDescr
        //     FROM Evaluations E
		// 	INNER JOIN StateRef S on S.State=E.State
		// 	OUTER APPLY
		// 	(
		// 		SELECT TOP 1
		// 		CASE
		// 		WHEN RL.state=4 THEN 'Complete as Dotted Line Manager'
		// 		WHEN RL.state=5 THEN 'Complete as Evaluator' END
		// 			 as yourAction, isnull(RL.wrongManager,0) as wrongManager, isnull(RL.state,0) as nstate,
		// 			 CASE WHEN (ISNULL(E.State,0) in (0,2) AND yourEvalAction.estate=5 AND
		// 			 CASE WHEN ISNULL(e.State,0) = 6 THEN 5 ELSE ISNULL(e.State,0) END <=yourEvalAction.estate
		// 			 AND onBehalf.NoAsnwers=0)
		// 					 THEN CASE
		// 						 WHEN (ISNULL(E.State,0) in (0) AND CASE
		// 																 WHEN isnull(E.empGrade,-1)=-1 THEN Hr.grade
		// 																 ELSE E.empGrade
		// 															 END >3 )
		// 							 THEN 2
		// 						 ELSE 1
		// 					 END
		// 				 WHEN -- For doted give action
		// 				 yourNextAction.nstate=CASE WHEN ISNULL(Ev.State,0)=1 THEN 4 ELSE ISNULL(Ev.State,0) END  AND onBehalf.NoAsnwers=0
		// 				 THEN 1
		// 			 END AS  isForAction
		// 		FROM ReportingLine RL
		// 		OUTER APPLY (
		// 			SELECT case when count(*) >0 then 1 else 0 end as 'NoAsnwers' FROM Evaluations E
		// 			WHERE State=0 AND UserID<>@empnotarget AND CycleID=E.CycleID and E.EmployeeID=rl.empnosource
		// 			) onBehalf
		// 		OUTER APPLY (
		// 			SELECT isnull(state,0) as estate
		// 			FROM ReportingLine WHERE
		// 			State=5
		// 			AND
		// 			empnotarget=@empnotarget and empnosource=HR.empno
		// 			) yourEvalAction
		// 		OUTER APPLY (
		// 			SELECT TOP 1  CASE WHEN state=4 THEN 'Complete as Dotted Line Manager'
		// 			WHEN state=5 THEN CASE WHEN Ev.State=6 THEN 'Revise / Finalize as Evaluator' ELSE 'Complete as Evaluator' END
		// 			END as yourAction, isnull(wrongManager,0) as wrongManager, isnull(state,0) as nstate
		// 			FROM ReportingLine WHERE
		// 			State>=
		// 			CASE WHEN ISNULL(Ev.State,0) in (0,2) THEN 5 WHEN ISNULL(Ev.State,0) = 1 THEN 4 END
		// 			AND
		// 			empnotarget=@empnotarget and empnosource=HR.empno
		// 			ORDER BY state asc
		// 			) yourNextAction
		// 		WHERE
		// 		state>= CASE WHEN ISNULL(E.State,0) in (0,2) THEN 5 WHEN ISNULL(E.State,0) = 1 THEN 4 END
		// 				AND empnotarget=@empnotarget and empnosource=E.EmployeeID
		// 		ORDER BY state asc
		// 		UNION
		// 		SELECT TOP 1
		// 		CASE
		// 		WHEN state=4 THEN 'Complete as Dotted Line Manager'
		// 		WHEN state=5 THEN 'Complete as Evaluator' END
		// 			 as yourAction, isnull(wrongManager,0) as wrongManager, isnull(state,0) as nstate,
		// 			 CASE WHEN (ISNULL(Ev.State,0) in (0,2) AND yourEvalAction.estate=5 AND
		// 			 CASE WHEN ISNULL(ev.State,0) = 6 THEN 5 ELSE ISNULL(ev.State,0) END <=yourEvalAction.estate
		// 			 AND onBehalf.NoAsnwers=0)
		// 					 THEN CASE
		// 						 WHEN (ISNULL(Ev.State,0) in (0) AND CASE
		// 																 WHEN isnull(Ev.empGrade,-1)=-1 THEN Hr.grade
		// 																 ELSE Ev.empGrade
		// 															 END >3 )
		// 							 THEN 2
		// 						 ELSE 1
		// 					 END
		// 				 WHEN -- For doted give action
		// 				 yourNextAction.nstate=CASE WHEN ISNULL(Ev.State,0)=1 THEN 4 ELSE ISNULL(Ev.State,0) END  AND onBehalf.NoAsnwers=0
		// 				 THEN 1
		// 			 END AS  isForAction
		// 		FROM ReportingLineExceptions
		// 		OUTER APPLY (
		// 			SELECT case when count(*) >0 then 1 else 0 end as 'NoAsnwers' FROM Evaluations E
		// 			WHERE State=0 AND UserID<>@empnotarget AND CycleID=E.CycleID and E.EmployeeID=rl.empnosource
		// 			) onBehalf
		// 		OUTER APPLY (
		// 			SELECT isnull(state,0) as estate
		// 			FROM ReportingLineExceptions WHERE
		// 			State=5
		// 			AND
		// 			empnotarget=@empnotarget and empnosource=HR.empno
		// 			) yourEvalAction
		// 		OUTER APPLY (
		// 			SELECT TOP 1  CASE WHEN state=4 THEN 'Complete as Dotted Line Manager'
		// 			WHEN state=5 THEN CASE WHEN Ev.State=6 THEN 'Revise / Finalize as Evaluator' ELSE 'Complete as Evaluator' END
		// 			END as yourAction, isnull(wrongManager,0) as wrongManager, isnull(state,0) as nstate
		// 			FROM ReportingLineExceptions WHERE
		// 			State>=
		// 			CASE WHEN ISNULL(Ev.State,0) in (0,2) THEN 5 WHEN ISNULL(Ev.State,0) = 1 THEN 4 END
		// 			AND
		// 			empnotarget=@empnotarget and empnosource=HR.empno
		// 			ORDER BY state asc
		// 			) yourNextAction
		// 		WHERE
		// 		state>= CASE WHEN ISNULL(E.State,0) in (0,2) THEN 5 WHEN ISNULL(E.State,0) = 1 THEN 4 END
		// 				AND empnotarget=@empnotarget and empnosource=E.EmployeeID
		// 		ORDER BY state asc
		// 	) yourAction
        //     WHERE E.EvaluationID = :evalid
        //     ";
        //     $query = $this->connection->prepare($queryString);
		// 	$query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
		// 	$query->bindValue(':userid', $userid, PDO::PARAM_STR);
        //     $result["success"] = $query->execute();
        //     $result["errorMessage"] = $query->errorInfo();
        //     $query->setFetchMode(PDO::FETCH_ASSOC);
           $result["evaluation"] = $evalid;
           return $result;
	}

	/*****
    *	Set wrong manager: evaluator or dotted line manager highlites a step for an employee as wrong. Basically notifying the system that another employee should do that step
    *
    */
    public function SetWrongManager($empno, $state, $cycleid)
   {
   		$queryString = "
   		Declare @empno as varchar(5) =:empno, @state as int=:state, @cycleid=:cycleid;
   		UPDATE dbo.ReportingLine SET wrongmanager=1 WHERE  empnosource=@empno AND state=@state AND cycleid=@cycleid;
   		";
   		$query = $this->connection->prepare($queryString);
   		$query->bindValue(':empno', $empno, PDO::PARAM_STR);
		$query->bindValue(':state', $state, PDO::PARAM_INT);
		$query->bindValue(':cycleid', $state, PDO::PARAM_INT);
   		$result["success"] = $query->execute();
   		$result["errorMessage"] = $query->errorInfo();
   		return $result;
   }

   /*****
    *	Revert wrong manager: revert the action of SetWrongManager
    *
    */
    public function RevertWrongManager($empno, $state, $cycleid)
   {
   		$queryString = "
   		Declare @empno as varchar(5) =:empno;
   		Declare @state as int=:state;
   		UPDATE dbo.ReportingLine SET wrongmanager=0 WHERE empnosource=@empno AND state=@state AND cycleid=@cycleid;
   		";
   		$query = $this->connection->prepare($queryString);
   		$query->bindValue(':empno', $empno, PDO::PARAM_STR);
		$query->bindValue(':state', $state, PDO::PARAM_INT);
		$query->bindValue(':cycleid', $state, PDO::PARAM_INT);
   		$result["success"] = $query->execute();
   		$result["errorMessage"] = $query->errorInfo();
   		return $result;
   }


} // END OF CLASS

?>
