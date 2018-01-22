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
		Declare @cycleid as int;
		Declare @userid as varchar(5);
		SELECT @userid=:userid;
		SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1
		SELECT Ev.EvaluationID, HR.empno as 'EmployeeID',  rtrim(ltrim(HR.family_name))+' '+rtrim(ltrim(HR.first_name)) as 'employeeName', hr.grade , hr.job_desc,
	   	CASE WHEN SR.StateDescription IS NULL AND CASE WHEN isnull(Ev.empGrade,-1)=-1 THEN Hr.grade ELSE Ev.empGrade END>3 THEN 'Goal Setting By Employee'
			WHEN (SR.StateDescription IS NULL or Ev.State=0) AND HR.grade<4 THEN 'Configuration By Evaluator' ELSE SR.StateDescription END as 'StateDescription',
	   	CASE WHEN isnull(Ev.State,0)=0 THEN 0 ELSE Ev.State END as 'State', CONVERT(DATETIME2(0),Ev.StateDate) as StateDate,
		   onBehalf.flag as onBehalfFlag, isnull(finished.flag,0) as finishedFlag, Ev.ManagesTeam, isnull(resumeFlag.Section, 0) as resumeSection, editBy.editBy,
		   ISNULL(yourNextAction.yourAction, 'No Action') yourAction, isnull(yourNextAction.wrongManager,0) as wrongManager,
		   yourNextAction.nstate AS yourActionState,  Ev.UploadedFile, CONVERT(DATETIME2(0),Ev.UploadedDate) AS UploadedDate,
		     CASE -- check if you are evaluator and give either optional or actual action
				WHEN (ISNULL(Ev.State,0) in (0,1,2,4,5) AND yourEvalAction.estate=4 AND 
				CASE WHEN ISNULL(ev.State,0) = 5 THEN 4 ELSE ISNULL(ev.State,0) END <=yourEvalAction.estate 
				AND onBehalf.flag=0)
				THEN CASE
						WHEN (ISNULL(Ev.State,0) in (0,2) AND isnull(resumeFlag.Section, 0)=0 AND CASE
																									WHEN isnull(Ev.empGrade,-1)=-1 THEN Hr.grade
																									ELSE Ev.empGrade
																									END >3 )
							THEN 2
						ELSE 1
					 END
				WHEN -- For doted give action
					yourNextAction.nstate=ISNULL(Ev.State,0)  AND onBehalf.flag=0
				THEN 1
			 END AS  isForAction
	   FROM dbo.ReportingLine RL
	   INNER JOIN  dbo.vw_arco_employee HR on HR.empno=RL.empnosource
	   LEFT JOIN   dbo.Evaluations Ev on Ev.EmployeeID=RL.empnosource AND Ev.CycleID=@cycleid
	   LEFT JOIN   dbo.EvaluationsCycle EC on EC.ID=Ev.CycleID AND EC.ID=@cycleid AND EC.questionaireInputStatus=1
	   LEFT JOIN   dbo.Answers A on A.EvaluationID=Ev.EvaluationID AND  a.State=ev.State
	   LEFT JOIN   dbo.StateRef SR on SR.State = Ev.State
	   OUTER APPLY (
		   SELECT case when count(distinct(UA.userid)) >0 then 1 else 0 end as 'flag' FROM Answers UA
		  INNER JOIN Evaluations E on E.EvaluationID=UA.EvaluationID and E.EvaluationID=Ev.EvaluationID
		  WHERE UA.State=2 AND E.State=2 AND UA.UserID=E.EmployeeID AND E.CycleID=@cycleid
			   ) onBehalf -- if at state 2 there is at least one answer from the emploee dont allow to do on behalf.
		OUTER APPLY (
		SELECT distinct(A.Finished) as 'flag' FROM Answers A
		WHERE A.State=Ev.State AND A.UserID=@userid AND A.EvaluationID=Ev.EvaluationID
		) finished
	   OUTER APPLY (
			   SELECT TOP 1 QS.ID as Section FROM Answers A
			   INNER JOIN Questions Q on Q.ID=A.QuestionID
			   INNER JOIN QuestionSections QS on QS.ID=Q.SectionID
			   WHERE A.Finished=0 AND A.UserID=@userid AND A.EvaluationID=Ev.EvaluationID and A.State=Ev.State
			   ORDER BY A.Date DESC
			   ) resumeFlag
		OUTER APPLY (
			   SELECT empnotarget as editBy FROM ReportingLine WHERE empnotarget=@userid AND empnosource=ev.EmployeeID 
			   AND ( State=isnull(Ev.State,0) or (isnull(Ev.State,0)=2 and state=4) or (isnull(Ev.State,0)=5 and state=4))
			   ) editBy
		OUTER APPLY (
			   SELECT TOP 1  CASE WHEN state=3 THEN 'Complete as Dotted Line Manager'
			   WHEN state=4 THEN CASE WHEN Ev.State=5 THEN 'Revise / Finalize as Evaluator' ELSE 'Complete as Evaluator' END
			   END as yourAction, isnull(wrongManager,0) as wrongManager, isnull(state,0) as nstate
			   FROM ReportingLine WHERE
			   State>= 
			   CASE 
			   	WHEN finished.flag=1 THEN ISNULL(Ev.State,0) + 1 
			   	WHEN Ev.State=5 THEN ISNULL(Ev.State,0) -1 -- for reviewer.
				ELSE ISNULL(Ev.State,0) 
			   END
			   AND
			   empnotarget=@userid and empnosource=HR.empno
			   ORDER BY state asc
			   ) yourNextAction
		OUTER APPLY (
			   SELECT isnull(state,0) as estate
			   FROM ReportingLine WHERE
			   State=4
			   AND
			   empnotarget=@userid and empnosource=HR.empno
			   ) yourEvalAction
	   --WHERE RL.empnotarget=@userid AND ((isnull(Ev.State, 0)in (0,1) AND RL.State=2) OR (isnull(Ev.State,0)>1 AND Ev.State=Rl.state) OR (isnull(Ev.State,0)=5))
	   WHERE RL.empnotarget=@userid AND isnull(RL.excludeFromCycles,0)<>1 --AND ((isnull(Ev.State, 0)in (2,3) AND RL.State=3) OR (isnull(Ev.State,0)>3 AND Ev.State=Rl.state) OR (isnull(Ev.State,0)=6))
	   GROUP BY Ev.EvaluationID, HR.empno, Hr.grade, Ev.empGrade, HR.family_name, HR.first_name, hr.grade,hr.job_desc, SR.StateDescription,
	   Ev.State, Ev.StateDate, onBehalf.flag, Ev.ManagesTeam, resumeFlag.Section, RL.empnotarget, editBy.editBy, yourNextAction.yourAction,  yourNextAction.wrongManager, yourNextAction.nstate, finished.flag,
	   yourEvalAction.estate,Ev.UploadedFile, Ev.UploadedDate
	   --HAVING count(A.Answer)=0
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
     *	Get actions based on reporting line * check if required.
     *
     */
    public function getCurrentActions($user)
	{
        $queryString="
		Declare @cycleid as int;
		Declare @userid as varchar(5);
		SELECT @userid=:userid;
		SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1
		SELECT
		count(distinct hr.empno) as currentPending,
		CASE WHEN isnull(Ev.State,0)=0 THEN 0 ELSE Ev.State END
		as 'StatePending',
	    CASE
			WHEN  isnull(Ev.State,0)=0 THEN 'Optional - Goal Setting on Behalf of Employee'
			WHEN  isnull(Ev.State,0)=2 THEN 'Optional - Complete Evaluation on Behalf of Employee'
		ELSE SR.StateDescription END
	   as 'StateDescription',
	   yourAction.yourActionState,
	   ISNULL(yourAction.yourAction, 'No Action') yourAction,
	   onBehalf.flag, onBehalfGoals.goalsflag
	   FROM dbo.ReportingLine RL
	   INNER JOIN  dbo.vw_arco_employee HR on HR.empno=RL.empnosource
	   LEFT JOIN   dbo.Evaluations Ev on Ev.EmployeeID=RL.empnosource AND Ev.CycleID=@cycleid
	   LEFT JOIN   dbo.EvaluationsCycle EC on EC.ID=Ev.CycleID AND EC.ID=@cycleid AND EC.questionaireInputStatus=1
	   LEFT JOIN   dbo.Answers A on A.EvaluationID=Ev.EvaluationID AND  a.State=ev.State
	   LEFT JOIN   dbo.StateRef SR on SR.State = Ev.State
	   OUTER APPLY
	   (
			SELECT case when count(distinct(A.userid)) >0 then 1 else 0 end as 'flag' FROM Answers A
			INNER JOIN Evaluations E on E.EvaluationID=A.EvaluationID and E.EvaluationID=Ev.EvaluationID
			WHERE A.State=2 AND E.State=2 AND A.UserID=E.EmployeeID AND E.CycleID=@cycleid
		) onBehalf -- on behalf to be set to off if at least one answer from employee
		OUTER APPLY (
		SELECT case when count(*) >0 then 1 else 0 end as 'goalsflag' FROM Evaluations E
			WHERE State=0 AND UserID<>@userid AND CycleID=@cycleid and E.EmployeeID=rl.empnosource
		) onBehalfGoals
		OUTER APPLY
		(
			SELECT empnotarget as editBy FROM ReportingLine WHERE empnotarget=@userid AND empnosource=ev.EmployeeID AND ( State=isnull(Ev.State,0) or (isnull(Ev.State,0)=2 and state=4) or (isnull(Ev.State,0)=5 and state=4))
		) editBy
		OUTER APPLY
		(
			SELECT TOP 1
			CASE
				WHEN state=3 THEN 'Complete as Dotted Line Manager'
				WHEN state=4 THEN CASE WHEN Ev.State=5 THEN 'Revise / Finalize as Evaluator' ELSE 'Complete as Evaluator' END
				END as yourAction, isnull(wrongManager,0) as wrongManager, isnull(state,0) as nstate
			FROM ReportingLine WHERE
			State>=isnull(Ev.State,0)
			and empnotarget=@userid and empnosource=HR.empno
			ORDER BY state asc
		) yourAction
	   WHERE RL.empnotarget=@userid AND isnull(RL.excludeFromCycles,0)<>1
		AND yourAction.yourActionState= CASE
											WHEN isnull(Ev.State,0) in (0,1,2,5) THEN 4 ELSE EV.state
										END
		AND onBehalf.flag=0 AND onBehalfGoals.goalsflag=0
	   GROUP BY hr.empno, Ev.State, SR.StateDescription, yourAction.yourActionState, yourAction.yourAction, onBehalf.flag, onBehalfGoals.goalsflag
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
	 Declare @cycleid as int;
		Select @cycleid = ID FROM EvaluationsCycle WHERE  questionaireInputStatus=1
		SELECT Ev.EvaluationID, Hr.empno as 'EmployeeID',  rtrim(ltrim(HR.family_name))+' '+rtrim(ltrim(HR.first_name)) as 'employeeName', Ev.ManagesTeam, hr.job_desc,
		HR.grade, SR.StateDescription, isnull(Ev.State,0) as State, CONVERT(DATETIME2(0),Ev.StateDate) as StateDate, onBehalf.NoAsnwers as onBehalfFlag,  isnull(resumeFlag.Section, 0) as resumeSection
        FROM dbo.vw_arco_employee HR
		INNER JOIN dbo.ReportingLine RL ON RL.empnosource=HR.empno AND RL.state=4
        LEFT JOIN dbo.Evaluations Ev on HR.empno=Ev.EmployeeID AND Ev.cycleid=@cycleid
		LEFT JOIN dbo.StateRef SR on SR.State = isnull(Ev.State,0)
		OUTER APPLY (
		SELECT case when count(*) >0 then 1 else 0 end as 'NoAsnwers' FROM Answers A
		INNER JOIN Evaluations E on E.EvaluationID=A.EvaluationID and E.EvaluationID=Ev.EvaluationID
		WHERE A.State=2 AND E.State=2 AND A.UserID<>E.EmployeeID
		) onBehalf -- on behalf flag to not allow employee to change eval in case the evaluator has clicked on behalf
		OUTER APPLY (
        SELECT TOP 1  QS.ID as Section FROM Answers A
		INNER JOIN Questions Q on Q.ID=A.QuestionID
		INNER JOIN QuestionSections QS on QS.ID=Q.SectionID
        WHERE A.Finished=0 AND A.UserID=:userid1 AND A.EvaluationID=Ev.EvaluationID
		ORDER BY A.Date DESC
        ) resumeFlag
        WHERE HR.empno=:userid AND ISNULL(rl.excludeFromCycles,0)<>@cycleid
		ORDER BY Ev.State ASC, Ev.StateDate, HR.grade DESC, HR.family_name ASC
        ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':userid', $user, PDO::PARAM_STR);
        $query->bindValue(':userid1', $user, PDO::PARAM_STR);
        $query->bindValue(':userid2', $user, PDO::PARAM_STR);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$result["myevaluations"] = $query->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}

    /*****
     *	Get Questions in Evaluation based on user grade, saved answers etc.
     *
     OUTER APPLY (SELECT AVG(CAST(AA.Answer AS DECIMAL(10, 2))) as AvgAnswer FROM Answers AA
		 INNER JOIN Questions AQ on AQ.ID=AA.QuestionID
		 INNER JOIN QuestionTypes AQT on AQT.ID=AQ.QuestionTypeID AND AQT.isnumeric=1
		WHERE AA.EvaluationID=@evalid AND AA.State=2 AND QuestionID=Q.ID
		 ) avgAnswer
     */
    public function getQuestions($evalID, $userid, $state)
	{
	 $queryString = "
        Declare @state as int;
        SELECT @state=:state;
        Declare @userid as varchar(5);
        SELECT @userid=:userid;
        Declare @evalid as int;
        SELECT @evalid=:evalid;
		Declare @hasgGoals as int = (SELECT CASE WHEN count (*)>0 THEN 1 ELSE 0 END FROM Goals WHERE EvaluationID=@evalid) ;
		SELECT Q.SectionID, QS.SectionNo, ISNULL(QS.SectionSuffix, '') as SectionSuffix, QS.SectionDescription, Q.ID as QuestionID, ROW_NUMBER() OVER(PARTITION BY Q.SectionID Order By Q.SectionID) as QuestionOrder, Q.Title,
        Q.QuestionDescripton, QG.AppliedGrade, QG.ExcelCellEndYear, QG.ExcelCellHalfYear, Q.QuestionTypeID,
        QT.Description, QT.TypeValues, Q.Fillinby, QG.isRequired,
		A.Answer as answer,
		PAEmp.Answer as EmpAnswer,
		PAEval.Answer as EvalAnswer,
		PARiv.Answer as EvalRevision,
		isnull(Q.NumberingOff,0) as NumberingOff
		FROM Questions Q
        INNER JOIN QuestionTypes QT on QT.ID=Q.QuestionTypeID
        INNER JOIN QuestionSections QS ON QS.ID=Q.SectionID
        INNER JOIN QuestionConfig QG ON QG.QuestionID=Q.ID
		LEFT JOIN Answers A ON A.QuestionID=Q.ID AND A.EvaluationID=@evalid AND  A.State=@state AND  UserID=@userid
		OUTER APPLY (
			SELECT TOP 1 Answer, State FROM Answers WHERE State=2 AND EvaluationID=@evalid and QuestionID=q.ID ORDER BY Date DESC
		) PAEmp
		OUTER APPLY (
			SELECT TOP 1 AE.Answer, AE.State FROM Answers AE 
			INNER JOIN dbo.Evaluations EE ON EE.EvaluationID=AE.EvaluationID
			WHERE 
			AE.State=4 AND AE.EvaluationID=@evalid and AE.QuestionID=q.ID 
			AND EE.EmployeeID<>@userid --this is in order not to retrieve the evaluator's answer if you are the employee
			ORDER BY Date DESC
		) PAEval
		OUTER APPLY (
			SELECT TOP 1 Answer, State FROM Answers WHERE State=5 AND EvaluationID=@evalid and QuestionID=q.ID ORDER BY Date DESC
		) PARiv
        WHERE QG.AppliedGrade=(SELECT
                    CASE
                        WHEN E.empGrade >=10 THEN 10
                        WHEN E.empGrade <=3 THEN 1
                        ELSE 4
                    END
        FROM  EVALUATIONS E
        WHERE (E.EvaluationID=@evalid AND E.ManagesTeam=0 AND Q.SectionID NOT IN (5)) OR( E.EvaluationID=@evalid AND E.ManagesTeam=1))
        AND QS.ID NOT IN (SELECT SectionID FROM QuestionSectionsConfig WHERE state=@state) AND ( Q.SectionID NOT IN (CASE WHEN @hasgGoals=1 THEN '' ELSE 3 END))
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
     *	Get Dotted lines all comments to display in form after state 4
     *

     */
    public function getDottedComments($evalID)
	{
	 $queryString = "
		SELECT A.QuestionID, Q.SectionID, A.Answer,  rtrim(ltrim(HR.family_name))+' '+rtrim(ltrim(HR.first_name)) as 'DotteLineName'
		FROM Answers A
		INNER JOIN Questions Q on A.QuestionID=Q.ID
		INNER JOIN QuestionSections QS on QS.ID=Q.SectionID
		INNER JOIN vw_arco_employee HR on HR.empno=A.UserID
		WHERE A.State=3 and A.EvaluationID=:evalid
        ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':evalid', $evalID, PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["dottedComments"] = $query->fetchAll();
		return $result;
	}
     /*****
     *	Get Employee Details in Evaluation Form
     *
     */
     public function getQuestionaireSections($evalID, $userid, $state)
	{
	 $queryString = "
        Declare @state as int;
        SELECT @state=:state;
        Declare @userid as varchar(5);
        SELECT @userid=:userid;
        Declare @evalid as int;
        SELECT @evalid=:evalid;
		Declare @hasgGoals as int = (SELECT CASE WHEN count (*)>0 THEN 1 ELSE 0 END FROM Goals WHERE EvaluationID=@evalid) ;
		SELECT Distinct Q.SectionID, QS.SectionNo, ISNULL(QS.SectionSuffix, '') as SectionSuffix, QS.SectionDescription,
		CASE WHEN Q.SectionID=3 AND @state<>3 THEN GoalsStatus.countGoals-GoalsStatus.countAnswers ELSE SectionStatus.requiredCount-SectionStatus.answersCount END as PendingAnswers,
		CASE WHEN Q.SectionID=3 AND @state<>3 THEN  GoalsStatus.countGoals ELSE SectionStatus.requiredCount END as RequiredAnswers,
		DENSE_RANK() OVER (ORDER BY  Q.SectionID)  as SectionOrder
		FROM Questions Q
        INNER JOIN QuestionTypes QT on QT.ID=Q.QuestionTypeID
        INNER JOIN QuestionSections QS ON QS.ID=Q.SectionID
        INNER JOIN QuestionConfig QG ON QG.QuestionID=Q.ID
		OUTER APPLY (
                    SELECT
                        CASE
                            WHEN E.empGrade >=10 THEN 10
                            WHEN E.empGrade <=3 THEN 1
                            ELSE 4
                        END
                        as empGrade, E.ManagesTeam
                     FROM  EVALUATIONS E
                     WHERE E.EvaluationID=@evalid
		 ) eval
		 OUTER APPLY (
                    SELECT count(SQ.ID) as requiredCount, count(SA.ID) as answersCount FROM Questions SQ
					INNER JOIN QuestionConfig SQC on SQC.QuestionID=SQ.ID
					LEFT JOIN Answers SA on SA.QuestionID=SQ.ID and SA.State=@state AND SA.EvaluationID=@evalid AND SA.UserID=@userid
					WHERE SQ.SectionID= Q.SectionID AND SQC.isRequired=1
					AND SQC.AppliedGrade=(SELECT CASE WHEN SE.empGrade >=10 THEN 10 WHEN SE.empGrade <=3 THEN 1 ELSE 4 END FROM  EVALUATIONS SE
										  WHERE (SE.EvaluationID=@evalid AND SE.ManagesTeam=0 AND SQ.SectionID NOT IN (5)) OR( SE.EvaluationID=@evalid AND SE.ManagesTeam=1))
					AND 1 = CASE WHEN
					((@state=2 AND (SQ.Fillinby like '%emp%' or SQ.Fillinby like '%eval%')) or (@state in (4,5) AND SQ.Fillinby like '%eval%') or (@state=3 AND SQ.Fillinby like '%dot%'))
							THEN 1
							ELSE 0
							END

		 ) SectionStatus
		 OUTER APPLY (
                    Select Count(SG.GoalID) as countGoals, count(SGA.Answer) as countAnswers FROM Goals SG
		 LEFT JOIN Answers SGA on SGA.EvaluationID=@evalid AND SG.GoalID=isnull(SGA.GoalID,0) AND SGA.State=@state AND SGA.UserID=@userid
		 where SG.EvaluationID=@evalid and isnull(SGA.State,0)<>3

		 ) GoalsStatus

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
	*	Get Evaluation Scores
	*
	*/
	public function getEvaluationScores($evalID, $userid, $state)
   {
	$queryString = "
		Declare @state as int;
		SELECT @state=:state;
		Declare @userid as varchar(5);
		SELECT @userid=:userid;
		Declare @evalid as int;
		SELECT @evalid=:evalid;
		Declare @hasgGoals as int = (SELECT CASE WHEN count (*)>0 THEN 1 ELSE 0 END FROM Goals WHERE EvaluationID=@evalid) ;
		SELECT DISTINCT	E.EvaluationID, 3 AS SectionID, QS.SectionDescription, QSW.weight as ScoreWeight,
	 	EmpAnswers.WeightedScore AS EmpScore,
		EvalAnswers.WeightedScore AS EvalScore,
		RevAnswers.WeightedScore AS RevScore
		FROM dbo.Answers A
		INNER JOIN dbo.Goals G ON G.GoalID=A.GoalID
		INNER JOIN dbo.QuestionSections QS ON QS.ID=3
		INNER JOIN Evaluations E on E.EvaluationID=A.EvaluationID
		INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
		OUTER APPLY (
					SELECT --CAST(SUM((CAST(G2.Weight as decimal(5,2))/100) * CAST(A2.Answer as decimal(5,2))) AS DECIMAL(5,2)) AS WeightedScore
					--CAST([dbo].[ConvertGoalScore](sum((G2.Weight/100) * cast(A2.Answer as integer))) AS DECIMAL(5,2)) AS WeightedScore
					CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(g2.Weight AS DECIMAL(5,2)) / 100)* cast(A2.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2)) AS WeightedScore
					FROM dbo.Answers A2
					INNER JOIN dbo.Goals G2 on G2.GoalID=A2.GoalID
					WHERE A2.EvaluationID=E.EvaluationID AND A2.State=2
				) EmpAnswers
		OUTER APPLY (
					SELECT --CAST(SUM((CAST(G2.Weight as decimal(5,2))/100) * CAST(A2.Answer as decimal(5,2))) AS DECIMAL(5,2)) AS WeightedScore
					--CAST(SUM([dbo].[ConvertGoalScore](G2.Weight, A2.Answer)) AS DECIMAL(5,2)) AS WeightedScore
					CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(g2.Weight AS DECIMAL(5,2)) / 100)* cast(A2.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2)) AS WeightedScore
					FROM dbo.Answers A2
					INNER JOIN dbo.Goals G2 on G2.GoalID=A2.GoalID
					WHERE A2.EvaluationID=E.EvaluationID AND A2.State=4
					AND E.EmployeeID<>@userid --this is in order not to retrieve the evaluator's answer if you are the employee
				) EvalAnswers
		OUTER APPLY (
					SELECT --CAST(SUM((CAST(G2.Weight as decimal(5,2))/100) * CAST(A2.Answer as decimal(5,2))) AS DECIMAL(5,2)) AS WeightedScore
					--CAST(SUM([dbo].[ConvertGoalScore](G2.Weight, A2.Answer)) AS DECIMAL(5,2)) AS WeightedScore
					CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(g2.Weight AS DECIMAL(5,2)) / 100)* cast(A2.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2)) AS WeightedScore
					FROM dbo.Answers A2
					INNER JOIN dbo.Goals G2 on G2.GoalID=A2.GoalID
					WHERE A2.EvaluationID=E.EvaluationID AND A2.State=5 AND (E.EmployeeID<>@userid OR (E.State=6 AND A2.Finished=1)) --this is in order not to retrieve the evaluator's answer if you are the employee
				) RevAnswers
		WHERE A.EvaluationID=@evalid
		UNION
		SELECT DISTINCT	E.EvaluationID, Q.SectionID, QS.SectionDescription,QSW.weight as ScoreWeight, EmpAnswers.Score AS EmpScore, EvalAnswers.Score AS EvalScore,
		RevAnswers.Score AS RevScore
		FROM dbo.Answers A
		INNER JOIN dbo.Questions Q ON Q.ID=A.QuestionID
		INNER JOIN dbo.QuestionSections QS ON QS.ID=Q.SectionID
		INNER JOIN Evaluations E on E.EvaluationID=A.EvaluationID
		INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasgGoals
		OUTER APPLY (
					SELECT CAST(SUM(CAST(A2.Answer AS decimal(5,2)))/COUNT(A2.Answer) as decimal(5,2)) AS Score FROM Answers A2
					INNER JOIN Questions Q2 on (Q2.ID=A2.QuestionID AND Q2.QuestionTypeID=1 AND isnull(A2.GoalID,0)=0)
					WHERE A2.EvaluationID=E.EvaluationID AND A2.State=2 AND	Q2.SectionID=Q.SectionID
				) EmpAnswers
		OUTER APPLY (
					SELECT CAST(SUM(CAST(A2.Answer AS decimal(5,2)))/COUNT(A2.Answer) as decimal(5,2)) AS Score FROM Answers A2
					INNER JOIN Questions Q2 on (Q2.ID=A2.QuestionID AND Q2.QuestionTypeID=1 AND isnull(A2.GoalID,0)=0)
					WHERE A2.EvaluationID=E.EvaluationID AND A2.State=4 AND	Q2.SectionID=Q.SectionID
					AND E.EmployeeID<>@userid --this is in order not to retrieve the evaluator's answer if you are the employee
				) EvalAnswers
		OUTER APPLY (
					SELECT CAST(SUM(CAST(A2.Answer AS decimal(5,2)))/COUNT(A2.Answer) as decimal(5,2)) AS Score FROM Answers A2
					INNER JOIN Questions Q2 on (Q2.ID=A2.QuestionID AND Q2.QuestionTypeID=1 AND isnull(A2.GoalID,0)=0)
					WHERE A2.EvaluationID=E.EvaluationID AND A2.State=5 AND	Q2.SectionID=Q.SectionID
					AND 1 = CASE WHEN E.EmployeeID=@userid AND E.State=6 THEN 1 WHEN E.EmployeeID<>@userid THEN 1 ELSE 0 END
					--AND E.EmployeeID<>@userid --this is in order not to retrieve the evaluator's answer if you are the employee
				) RevAnswers
		WHERE QS.HasScore=1 AND A.EvaluationID=@evalid;
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
   *	Get Evaluation Scores
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
		WHERE A.EvaluationID=E.EvaluationID AND A.State=2
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
							WHEN RL.state=3 THEN 'dotted'
							WHEN RL.state=4 THEN 'eval'
						END
				FROM ReportingLine RL
				INNER JOIN Evaluations E on E.EmployeeID=RL.empnosource
				WHERE E.EvaluationID=:evalID1 and rl.empnotarget=:userID1 AND rl.state=CASE WHEN e.State in (2,5) THEN 4 ELSE e.State END
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
	    DECLARE @evalid INT =:evalID;
		SELECT RL.state, CASE WHEN RL.state=5 THEN 'EMPLOYEE''S EVALUATOR'
		WHEN RL.state=4 THEN 'EMPLOYEE''S DOTTED LINE MANAGER'
		END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
		FROM ReportingLine RL
		INNER JOIN Evaluations E on E.EmployeeID=RL.empnosource
		LEFT JOIN EvaluationsCycle EC on EC.ID=E.CycleID
		LEFT JOIN vw_arco_employee EMP on EMP.empno=E.EmployeeID
		LEFT JOIN vw_arco_employee VEM on VEM.empno=RL.empnotarget
		WHERE E.EvaluationID=@evalid
		UNION
		SELECT 5, 'EMPLOYEE''S REVIEWER' AS 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
		FROM ReportingLine RL
		LEFT JOIN vw_arco_employee EMP on EMP.empno=RL.empnosource
		LEFT JOIN vw_arco_employee VEM on VEM.empno=RL.empnotarget
		WHERE RL.empnosource =(SELECT RL2.empnotarget FROM dbo.ReportingLine RL2  
		INNER JOIN dbo.Evaluations E2 ON E2.EmployeeID=RL2.empnosource AND RL2.state=5
		WHERE E2.EvaluationID=@evalid) AND RL.state=5
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
	 	DECLARE @empno as varchar(5) = :empno;
		DECLARE @CycleID AS INT =:cycleid;
		Declare @evaluator as varchar(5) = (
		   SELECT TOP 1 VEM.empno
		   FROM  vw_arco_employee VEM
		   LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnotarget AND RLE.empnosource=@empno AND RLE.goalCycle=@CycleID
		   LEFT JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@empno AND RL.empnosource NOT IN
		   (SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource=@empno AND goalCycle=@CycleID)
		   WHERE (COALESCE(RLE.empnosource, Rl.empnosource)=@empno) AND (COALESCE(RLE.State, RL.state) =5)
		) -- end of delclration to get evaluator of the employee
		SELECT COALESCE(RLE.STATE,RL.STATE), CASE WHEN COALESCE(RLE.STATE,RL.STATE)=5 THEN 'EMPLOYEE''S EVALUATOR'
					WHEN COALESCE(RLE.STATE,RL.STATE)=4 THEN 'EMPLOYEE''S DOTTED LINE MANAGER'
				END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
		FROM  vw_arco_employee VEM
		LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnotarget AND RLE.empnosource=@empno AND RLE.goalCycle=@CycleID
		LEFT JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@empno AND RL.empnosource NOT IN
		(SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource=@empno AND goalCycle=@CycleID)
		WHERE (COALESCE(RLE.empnosource, Rl.empnosource)=@empno) AND (COALESCE(RLE.State, RL.state) in (4,5))
		-- union to get reviwer
		UNION
		 SELECT 2, 'REVIEWER' as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
		FROM  vw_arco_employee VEM
		LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnotarget AND RLE.empnosource=@evaluator AND RLE.goalCycle=@CycleID
		LEFT JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@evaluator AND RL.empnosource NOT IN
		(SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource=@evaluator AND goalCycle=@CycleID)
		WHERE (COALESCE(RLE.empnosource, Rl.empnosource)=@evaluator) AND (COALESCE(RLE.State, RL.state) =5)
	 ";
	//  $queryString = "
	// 	 Declare @empno as varchar(5) = :empno;
	// 	 Declare @evaluator as varchar(5) = (SELECT empnotarget FROM ReportingLine WHERE empnosource=@empno AND state=4) ;
	// 	 SELECT RL.STATE, CASE WHEN RL.STATE=4 THEN 'EMPLOYEE''S EVALUATOR'
	// 				 WHEN RL.STATE=3 THEN 'EMPLOYEE''S DOTTED LINE MANAGER'
	// 			 END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
	// 	 VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc'
	// 	 FROM ReportingLine RL
	// 	 LEFT JOIN vw_arco_employee VEM on VEM.empno=RL.empnotarget
	// 	 WHERE RL.empnosource= @empno AND RL.State in (3,4)
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
        Declare @cycleid as int;
        Declare @userid as varchar(5);
        SELECT @userid=:userid;
		SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1
		SELECT G.gradeRange, sum(G.GoalConfiguration) as GoalConfiguration, sum(G.PendingEvaluator) as PendingEvaluator, sum(G.PendingEmployee) as PendingEmployee, sum(g.PendingDoted) as PendingDoted,
		sum(g.Complete) as Completed
		FROM
		  (			  SELECT Distinct RL.empnosource, CASE
			WHEN isnull(EMP.grade,0) between 0 and 3 THEN '0-3'
			WHEN EMP.grade between 4 and 9 THEN '4-9'
			WHEN EMP.grade >= 10 THEN '10'
			ELSE 'others' END AS gradeRange,
			CASE WHEN isnull(E.State,0) in (0,1) THEN 1 ELSE 0 END as GoalConfiguration,
			CASE
				WHEN isnull(E.State,0)=3 AND RL.State=3  THEN 1
				WHEN E.State=0 AND RL.State=2  THEN 1
				WHEN E.State=2 AND RL.State=2  THEN 1
			 ELSE 0
			END as PendingEvaluator,
			CASE WHEN E.State=2  THEN 1 ELSE 0 END as PendingEmployee,
			CASE WHEN E.State=3  THEN 1 ELSE 0 END as PendingDoted,
			--CASE WHEN E.State=5  THEN 1 ELSE 0 END as PendingReview,
			CASE WHEN E.State=5  THEN 1 ELSE 0 END as Complete
		  FROM  ReportingLine RL
		  INNER JOIN vw_arco_employee EMP ON EMP.empno=RL.empnosource
		  LEFT JOIN Evaluations E ON E.EmployeeID=RL.empnosource AND E.CycleID=@cycleid
		  WHERE RL.empnotarget=@userid AND isnull(EMP.grade,0)>=0
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
	if($state<>3)
		{
			$queryString = "
			Declare @evalid int = :evalid;
			Declare @userid varchar(5) = :userid;
			Declare @state int = :state;
			SELECT count(distinct a.userid) as cnt FROM ANSWERS A
			INNER JOIN Evaluations E on E.EvaluationID=A.EvaluationID
			WHERE E.EvaluationID=@evalid AND A.UserID<>@userid and a.State=@state
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
	Declare @evalid int = :evalid;
	Declare @userid varchar(5) = :userid;
	Declare @state int = :state;
	SELECT count(*) as cnt FROM ReportingLine RL
	INNER JOIN Evaluations E on E.EmployeeID=RL.empnosource
	WHERE E.EvaluationID=@evalid
	AND ((RL.state=CASE WHEN @state=2 or @state=5 THEN 4 ELSE @state END AND RL.empnotarget=@userid)
	OR (Rl.empnosource=@userid AND E.State=2))";
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
	Declare @evalid int = :evalid;
	Declare @userid varchar(5) = :userid;
	Declare @state int = :state;
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
                IF ( (isnull(@answer, '0')<>'0') OR (isnull(@goalid,'0')<>'0') OR (@questiontype = 2 AND @pause = 1) OR (@state=3 AND @finished =1))
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
			Declare @hasGoals int = (SELECT CASE WHEN count (*)>0 THEN 1 ELSE 0 END FROM Goals WHERE EvaluationID=@evalid);
			DECLARE @SectionID AS INT, @score AS DECIMAL(5,2), @weight AS	DECIMAL(5,2), @wscore AS DECIMAL(5,2),@scoreDesc AS VARCHAR(200);
            UPDATE dbo.Answers SET Finished=1 WHERE EvaluationID=@evalid AND State=@state AND UserID=@userid;

			--Check if we are at state 5 (review step). if the user clicks on submit revision, check if he answered any questions and copy the rest from state 4
			IF @state = 5
				BEGIN 
					INSERT INTO dbo.Answers
					(EvaluationID,QuestionID,Answer,State,UserID,GoalID,Date,Finished)
					SELECT  EvaluationID, QuestionID, Answer, 5, @userid, GoalID, Date, Finished FROM dbo.Answers 
					WHERE EvaluationID=@evalid AND State=4 AND
					(ISNULL(QuestionID,'')<>'' AND QuestionID NOT IN (SELECT QuestionID FROM dbo.Answers WHERE state=5 and EvaluationID=@evalid AND ISNULL(QuestionID,'')<>'')
					)
					union
					SELECT  EvaluationID, QuestionID, Answer, 5, @userid, GoalID, Date, Finished FROM dbo.Answers 
					WHERE EvaluationID=@evalid AND State=4 AND 
					(
					ISNULL(GoalID,'')<>'' AND GoalID NOT IN (SELECT GoalID FROM dbo.Answers WHERE state=5 and EvaluationID=@evalid AND ISNULL(GoalID,'')<>'')
					)
				END


				-- check if there is record in the evaluation scores otherwise create it
				UPDATE dbo.EvaluationScores SET EvaluationID=@evalid WHERE  EvaluationID=@evalid AND state=@state;
					IF @@ROWCOUNT = 0
					BEGIN
						INSERT INTO dbo.EvaluationScores VALUES(@evalid, @userid, @state,0, '', 0, 0,0, '', 0, 0,0, '', 0, 0,0, '',  0, 0,0,'');
					END
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
							 IF @SectionID=3 --goals scoring
							 BEGIN
								 SELECT
								 --@score=CAST([dbo].[ConvertGoalScore](G.Weight, A.Answer) AS DECIMAL(5,2))
								   @score=CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(G.Weight AS DECIMAL(5,2)) / 100)* cast(A.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2))
								 , @weight=QSW.weight,
								 @wscore=CAST(CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(G.Weight AS DECIMAL(5,2)) / 100)* cast(A.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2)) * QSW.weight AS DECIMAL(5,2)),
								 @scoreDesc=[dbo].[ConvertScoreToTextGoals](CAST([dbo].[ConvertGoalScore](CAST(SUM((CAST(G.Weight AS DECIMAL(5,2)) / 100)* cast(A.Answer as DECIMAL(5,2))) AS DECIMAL(5,2))) AS DECIMAL(5,2)))
								 FROM dbo.Evaluations E
								 INNER JOIN dbo.Answers A ON A.EvaluationID=E.EvaluationID
								 INNER JOIN dbo.Goals G on G.GoalID=A.GoalID
								 INNER JOIN dbo.QuestionSections QS ON QS.ID=@SectionID
								 INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasGoals
								 WHERE E.EvaluationID=@evalid AND A.State=@state
								 GROUP BY QSW.weight
								 -- update
								 UPDATE dbo.EvaluationScores SET GScore=@score, GWeight=@weight, GWeightedScore=@wscore, GSDEscription=@scoreDesc
							 	 WHERE  EvaluationID=@evalid AND UserID=@userid AND state=@state
							 END
							 ELSE IF @SectionID=2
							 BEGIN --PerformanceScore
								 SELECT
								 @score=CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)), @weight=QSW.weight,
								 @wscore=CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) *QSW.weight AS DECIMAL(5,2)),
								 @scoreDesc=[dbo].[ConvertScoreToTextPCStandards](CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)))
								 FROM dbo.Evaluations E
								 INNER JOIN dbo.Answers A ON A.EvaluationID=E.EvaluationID
								 INNER JOIN Questions Q on Q.ID=A.QuestionID
								 INNER JOIN dbo.QuestionSections QS ON QS.ID=Q.SectionID
								 INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasGoals
								 WHERE E.EvaluationID=@evalid AND A.State=@state AND QS.HasScore=1 AND QS.ID=@SectionID AND Q.QuestionTypeID=1 AND isnull(A.GoalID,0)=0
								 GROUP BY QSW.weight
								 -- update
								 UPDATE dbo.EvaluationScores SET PScore=@score, PWeight=@weight, PWeightedScore=@wscore, PSDEscription=@scoreDesc
							 WHERE  EvaluationID=@evalid AND UserID=@userid AND state=@state
							 END
							 ELSE IF @SectionID=4
							 BEGIN --CoreCompetencies Score
								 SELECT
								 @score=CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)), @weight=QSW.weight,
								 @wscore=CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) *QSW.weight AS DECIMAL(5,2)),
								 @scoreDesc=[dbo].[ConvertScoreToTextPCStandards](CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)))
								 FROM dbo.Evaluations E
								 INNER JOIN dbo.Answers A ON A.EvaluationID=E.EvaluationID
								 INNER JOIN Questions Q on Q.ID=A.QuestionID
								 INNER JOIN dbo.QuestionSections QS ON QS.ID=Q.SectionID
								 INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasGoals
								 WHERE E.EvaluationID=@evalid AND A.State=@state AND QS.HasScore=1 AND QS.ID=@SectionID AND Q.QuestionTypeID=1 AND isnull(A.GoalID,0)=0
								 GROUP BY QSW.weight
								 -- update
								 UPDATE dbo.EvaluationScores SET CScore=@score, CWeight=@weight, CWeightedScore=@wscore, CSDEscription=@scoreDesc
							 	 WHERE  EvaluationID=@evalid AND UserID=@userid AND state=@state
							 END
							 ELSE IF @SectionID=5
							 BEGIN --Leadership Score
								 SELECT
								 @score=CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)), @weight=QSW.weight,
								 @wscore=CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) *QSW.weight AS DECIMAL(5,2)),
								 @scoreDesc=[dbo].[ConvertScoreToTextPCStandards](CAST(SUM(CAST (A.Answer AS DECIMAL(5,2)))/COUNT(A.Answer) AS DECIMAL(5,2)))
								 FROM dbo.Evaluations E
								 INNER JOIN dbo.Answers A ON A.EvaluationID=E.EvaluationID
								 INNER JOIN Questions Q on Q.ID=A.QuestionID
								 INNER JOIN dbo.QuestionSections QS ON QS.ID=Q.SectionID
								 INNER JOIN QuestionSectionWeights QSW on QS.ID=QSW.sectionid AND QSW.gradeLessThan4=CASE WHEN E.empGrade<4 THEN 1 ELSE 0 END AND QSW.forManager=E.ManagesTeam AND QSW.withGoals=@hasGoals
								 WHERE E.EvaluationID=@evalid AND A.State=@state AND QS.HasScore=1 AND QS.ID=@SectionID AND Q.QuestionTypeID=1 AND isnull(A.GoalID,0)=0
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
            DECLARE @evalCount int = (SELECT count(*) from ReportingLine RL
            INNER JOIN Evaluations E ON E.EmployeeID=RL.empnosource AND E.EvaluationID=@evalid AND CASE WHEN E.State=5 THEN 4 ELSE E.STATE END=Rl.state);

			-- get dotted required
			DECLARE @dotedCount int =(SELECT count(*) from ReportingLine RL
            INNER JOIN Evaluations E ON E.EmployeeID=RL.empnosource AND E.EvaluationID=@evalid AND Rl.state=3);

			--get how many have answered
            DECLARE @actualCount int = (SELECT count(distinct UserID) from Answers WHERE State =@state and Finished=1 AND EvaluationID=@evalid);

            IF (@actualCount=@evalCount OR @state=2)
                BEGIN
					-- This part was applied so that to move forward the process if dotted is not required for this evaluation.
					IF(@state=2 AND @dotedCount=0)
						BEGIN
						SELECT @nextState=4;
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
     *	Revise Selected Evaluations: revise evaluations of them that have state 5, clone state 4 answers and update evaluations state.
     *
     */
	 public function reviseEvaluations($evaluations, $userid)
	 {
 
	 foreach ($evaluations as &$evaluation) 
	 	{
		 //validate if state has changed
			$queryString = "
			Declare @evalid int = :evalid;
			SELECT count(*) as cnt FROM dbo.Evaluations WHERE EvaluationID=@evalid and State=5";
		
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
		SELECT  EvaluationID, QuestionID, Answer, 5, @userid, GoalID, Date, Finished FROM dbo.Answers 
		WHERE EvaluationID=@evalid AND State=4
		--Clone scores
		INSERT INTO dbo.EvaluationScores
		(EvaluationID,UserID,State,PScore,PSDescription,PWeight,PWeightedScore,GScore,GSDescription,GWeight,
		GWeightedScore,CScore,CSDescription,CWeight,CWeightedScore,LScore,LSDescription,LWeight,LWeightedScore,
		OverallScore,OSDescription)
		SELECT EvaluationID, @userid, 5, PScore, PSDescription, PWeight, PWeightedScore,GScore,GSDescription,GWeight,
		GWeightedScore,CScore,CSDescription,CWeight,CWeightedScore,LScore,LSDescription,LWeight,LWeightedScore,
		OverallScore,OSDescription FROM dbo.EvaluationScores WHERE EvaluationID=@evalid AND State=4;
		--Update evaluation state
		UPDATE dbo.Evaluations SET State=6, StateDate=GETDATE() WHERE EvaluationID=@evalid
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
            Declare @cycleid as int =:cycleid;
            Declare @evalid	as int;
            Declare @grade as varchar(2);
            -- SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1 to be used later in 2017
            UPDATE dbo.Evaluations SET ManagesTeam=:mteam WHERE State in (0,1) AND EmployeeID=:empno AND CycleID=@cycleid AND UserID=:userid
            IF @@ROWCOUNT = 0
                  BEGIN
                      SELECT @grade=grade from vw_arco_employee where empno=:empno1
					  INSERT INTO dbo.Evaluations 
					  OUTPUT Inserted.EvaluationID 
					  VALUES(@cycleid, :empno2, @grade, 0, getdate(), :mteam1, :userid1, NULL, NULL);
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
			Declare @evalid as int = :evalid;
			Declare @cycleid as int = :cycleid;
			Declare @userid as varchar(5) =:userid;
			Declare @empno as varchar(5) =:empno;
			Declare @grade as int;
			Declare @state as int;
			Declare @answerCount as int;
			--Declare @goalsWeight as int = 100;
			Declare @onBehalf as int =:onbehalf;
			Declare @hasDotted as int;
			--Define if employee has dotted required only if state less than 4
			IF @state<4
			BEGIN
				IF (SELECT count(*) FROM dbo.ReportingLineExceptions 
					WHERE empnosource = (SELECT EmployeeID FROM dbo.Evaluations WHERE EvaluationID=@evalid) AND goalCycle=@cycleid)>0
					BEGIN
						 SELECT @hasDotted = CASE WHEN count(*)>0 THEN 1 ELSE 0 END FROM dbo.ReportingLineExceptions 
						WHERE empnosource = (SELECT EmployeeID FROM dbo.Evaluations WHERE EvaluationID=@evalid) AND goalCycle=@cycleid AND State=4
					END
				ELSE
					BEGIN
						SELECT @hasDotted = CASE WHEN count(*)>0 THEN 1 ELSE 0 END FROM dbo.ReportingLine 
						WHERE empnosource = (SELECT EmployeeID FROM dbo.Evaluations WHERE EvaluationID=@evalid) AND excludeFromCycles<>@cycleid AND State=4
					END
			END


			SELECT @evalid=E.EvaluationID, @state=E.State, @grade=HR.Grade
			FROM Evaluations E
			INNER JOIN dbo.vw_arco_employee HR on HR.empno=E.EmployeeID
			WHERE (E.CycleID=@cycleid AND E.EmployeeID=@empno) or (EvaluationID=@evalid);

			-- extra validation to avoid for state 2 and above moving state while there are no answers.
			SELECT @answerCount=COUNT(*) FROM ANSWERS WHERE EvaluationID=@evalid and state=@state;

			--missing, goals validation to update when state is 1 and goals are required to allow you to go ahead.
			UPDATE dbo.Evaluations SET State=
			CASE
				WHEN @state=0 AND @hasDotted=1 THEN 1
				WHEN @state=0 AND @hasDotted=0 THEN 2
				WHEN @state=0 AND @grade<4 THEN 5 --Sent Directly to Evaluator, shouldnt have dotted to go to
				WHEN @state in (0,1,2) THEN @state+1
				WHEN @state in (3,4,5,6) AND @answerCount>0 THEN @state+1
				--ELSE @state
			END,
			StateDate=getdate()
			OUTPUT Inserted.EvaluationID
			WHERE EvaluationID=@evalid;
			
			--Once Update is done, go and insert history for goals if state between 0 and 3
			IF @state<4
			BEGIN
				IF @onBehalf = 0
					BEGIN
					INSERT INTO dbo.GoalsHistory
					(GoalID,EvaluationID,GoalDescription,Weight,UserID,AttributeCode,State,Date)
					SELECT GoalID, EvaluationID, GoalDescription, Weight, UserID, AttributeCode, State, GETDATE() FROM dbo.Goals WHERE State=@state AND UserID=@userid
					END
				IF @onBehalf = 1
					BEGIN
					INSERT INTO dbo.GoalsHistory
					(GoalID,EvaluationID,GoalDescription,Weight,UserID,AttributeCode,State,Date)
					SELECT '', @evalid, 'Moved Forward', '', @userid, '', @state, GETDATE()
					END
			END
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
	 	   	$id = $query->fetch();
			$evalid=$id["EvaluationID"];
            $queryString = "
            SELECT E.EvaluationID, E.State, CONVERT(DATETIME2(0),E.StateDate) as StateDate, S.StateDescription
            FROM Evaluations E
            INNER JOIN StateRef S on S.State=E.State
            WHERE EvaluationID = :evalid
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
    *	Set wrong manager: evaluator or dotted line manager highlites a step for an employee as wrong. Basically notifying the system that another employee should do that step
    *
    */
    public function SetWrongManager($empno, $state)
   {
   		$queryString = "
   		Declare @empno as varchar(5) =:empno;
   		Declare @state as int=:state;
   		UPDATE dbo.ReportingLine SET wrongmanager=1 WHERE  empnosource=@empno and state=@state;
   		";
   		$query = $this->connection->prepare($queryString);
   		$query->bindValue(':empno', $empno, PDO::PARAM_STR);
   		$query->bindValue(':state', $state, PDO::PARAM_INT);
   		$result["success"] = $query->execute();
   		$result["errorMessage"] = $query->errorInfo();
   		return $result;
   }

   /*****
    *	Revert wrong manager: revert the action of SetWrongManager
    *
    */
    public function RevertWrongManager($empno, $state)
   {
   		$queryString = "
   		Declare @empno as varchar(5) =:empno;
   		Declare @state as int=:state;
   		UPDATE dbo.ReportingLine SET wrongmanager=0 WHERE empnosource=@empno and state=@state;
   		";
   		$query = $this->connection->prepare($queryString);
   		$query->bindValue(':empno', $empno, PDO::PARAM_STR);
   		$query->bindValue(':state', $state, PDO::PARAM_INT);
   		$result["success"] = $query->execute();
   		$result["errorMessage"] = $query->errorInfo();
   		return $result;
   }


} // END OF CLASS

?>
