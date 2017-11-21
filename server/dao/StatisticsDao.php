<?php

class StatisticsDAO{

	private $connection = NULL;


	public function __construct($conn)
	{
		$this->connection = $conn;
	}


    /*****
     *	Get Evaluatiors' Evaluations
     *
     */

    public function GetEvaluatorsEvals($filters)
   {
   		$queryString = "
			DECLARE @sql NVARCHAR(max);
			DECLARE @ParmDefinition NVARCHAR(max);
			DECLARE  @evaluator NVARCHAR(5)=:evaluator,@reviewer VARCHAR(5)=:reviewer, @form NVARCHAR(10)=:form, @employee NVARCHAR(100)=:employee, @position NVARCHAR(100)=:position, @region NVARCHAR(4) =:region,
			@coreDesc NVARCHAR(100)=:coreDesc, @goalsDesc NVARCHAR(100)=:goalsDesc, @leadershipDesc NVARCHAR(100)=:leadershipDesc, @performanceDesc NVARCHAR(100)=:performanceDesc, @overallDesc NVARCHAR(100)=:overallDesc,
			@empcoreDesc NVARCHAR(100)=:empcoreDesc, @empgoalsDesc NVARCHAR(100)=:empgoalsDesc, @empleadershipDesc NVARCHAR(100)=:empleadershipDesc, @empperformanceDesc NVARCHAR(100)=:empperformanceDesc,
			@empoverallDesc NVARCHAR(100)=:empoverallDesc, @myStatistics INT =:myStatistics, @cycleID INT=:cycleid, @typeOfStats INT=:typeOfStats, @calibrated INT=:calibrated;
			SELECT @sql=N'
			--Declare @cycleid as int;
			--SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
			SELECT eval.empno as ''Evaluator'', eval.family_name+'' ''+eval.first_name AS EvalName, emp.empno, emp.family_name+'' ''+emp.first_name AS Name, CASE WHEN e.empGrade<4 THEN ''Grades 0-4'' WHEN emp.grade<10 THEN ''Grades 4-9'' ELSE ''Grades 10+'' END AS Form,
			   emp.job_desc, emp.region, empScore.PScore AS EPScore, empScore.PSDescription AS EPSDescr, empScore.GScore AS EGScore, empscore.GSDescription AS EGSDescr,
			   empScore.CScore AS ECScore, empScore.CSDescription AS ECSDescr, empScore.LScore AS ELScore, empScore.LSDescription AS ELSDescr,  empScore.OverallScore AS EOScore,
			   empScore.OSDescription AS EOSDescr, evalScore.PScore as EVPScore, evalScore.PSDescription AS EVPSDescr, evalScore.GScore as EVGscore, evalScore.GSDescription AS EVGSDescr, evalScore.CScore as EVCScore,
			   evalScore.CSDescription AS EVCSDescr, evalScore.LScore EVLScore, evalScore.LSDescription AS EVLSDescr, evalScore.OverallScore  AS EVOScore, evalScore.OSDescription AS EVODescr,
			   evalScore.PScore-empScore.PScore AS PGap, evalScore.GScore-empScore.GScore AS GGap, evalScore.CScore-empScore.CScore AS CGap, evalScore.LScore-empScore.LScore AS LGap,
			   evalScore.OverallScore-empScore.OverallScore AS OGap
			FROM dbo.Evaluations E
			INNER JOIN dbo.vw_arco_employee emp ON emp.empno = e.EmployeeID
			INNER JOIN reportingLine RLM on RLM.empnosource=e.EmployeeID';
			SELECT @sql=@sql+ CASE WHEN @myStatistics=0 OR @myStatistics=1 THEN
			' AND RLM.State=4' 
			WHEN @myStatistics=2 THEN ' 
			AND RLM.State=3
			INNER JOIN reportingLine RLD on RLD.empnosource=e.employeeID AND RLD.State=4
			' END 
			SELECT @sql=@sql+ '
			INNER JOIN dbo.vw_arco_employee eval ON eval.empno = ' 
			SELECT @sql=@sql+ CASE WHEN @myStatistics=2 THEN ' RLD.empnotarget ' ELSE  ' RLM.empnotarget ' END
			SELECT @sql=@sql+ '
			OUTER APPLY(
			SELECT * FROM dbo.EvaluationScores
			WHERE EvaluationID=E.EvaluationID AND State=2
			)empScore
			OUTER APPLY(
			SELECT * FROM dbo.EvaluationScores
			WHERE EvaluationID=E.EvaluationID AND State=CASE WHEN @calibrated=0 THEN 4 WHEN @calibrated=1 THEN 5 END
			)evalScore
			WHERE E.CycleID=@cycleid AND E.EmployeeID IN
			(SELECT RL.empnosource FROM dbo.ReportingLine RL WHERE RL.empnotarget '

			SELECT @sql=@sql+ CASE WHEN @myStatistics=0 THEN 'in (SELECT emp.empno FROM dbo.ReportingLine RL
			INNER JOIN dbo.vw_arco_employee emp ON emp.empno = RL.empnosource
			OUTER APPLY(
			SELECT COUNT(RL2.empnosource) AS count FROM dbo.ReportingLine RL2 WHERE RL2.empnotarget= RL.empnosource AND RL2.state=4
			AND ISNULL(RL2.excludeFromCycles,0)<>@cycleid
			)Evals
			WHERE RL.empnotarget=@reviewer AND RL.state=4 AND ISNULL(RL.excludeFromCycles,0)<>@cycleid AND Evals.count>0) AND RL.State=4 '
			WHEN @myStatistics=1  THEN '=@evaluator AND RL.State=4 '
		    WHEN @myStatistics=2  THEN '=@evaluator AND RL.State=3 'END;

			SELECT @sql=@sql+' AND ISNULL(RL.excludeFromCycles,0)<>@cycleid) ';
			--calibrated
			IF @calibrated=0
			BEGIN 
			SELECT @sql=@sql+' AND E.State IN (5,6)';
			END
			IF @calibrated=1
			BEGIN 
			SELECT @sql=@sql+' AND E.State=6';
			END
			

			SET @ParmDefinition = N'@form NVARCHAR(10), @evaluator NVARCHAR(5),@employee NVARCHAR(100), @position NVARCHAR(100), @region NVARCHAR(4),
			@coreDesc NVARCHAR(100), @goalsDesc NVARCHAR(100), @leadershipDesc NVARCHAR(100), @performanceDesc NVARCHAR(100), @overallDesc NVARCHAR(100),
			@empcoreDesc NVARCHAR(100), @empgoalsDesc NVARCHAR(100), @empleadershipDesc NVARCHAR(100), @empperformanceDesc NVARCHAR(100), @empoverallDesc NVARCHAR(100),
			@reviewer NVARCHAR(5), @myStatistics INT, @cycleID INT, @typeOfStats INT, @calibrated INT'

			--main filters
			IF @evaluator IS NOT NULL AND @evaluator <> ''
			BEGIN
					 SELECT @sql = @sql + ' AND RLM.empnotarget=@evaluator'
			END

			IF @form IS NOT NULL AND @form <> '' AND @form <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND E.empGrade>= CASE WHEN @form=''1_3'' THEN 0 WHEN @form=''4_9'' THEN 4 WHEN @form=''10'' THEN 10 END AND  E.empGrade<= CASE WHEN @form=''1_3'' THEN 3 WHEN @form=''4_9'' THEN 9 WHEN @form=''10'' THEN 20 END'
			END
			IF @employee IS NOT NULL AND @employee<>''
			BEGIN
				 SELECT @sql = @sql + ' AND (emp.family_name like ''%'+@employee+'%'' OR emp.first_name like ''%'+@employee+'%'' OR emp.empno=@employee)'
			END
			IF @position IS NOT NULL AND  @position<>''
			BEGIN
				 SELECT @sql = @sql + ' AND emp.job_desc like ''%'+@position+'%'''
			END
			IF @region IS NOT NULL AND @region<>''
			BEGIN
				 SELECT @sql = @sql + ' AND emp.region like ''%'+@region+'%'''
			END
			--evaluator scores
			IF @coreDesc IS NOT NULL AND @coreDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND evalScore.CSDescription=@coreDesc'
			END
			IF @goalsDesc IS NOT NULL AND @goalsDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND evalScore.GSDescription=@goalsDesc'
			END
			IF @leadershipDesc IS NOT NULL AND @leadershipDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND evalScore.LSDescription=@leadershipDesc'
			END
			IF @performanceDesc IS NOT NULL AND @performanceDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND evalScore.PSDescription=@performanceDesc'
			END
			IF @overallDesc IS NOT NULL AND @overallDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND evalScore.OSDescription=@overallDesc'
			END

			--employye scores
			IF @empcoreDesc IS NOT NULL AND @empcoreDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND empScore.CSDescription=@empcoreDesc'
			END
			IF @empgoalsDesc IS NOT NULL AND @empgoalsDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND empScore.GSDescription=@empgoalsDesc'
			END
			IF @empleadershipDesc IS NOT NULL AND @empleadershipDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND empScore.LSDescription=@empleadershipDesc'
			END
			IF @empperformanceDesc IS NOT NULL AND @empperformanceDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND empScore.PSDescription=@empperformanceDesc'
			END
			IF @empoverallDesc IS NOT NULL AND @empoverallDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND empScore.OSDescription=@empoverallDesc'
			END
			-- ORDER BY
			IF @typeOfStats = 0 -- Evlauator's Score Report Order By Evaluator, and OvPerformance
			BEGIN
				SELECT @sql= @sql+' ORDER BY RLM.empnotarget asc, evalScore.OverallScore desc';
			END
			IF @typeOfStats = 1 -- Gap report order by gap
			BEGIN
				SELECT @sql= @sql+' ORDER BY evalScore.OverallScore-empScore.OverallScore desc';
			END
			EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @evaluator=@evaluator, @form=@form, @position=@position, @region=@region, @employee=@employee, @coreDesc=@coreDesc,
			@goalsDesc=@goalsDesc,@leadershipDesc=@leadershipDesc,@performanceDesc=@performanceDesc,@overallDesc=@overallDesc, @empcoreDesc=@coreDesc, @empgoalsDesc=@empgoalsDesc,
			@empleadershipDesc=@empleadershipDesc,@empperformanceDesc=@empperformanceDesc,@empoverallDesc=@empoverallDesc, @reviewer=@reviewer, @myStatistics=@myStatistics,
			@cycleID=@cycleID, @typeOfStats=@typeOfStats, @calibrated=@calibrated
   		";
   		$query = $this->connection->prepare($queryString);
   		$query->bindValue(':evaluator', $filters['evaluator'], PDO::PARAM_STR);
		$query->bindValue(':reviewer', $filters['loggedin_user'], PDO::PARAM_STR);
		$query->bindValue(':employee', $filters['employee'], PDO::PARAM_STR);
		$query->bindValue(':form', $filters['grade'], PDO::PARAM_STR);
		$query->bindValue(':position', $filters['position'], PDO::PARAM_STR);
		$query->bindValue(':region', $filters['region'], PDO::PARAM_STR);
		$query->bindValue(':coreDesc', $filters['core_comp_descr'], PDO::PARAM_STR);
		$query->bindValue(':goalsDesc', $filters['goals_descr'], PDO::PARAM_STR);
		$query->bindValue(':leadershipDesc', $filters['lead_comp_descr'], PDO::PARAM_STR);
		$query->bindValue(':performanceDesc', $filters['perf_stand_descr'], PDO::PARAM_STR);
		$query->bindValue(':overallDesc', $filters['over_perf_descr'], PDO::PARAM_STR);
		$query->bindValue(':empcoreDesc', $filters['emp_core_comp_descr'], PDO::PARAM_STR);
		$query->bindValue(':empgoalsDesc', $filters['emp_goals_descr'], PDO::PARAM_STR);
		$query->bindValue(':empleadershipDesc', $filters['emp_lead_comp_descr'], PDO::PARAM_STR);
		$query->bindValue(':empperformanceDesc', $filters['emp_perf_stand_descr'], PDO::PARAM_STR);
		$query->bindValue(':empoverallDesc', $filters['emp_over_perf_descr'], PDO::PARAM_STR);
		$query->bindValue(':myStatistics', $filters['myStatistics'], PDO::PARAM_INT);
		$query->bindValue(':cycleid', $filters['cycleid'], PDO::PARAM_INT);
		$query->bindValue(':typeOfStats', $filters['type_of_statistics'], PDO::PARAM_INT);
		$query->bindValue(':calibrated', $filters['calibrated'], PDO::PARAM_INT);
   		$result["success"] = $query->execute();
   		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
        $result["evaluations"] = $query->fetchAll();
   		return $result;
   }

   /*****
	*	Get Plot Chart Data, and gaps plot
	*
	*/

   public function GetPlotChart($filters)
  {
	   $queryString = "
		   DECLARE @sql NVARCHAR(max);
		   DECLARE @ParmDefinition NVARCHAR(max);
		   DECLARE  @evaluator NVARCHAR(5)=:evaluator,@reviewer VARCHAR(5)=:reviewer, @form NVARCHAR(10)=:form, @employee NVARCHAR(100)=:employee, @position NVARCHAR(100)=:position, @region NVARCHAR(4) =:region,
		   @coreDesc NVARCHAR(100)=:coreDesc, @goalsDesc NVARCHAR(100)=:goalsDesc, @leadershipDesc NVARCHAR(100)=:leadershipDesc, @performanceDesc NVARCHAR(100)=:performanceDesc, @overallDesc NVARCHAR(100)=:overallDesc,
		   @empcoreDesc NVARCHAR(100)=:empcoreDesc, @empgoalsDesc NVARCHAR(100)=:empgoalsDesc, @empleadershipDesc NVARCHAR(100)=:empleadershipDesc, @empperformanceDesc NVARCHAR(100)=:empperformanceDesc,
		   @empoverallDesc NVARCHAR(100)=:empoverallDesc, @myStatistics INT =:myStatistics, @cycleID INT=:cycleid, @calibrated INT=:calibrated;
		   SELECT @sql=N'
		   --Declare @cycleid as int;
		   --SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		   SELECT  MIN(evalScore.PScore) as MinPerfScore, MAX(evalScore.PScore) as MaxPerfScore, CAST(AVG(evalScore.PScore) AS DECIMAL(5,2)) as AvgPerfScore,
		   MIN(NULLIF(evalScore.GScore,0)) as MinGoalScore, MAX(evalScore.GScore) as MaxGoalScore, CAST(AVG(NULLIF(evalScore.GScore,0)) AS DECIMAL(5,2)) as AvgGoalScore,
		   MIN(evalScore.CScore) as MinCoreCompScore, MAX(evalScore.CScore) as MaxCoreCompScore, CAST(AVG(evalScore.CScore) AS DECIMAL(5,2)) as AvgCoreCompScore,
		   MIN(NULLIF(evalScore.LScore,0)) as MinLeadershipScore, MAX(NULLIF(evalScore.LScore,0)) as MaxLeadershipScore, CAST(AVG(NULLIF(evalScore.LScore,0)) AS DECIMAL(5,2)) as AvgLeadershipScore,
		   MIN(evalScore.OverallScore) MinOverallScore, MAX(evalScore.OverallScore) MaxOverallScore, CAST(AVG(evalScore.OverallScore) AS DECIMAL(5,2)) AvgOverallScore,
		   CAST(AVG(empScore.PScore) AS DECIMAL(5,2)) EmpAvgPerfScore,
		   CAST(AVG(empScore.GScore) AS DECIMAL(5,2)) EmpAvgGoalScore,
		   CAST(AVG(empScore.CScore) AS DECIMAL(5,2)) EmpAvgCoreCompScore,
		   CAST(AVG(empScore.LScore) AS DECIMAL(5,2)) EmpAvgLeadershipScore,
		   CAST(AVG(empScore.OverallScore) AS DECIMAL(5,2)) EmpAvgOverallScore

		   FROM dbo.Evaluations E
		   INNER JOIN dbo.vw_arco_employee emp ON emp.empno = e.EmployeeID
		   INNER JOIN reportingLine RLM on RLM.empnosource=e.EmployeeID';
		   SELECT @sql=@sql+ CASE WHEN @myStatistics=0 OR @myStatistics=1 THEN
		   ' AND RLM.State=4' WHEN @myStatistics=2 THEN ' AND RLM.State=3' END 

		   SELECT @sql=@sql+ '
		   OUTER APPLY(
		   SELECT * FROM dbo.EvaluationScores
		   WHERE EvaluationID=E.EvaluationID AND State=2
		   )empScore
		   OUTER APPLY(
		   SELECT * FROM dbo.EvaluationScores
		   WHERE EvaluationID=E.EvaluationID AND State=CASE WHEN @calibrated=0 THEN 4 WHEN @calibrated=1 THEN 5 END
		   )evalScore
		   WHERE E.CycleID=@cycleid AND E.EmployeeID IN
		   (SELECT RL.empnosource FROM dbo.ReportingLine RL WHERE RL.empnotarget '
		   SELECT @sql=@sql+ CASE WHEN @myStatistics=0 THEN 'in (SELECT emp.empno FROM dbo.ReportingLine RL
					INNER JOIN dbo.vw_arco_employee emp ON emp.empno = RL.empnosource
					OUTER APPLY(
					SELECT COUNT(RL2.empnosource) AS count FROM dbo.ReportingLine RL2 WHERE RL2.empnotarget= RL.empnosource AND RL2.state=4
					AND ISNULL(RL2.excludeFromCycles,0)<>@cycleid
					)Evals
					WHERE RL.empnotarget=@reviewer AND RL.state=4 AND ISNULL(RL.excludeFromCycles,0)<>@cycleid AND Evals.count>0)'
		   WHEN @myStatistics=1 THEN '=@evaluator AND RL.State=4'
		   WHEN @myStatistics=2 THEN '=@evaluator AND RL.State=3 'END;

		   SELECT @sql=@sql+' AND ISNULL(RL.excludeFromCycles,0)<>@cycleid ) ';
		   --calibrated
		   IF @calibrated=0
		   BEGIN 
		   SELECT @sql=@sql+' AND E.State IN (5,6)';
		   END
		   IF @calibrated=1
		   BEGIN 
		   SELECT @sql=@sql+' AND E.State=6';
		   END
		   SET @ParmDefinition = N'@form NVARCHAR(10), @evaluator NVARCHAR(5),@employee NVARCHAR(100), @position NVARCHAR(100), @region NVARCHAR(4),
		   @coreDesc NVARCHAR(100), @goalsDesc NVARCHAR(100), @leadershipDesc NVARCHAR(100), @performanceDesc NVARCHAR(100), @overallDesc NVARCHAR(100),
		   @empcoreDesc NVARCHAR(100), @empgoalsDesc NVARCHAR(100), @empleadershipDesc NVARCHAR(100), @empperformanceDesc NVARCHAR(100), @empoverallDesc NVARCHAR(100),
		   @reviewer NVARCHAR(5), @myStatistics INT, @cycleID INT, @calibrated INT'

		   --main filters
		   IF @evaluator IS NOT NULL AND @evaluator <> ''
		   BEGIN
					SELECT @sql = @sql + ' AND RLM.empnotarget=@evaluator'
		   END

		   IF @form IS NOT NULL AND @form <> '' AND @form <> 'Select All'
		   BEGIN
				SELECT @sql = @sql + ' AND E.empGrade>= CASE WHEN @form=''1_3'' THEN 0 WHEN @form=''4_9'' THEN 4 WHEN @form=''10'' THEN 10 END AND  E.empGrade<= CASE WHEN @form=''1_3'' THEN 3 WHEN @form=''4_9'' THEN 9 WHEN @form=''10'' THEN 20 END'
		   END
		   IF @employee IS NOT NULL AND @employee<>''
		   BEGIN
				SELECT @sql = @sql + ' AND (emp.family_name like ''%'+@employee+'%'' OR emp.first_name like ''%'+@employee+'%'' OR emp.empno=@employee)'
		   END
		   IF @position IS NOT NULL AND  @position<>''
		   BEGIN
				SELECT @sql = @sql + ' AND emp.job_desc like ''%'+@position+'%'''
		   END
		   IF @region IS NOT NULL AND @region<>''
		   BEGIN
				SELECT @sql = @sql + ' AND emp.region like ''%'+@region+'%'''
		   END
		   --evaluator scores
		   IF @coreDesc IS NOT NULL AND @coreDesc <> 'Select All'
		   BEGIN
				SELECT @sql = @sql + ' AND evalScore.CSDescription=@coreDesc'
		   END
		   IF @goalsDesc IS NOT NULL AND @goalsDesc <> 'Select All'
		   BEGIN
				SELECT @sql = @sql + ' AND evalScore.GSDescription=@goalsDesc'
		   END
		   IF @leadershipDesc IS NOT NULL AND @leadershipDesc <> 'Select All'
		   BEGIN
				SELECT @sql = @sql + ' AND evalScore.LSDescription=@leadershipDesc'
		   END
		   IF @performanceDesc IS NOT NULL AND @performanceDesc <> 'Select All'
		   BEGIN
				SELECT @sql = @sql + ' AND evalScore.PSDescription=@performanceDesc'
		   END
		   IF @overallDesc IS NOT NULL AND @overallDesc <> 'Select All'
		   BEGIN
				SELECT @sql = @sql + ' AND evalScore.OSDescription=@overallDesc'
		   END

		   --employye scores
		   IF @empcoreDesc IS NOT NULL AND @empcoreDesc <> 'Select All'
		   BEGIN
				SELECT @sql = @sql + ' AND empScore.CSDescription=@empcoreDesc'
		   END
		   IF @empgoalsDesc IS NOT NULL AND @empgoalsDesc <> 'Select All'
		   BEGIN
				SELECT @sql = @sql + ' AND empScore.GSDescription=@empgoalsDesc'
		   END
		   IF @empleadershipDesc IS NOT NULL AND @empleadershipDesc <> 'Select All'
		   BEGIN
				SELECT @sql = @sql + ' AND empScore.LSDescription=@empleadershipDesc'
		   END
		   IF @empperformanceDesc IS NOT NULL AND @empperformanceDesc <> 'Select All'
		   BEGIN
				SELECT @sql = @sql + ' AND empScore.PSDescription=@empperformanceDesc'
		   END
		   IF @empoverallDesc IS NOT NULL AND @empoverallDesc <> 'Select All'
		   BEGIN
				SELECT @sql = @sql + ' AND empScore.OSDescription=@empoverallDesc'
		   END

		   EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @evaluator=@evaluator, @form=@form, @position=@position, @region=@region, @employee=@employee, @coreDesc=@coreDesc,
		   @goalsDesc=@goalsDesc,@leadershipDesc=@leadershipDesc,@performanceDesc=@performanceDesc,@overallDesc=@overallDesc, @empcoreDesc=@coreDesc,
		   @empgoalsDesc=@empgoalsDesc,@empleadershipDesc=@empleadershipDesc,@empperformanceDesc=@empperformanceDesc,@empoverallDesc=@empoverallDesc, @reviewer=@reviewer, 
		   @myStatistics=@myStatistics, @cycleID = @cycleID, @calibrated=@calibrated;
	   ";
	   $query = $this->connection->prepare($queryString);
	   $query->bindValue(':evaluator', $filters['evaluator'], PDO::PARAM_STR);
	   $query->bindValue(':reviewer', $filters['loggedin_user'], PDO::PARAM_STR);
	   $query->bindValue(':employee', $filters['employee'], PDO::PARAM_STR);
	   $query->bindValue(':form', $filters['grade'], PDO::PARAM_STR);
	   $query->bindValue(':position', $filters['position'], PDO::PARAM_STR);
	   $query->bindValue(':region', $filters['region'], PDO::PARAM_STR);
	   $query->bindValue(':coreDesc', $filters['core_comp_descr'], PDO::PARAM_STR);
	   $query->bindValue(':goalsDesc', $filters['goals_descr'], PDO::PARAM_STR);
	   $query->bindValue(':leadershipDesc', $filters['lead_comp_descr'], PDO::PARAM_STR);
	   $query->bindValue(':performanceDesc', $filters['perf_stand_descr'], PDO::PARAM_STR);
	   $query->bindValue(':overallDesc', $filters['over_perf_descr'], PDO::PARAM_STR);
	   $query->bindValue(':empcoreDesc', $filters['emp_core_comp_descr'], PDO::PARAM_STR);
	   $query->bindValue(':empgoalsDesc', $filters['emp_goals_descr'], PDO::PARAM_STR);
	   $query->bindValue(':empleadershipDesc', $filters['emp_lead_comp_descr'], PDO::PARAM_STR);
	   $query->bindValue(':empperformanceDesc', $filters['emp_perf_stand_descr'], PDO::PARAM_STR);
	   $query->bindValue(':empoverallDesc', $filters['emp_over_perf_descr'], PDO::PARAM_STR);
	   $query->bindValue(':myStatistics', $filters['myStatistics'], PDO::PARAM_INT);
	   $query->bindValue(':cycleid', $filters['cycleid'], PDO::PARAM_INT);
	   $query->bindValue(':calibrated', $filters['calibrated'], PDO::PARAM_INT);
	   $result["success"] = $query->execute();
	   $result["errorMessage"] = $query->errorInfo();
	   $query->setFetchMode(PDO::FETCH_ASSOC);
	   $result["evaluations"] = $query->fetchAll();
	   return $result;
  }

  /*****
   *	Get Bell Shaped Chart Data
   *
   */

  public function GetBellShapedChart($filters)
 {
	  $queryString = "
		  DECLARE @sql NVARCHAR(max);
		  DECLARE @ParmDefinition NVARCHAR(max);
		  DECLARE  @evaluator NVARCHAR(5)=:evaluator,@reviewer VARCHAR(5)=:reviewer, @form NVARCHAR(10)=:form, @employee NVARCHAR(100)=:employee, @position NVARCHAR(100)=:position, @region NVARCHAR(4) =:region,
		  @coreDesc NVARCHAR(100)=:coreDesc, @goalsDesc NVARCHAR(100)=:goalsDesc, @leadershipDesc NVARCHAR(100)=:leadershipDesc, @performanceDesc NVARCHAR(100)=:performanceDesc, @overallDesc NVARCHAR(100)=:overallDesc,
		  @empcoreDesc NVARCHAR(100)=:empcoreDesc, @empgoalsDesc NVARCHAR(100)=:empgoalsDesc, @empleadershipDesc NVARCHAR(100)=:empleadershipDesc, @empperformanceDesc NVARCHAR(100)=:empperformanceDesc,
		  @empoverallDesc NVARCHAR(100)=:empoverallDesc, @myStatistics INT =:myStatistics, @cycleID INT=:cycleid, @calibrated INT=:calibrated;
		  SELECT @sql=N'
		  --Declare @cycleid as int;
		  --SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		  SELECT SUM( CASE evalScore.OSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS PerfImproNeededCount,
		  SUM( CASE evalScore.OSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS BuildingCapabilityCount,
		  SUM( CASE evalScore.OSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS AchievingPerformanceCount,
		  SUM( CASE evalScore.OSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS LeadingPerformanceCount,
		  SUM( CASE empScore.OSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS EmpPerfImproNeededCount,
		  SUM( CASE empScore.OSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS EmpBuildingCapabilityCount,
		  SUM( CASE empScore.OSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS EmpAchievingPerformanceCount,
		  SUM( CASE empScore.OSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS EmpLeadingPerformanceCount
		  FROM dbo.Evaluations E
		  INNER JOIN dbo.vw_arco_employee emp ON emp.empno = e.EmployeeID
		  INNER JOIN reportingLine RLM on RLM.empnosource=e.EmployeeID ';
		   SELECT @sql=@sql+ CASE WHEN @myStatistics=0 OR @myStatistics=1 THEN
		   'AND RLM.State=4' WHEN @myStatistics=2 THEN ' AND RLM.State=3' END 
		  
		  SELECT @sql=@sql+ '
		  OUTER APPLY(
		  SELECT * FROM dbo.EvaluationScores
		  WHERE EvaluationID=E.EvaluationID AND State=2
		  )empScore
		  OUTER APPLY(
			  SELECT * FROM dbo.EvaluationScores
			  WHERE EvaluationID=E.EvaluationID AND State=CASE WHEN @calibrated=0 THEN 4 WHEN @calibrated=1 THEN 5 END
			  )evalScore
		  WHERE E.CycleID=@cycleid AND E.EmployeeID IN
		  (SELECT RL.empnosource FROM dbo.ReportingLine RL WHERE RL.empnotarget '
		  SELECT @sql=@sql+ CASE WHEN @myStatistics=0 THEN 'in (SELECT emp.empno FROM dbo.ReportingLine RL
				   INNER JOIN dbo.vw_arco_employee emp ON emp.empno = RL.empnosource
				   OUTER APPLY(
				   SELECT COUNT(RL2.empnosource) AS count FROM dbo.ReportingLine RL2 WHERE RL2.empnotarget= RL.empnosource AND RL2.state=4
				   AND ISNULL(RL2.excludeFromCycles,0)<>@cycleid
				   )Evals
				   WHERE RL.empnotarget=@reviewer AND RL.state=4 AND ISNULL(RL.excludeFromCycles,0)<>@cycleid AND Evals.count>0)'
			WHEN @myStatistics=1 THEN '=@evaluator AND RL.State=4'
			WHEN @myStatistics=2 THEN '=@evaluator AND RL.State=3 'END;

		  SELECT @sql=@sql+' AND ISNULL(RL.excludeFromCycles,0)<>@cycleid) ';
		--calibrated
		IF @calibrated=0
		BEGIN 
		SELECT @sql=@sql+' AND E.State IN (5,6)';
		END
		IF @calibrated=1
		BEGIN 
		SELECT @sql=@sql+' AND E.State=6';
		END
			SET @ParmDefinition = N'@form NVARCHAR(10), @evaluator NVARCHAR(5),@employee NVARCHAR(100), @position NVARCHAR(100), @region NVARCHAR(4),
			@coreDesc NVARCHAR(100), @goalsDesc NVARCHAR(100), @leadershipDesc NVARCHAR(100), @performanceDesc NVARCHAR(100), @overallDesc NVARCHAR(100),
			@empcoreDesc NVARCHAR(100), @empgoalsDesc NVARCHAR(100), @empleadershipDesc NVARCHAR(100), @empperformanceDesc NVARCHAR(100), @empoverallDesc NVARCHAR(100),
			@reviewer NVARCHAR(5), @myStatistics INT, @cycleID INT, @calibrated INT'

			--main filters
			IF @evaluator IS NOT NULL AND @evaluator <> ''
			BEGIN
					 SELECT @sql = @sql + ' AND RLM.empnotarget=@evaluator'
			END

			IF @form IS NOT NULL AND @form <> '' AND @form <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND E.empGrade>= CASE WHEN @form=''1_3'' THEN 0 WHEN @form=''4_9'' THEN 4 WHEN @form=''10'' THEN 10 END AND  E.empGrade<= CASE WHEN @form=''1_3'' THEN 3 WHEN @form=''4_9'' THEN 9 WHEN @form=''10'' THEN 20 END'
			END
			IF @employee IS NOT NULL AND @employee<>''
			BEGIN
				 SELECT @sql = @sql + ' AND (emp.family_name like ''%'+@employee+'%'' OR emp.first_name like ''%'+@employee+'%'' OR emp.empno=@employee)'
			END
			IF @position IS NOT NULL AND  @position<>''
			BEGIN
				 SELECT @sql = @sql + ' AND emp.job_desc like ''%'+@position+'%'''
			END
			IF @region IS NOT NULL AND @region<>''
			BEGIN
				 SELECT @sql = @sql + ' AND emp.region like ''%'+@region+'%'''
			END
			--evaluator scores
			IF @coreDesc IS NOT NULL AND @coreDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND evalScore.CSDescription=@coreDesc'
			END
			IF @goalsDesc IS NOT NULL AND @goalsDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND evalScore.GSDescription=@goalsDesc'
			END
			IF @leadershipDesc IS NOT NULL AND @leadershipDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND evalScore.LSDescription=@leadershipDesc'
			END
			IF @performanceDesc IS NOT NULL AND @performanceDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND evalScore.PSDescription=@performanceDesc'
			END
			IF @overallDesc IS NOT NULL AND @overallDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND evalScore.OSDescription=@overallDesc'
			END

			--employye scores
			IF @empcoreDesc IS NOT NULL AND @empcoreDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND empScore.CSDescription=@empcoreDesc'
			END
			IF @empgoalsDesc IS NOT NULL AND @empgoalsDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND empScore.GSDescription=@empgoalsDesc'
			END
			IF @empleadershipDesc IS NOT NULL AND @empleadershipDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND empScore.LSDescription=@empleadershipDesc'
			END
			IF @empperformanceDesc IS NOT NULL AND @empperformanceDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND empScore.PSDescription=@empperformanceDesc'
			END
			IF @empoverallDesc IS NOT NULL AND @empoverallDesc <> 'Select All'
			BEGIN
				 SELECT @sql = @sql + ' AND empScore.OSDescription=@empoverallDesc'
			END

			EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @evaluator=@evaluator, @form=@form, @position=@position, @region=@region, @employee=@employee, @coreDesc=@coreDesc,
			@goalsDesc=@goalsDesc,@leadershipDesc=@leadershipDesc,@performanceDesc=@performanceDesc,@overallDesc=@overallDesc, @empcoreDesc=@coreDesc,
			@empgoalsDesc=@empgoalsDesc,@empleadershipDesc=@empleadershipDesc,@empperformanceDesc=@empperformanceDesc,@empoverallDesc=@empoverallDesc, 
			@reviewer=@reviewer, @myStatistics=@myStatistics, @cycleID=@cycleID, @calibrated=@calibrated

	  ";
	  $query = $this->connection->prepare($queryString);
	  $query->bindValue(':evaluator', $filters['evaluator'], PDO::PARAM_STR);
	  $query->bindValue(':reviewer', $filters['loggedin_user'], PDO::PARAM_STR);
	  $query->bindValue(':employee', $filters['employee'], PDO::PARAM_STR);
	  $query->bindValue(':form', $filters['grade'], PDO::PARAM_STR);
	  $query->bindValue(':position', $filters['position'], PDO::PARAM_STR);
	  $query->bindValue(':region', $filters['region'], PDO::PARAM_STR);
	  $query->bindValue(':coreDesc', $filters['core_comp_descr'], PDO::PARAM_STR);
	  $query->bindValue(':goalsDesc', $filters['goals_descr'], PDO::PARAM_STR);
	  $query->bindValue(':leadershipDesc', $filters['lead_comp_descr'], PDO::PARAM_STR);
	  $query->bindValue(':performanceDesc', $filters['perf_stand_descr'], PDO::PARAM_STR);
	  $query->bindValue(':overallDesc', $filters['over_perf_descr'], PDO::PARAM_STR);
	  $query->bindValue(':empcoreDesc', $filters['emp_core_comp_descr'], PDO::PARAM_STR);
	  $query->bindValue(':empgoalsDesc', $filters['emp_goals_descr'], PDO::PARAM_STR);
	  $query->bindValue(':empleadershipDesc', $filters['emp_lead_comp_descr'], PDO::PARAM_STR);
	  $query->bindValue(':empperformanceDesc', $filters['emp_perf_stand_descr'], PDO::PARAM_STR);
	  $query->bindValue(':empoverallDesc', $filters['emp_over_perf_descr'], PDO::PARAM_STR);
	  $query->bindValue(':myStatistics', $filters['myStatistics'], PDO::PARAM_INT);
	  $query->bindValue(':cycleid', $filters['cycleid'], PDO::PARAM_INT);
	  $query->bindValue(':calibrated', $filters['calibrated'], PDO::PARAM_INT);
	  $result["success"] = $query->execute();
	  $result["errorMessage"] = $query->errorInfo();
	  $query->setFetchMode(PDO::FETCH_ASSOC);
	  $result["evaluations"] = $query->fetchAll();
	  return $result;
 }

 /*****
  *	Get Comparison
  *
  */

 public function GetEvaluatorsAvgTendency($filters)
 {
 	$queryString = "
 		DECLARE @sql NVARCHAR(max);
 		DECLARE @ParmDefinition NVARCHAR(max);
 		DECLARE  @evaluator NVARCHAR(5)=:evaluator,@reviewer VARCHAR(5)=:reviewer, @form NVARCHAR(10)=:form, @employee NVARCHAR(100)=:employee, @position NVARCHAR(100)=:position, @region NVARCHAR(4) =:region,
 		@coreDesc NVARCHAR(100)=:coreDesc, @goalsDesc NVARCHAR(100)=:goalsDesc, @leadershipDesc NVARCHAR(100)=:leadershipDesc, @performanceDesc NVARCHAR(100)=:performanceDesc, @overallDesc NVARCHAR(100)=:overallDesc,
 		@empcoreDesc NVARCHAR(100)=:empcoreDesc, @empgoalsDesc NVARCHAR(100)=:empgoalsDesc, @empleadershipDesc NVARCHAR(100)=:empleadershipDesc, @empperformanceDesc NVARCHAR(100)=:empperformanceDesc,
		 @empoverallDesc NVARCHAR(100)=:empoverallDesc,  @hasGoals NVARCHAR(3)=:hasGoals, @isManager NVARCHAR(3)=:isManager, @myStatistics INT =:myStatistics, 
		 @actualAvgFlag INT =:actualAvgFlag, @cycleID INT=:cycleid, @typeOfStats INT=:typeOfStats, @calibrated INT =:calibrated;
		 SELECT @sql=N'
 		--Declare @cycleid as int;
 		--SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
 		SELECT RLM.empnotarget as ''Evaluator'', LTrim(RTrim(eval.family_name))+'' ''+LTrim(RTrim(eval.first_name)) AS Name, COUNT(emp.empno) as ''EvaluatedCount'',
		CAST(AVG(evalScore.PScore) as decimal(5,2)) as AVGPScore,
		[dbo].[ConvertScoreToTextPCStandards](AVG(evalScore.PScore)) as AVGPDesc,
		MIN(evalScore.PScore) as MINPScore, MAX(evalScore.PScore) as MAXPScore,
		SUM( CASE evalScore.PSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS PPerfImproNeededCount,
		  SUM( CASE evalScore.PSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS PBuildingCapabilityCount,
		  SUM( CASE evalScore.PSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS PAchievingPerformanceCount,
		  SUM( CASE evalScore.PSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS PLeadingPerformanceCount,
		  CAST(AVG(evalScore.PScore) as decimal(5,2)) - CAST(AVG(empScore.PScore) as decimal(5,2)) as PGap,

		CAST(AVG(NULLIF(evalScore.GScore,0)) as decimal(5,2)) as AVGGScore,
		[dbo].[ConvertScoreToTextGoals](AVG(evalScore.GScore)) as AVGGDesc,
		MIN(NULLIF(evalScore.GScore,0)) as MINGScore, MAX(NULLIF(evalScore.GScore,0)) as MAXGScore,
		SUM( CASE evalScore.GSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS GPerfImproNeededCount,
		  SUM( CASE evalScore.GSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS GBuildingCapabilityCount,
		  SUM( CASE evalScore.GSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS GAchievingPerformanceCount,
		  SUM( CASE evalScore.GSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS GLeadingPerformanceCount,
		  CAST(AVG(evalScore.GScore) as decimal(5,2)) - CAST(AVG(empScore.GScore) as decimal(5,2)) as GGap,

		CAST(AVG(evalScore.CScore) as decimal(5,2)) as AVGCScore,
		[dbo].[ConvertScoreToTextPCStandards](AVG(evalScore.CScore)) as AVGCDesc,
		MIN(evalScore.CScore) as MINCScore, MAX(evalScore.CScore) as MAXCScore,
		SUM( CASE evalScore.CSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS CPerfImproNeededCount,
		  SUM( CASE evalScore.CSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS CBuildingCapabilityCount,
		  SUM( CASE evalScore.CSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS CAchievingPerformanceCount,
		  SUM( CASE evalScore.CSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS CLeadingPerformanceCount,
		  CAST(AVG(evalScore.CScore) as decimal(5,2)) - CAST(AVG(empScore.CScore) as decimal(5,2)) as CGap,

		CAST(AVG(NULLIF(evalScore.LScore,0)) as decimal(5,2)) as AVGLScore,
		[dbo].[ConvertScoreToTextPCStandards](AVG(evalScore.LScore)) as AVGLDesc,
		MIN(NULLIF(evalScore.LScore,0)) as MINLScore, MAX(NULLIF(evalScore.LScore,0)) as MAXLScore,
		SUM( CASE evalScore.LSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS LPerfImproNeededCount,
		  SUM( CASE evalScore.LSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS LBuildingCapabilityCount,
		  SUM( CASE evalScore.LSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS LAchievingPerformanceCount,
		  SUM( CASE evalScore.LSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS LLeadingPerformanceCount,
		  CAST(AVG(evalScore.LScore) as decimal(5,2)) - CAST(AVG(empScore.LScore) as decimal(5,2)) as LGap,

		CAST(AVG(evalScore.OverallScore) as decimal(5,2)) as AVGOScore,
		 CASE
			 WHEN AVG(evalScore.OverallScore) <ScoreScale.Scale1 THEN ScoreScale.ScaleDesc1
			 WHEN AVG(evalScore.OverallScore) <ScoreScale.Scale2 THEN ScoreScale.ScaleDesc2
			 WHEN AVG(evalScore.OverallScore) <ScoreScale.Scale3 THEN ScoreScale.ScaleDesc3
			 ELSE ScoreScale.ScaleDesc4 END  as AVGODesc,
		MIN(evalScore.OverallScore) as MINOScore, MAX(evalScore.OverallScore) as MAXOScore,
		SUM( CASE evalScore.OSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS OPerfImproNeededCount,
		  SUM( CASE evalScore.OSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS OBuildingCapabilityCount,
		  SUM( CASE evalScore.OSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS OAchievingPerformanceCount,
		  SUM( CASE evalScore.OSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS OLeadingPerformanceCount,
		  CAST(AVG(evalScore.OverallScore) as decimal(5,2)) - CAST(AVG(empScore.OverallScore) as decimal(5,2)) as OGap

 		FROM dbo.Evaluations E
 		INNER JOIN dbo.vw_arco_employee emp ON emp.empno = e.EmployeeID
 		INNER JOIN reportingLine RLM on RLM.empnosource=e.EmployeeID AND RLM.State=4
		INNER JOIN dbo.vw_arco_employee eval ON eval.empno = RLM.empnotarget
 		OUTER APPLY(
 		SELECT * FROM dbo.EvaluationScores
 		WHERE EvaluationID=E.EvaluationID AND State=2
 		)empScore
 		OUTER APPLY(
 		SELECT * FROM dbo.EvaluationScores
 		WHERE EvaluationID=E.EvaluationID AND State=CASE WHEN @calibrated=0 THEN 4 WHEN @calibrated=1 THEN 5 END
 		)evalScore
		OUTER APPLY(
		SELECT SSC.* FROM dbo.[ScoreScales] SSC
		INNER JOIN dbo.ScoreGroups SG ON SG.scoreScaleID=SSC.GroupID
		WHERE SG.gradeLessThan4=CASE WHEN @form=''1_3'' THEN 1 ELSE 0 END
		AND SG.withGoals=CASE WHEN @hasGoals=''Yes'' THEN 1 ELSE 0 END
		AND SG.forManager=CASE WHEN @isManager=''Yes'' THEN 1 ELSE 0 END
		) ScoreScale
 		WHERE E.CycleID=@cycleid AND E.EmployeeID IN
 		(SELECT RL.empnosource FROM dbo.ReportingLine RL WHERE RL.empnotarget '
		SELECT @sql=@sql+ CASE WHEN @myStatistics=0 THEN 'in (SELECT emp.empno FROM dbo.ReportingLine RL
				 INNER JOIN dbo.vw_arco_employee emp ON emp.empno = RL.empnosource
				 OUTER APPLY(
				 SELECT COUNT(RL2.empnosource) AS count FROM dbo.ReportingLine RL2 WHERE RL2.empnotarget= RL.empnosource AND RL2.state=4
				 AND ISNULL(RL2.excludeFromCycles,0)<>@cycleid
				 )Evals
				 WHERE RL.empnotarget=@reviewer AND RL.state=4 AND ISNULL(RL.excludeFromCycles,0)<>@cycleid AND Evals.count>0)'
				 WHEN @myStatistics=1 THEN '=@evaluator' END;

		SELECT @sql=@sql+' AND RL.State=4 AND ISNULL(RL.excludeFromCycles,0)<>@cycleid)';
		--calibrated
		IF @calibrated=0
		BEGIN 
		SELECT @sql=@sql+' AND E.State IN (5,6)';
		END
		IF @calibrated=1
		BEGIN 
		SELECT @sql=@sql+' AND E.State=6';
		END
		
 		SET @ParmDefinition = N'@form NVARCHAR(10), @evaluator NVARCHAR(5),@employee NVARCHAR(100), @position NVARCHAR(100), @region NVARCHAR(4),
 		@coreDesc NVARCHAR(100), @goalsDesc NVARCHAR(100), @leadershipDesc NVARCHAR(100), @performanceDesc NVARCHAR(100), @overallDesc NVARCHAR(100),
 		@empcoreDesc NVARCHAR(100), @empgoalsDesc NVARCHAR(100), @empleadershipDesc NVARCHAR(100), @empperformanceDesc NVARCHAR(100), @empoverallDesc NVARCHAR(100), @hasGoals NVARCHAR(3),
		@isManager NVARCHAR(3), @reviewer NVARCHAR(5), @myStatistics INT, @actualAvgFlag INT, @cycleID INT, @typeOfStats INT, @calibrated INT'

 		--main filters
 		IF @evaluator IS NOT NULL AND @evaluator <> ''
 		BEGIN
 				 SELECT @sql = @sql + ' AND RLM.empnotarget=@evaluator'
 		END

 		IF @form IS NOT NULL AND @form <> '' AND @form <> 'Select All'
 		BEGIN
 			 SELECT @sql = @sql + ' AND E.empGrade>= CASE WHEN @form=''1_3'' THEN 0 WHEN @form=''4_9'' THEN 4 WHEN @form=''10'' THEN 10 END AND  E.empGrade<= CASE WHEN @form=''1_3'' THEN 3 WHEN @form=''4_9'' THEN 9 WHEN @form=''10'' THEN 20 END'
 		END
 		IF @employee IS NOT NULL AND @employee<>''
 		BEGIN
 			 SELECT @sql = @sql + ' AND (emp.family_name like ''%'+@employee+'%'' OR emp.first_name like ''%'+@employee+'%'' OR emp.empno=@employee)'
 		END
 		IF @position IS NOT NULL AND  @position<>''
 		BEGIN
 			 SELECT @sql = @sql + ' AND emp.job_desc like ''%'+@position+'%'''
 		END
 		IF @region IS NOT NULL AND @region<>''
 		BEGIN
 			 SELECT @sql = @sql + ' AND emp.region like ''%'+@region+'%'''
 		END
 		--evaluator scores
 		IF @coreDesc IS NOT NULL AND @coreDesc <> 'Select All'
 		BEGIN
 			 SELECT @sql = @sql + ' AND evalScore.CSDescription=@coreDesc'
 		END
 		IF @goalsDesc IS NOT NULL AND @goalsDesc <> 'Select All'
 		BEGIN
 			 SELECT @sql = @sql + ' AND evalScore.GSDescription=@goalsDesc'
 		END
 		IF @leadershipDesc IS NOT NULL AND @leadershipDesc <> 'Select All'
 		BEGIN
 			 SELECT @sql = @sql + ' AND evalScore.LSDescription=@leadershipDesc'
 		END
 		IF @performanceDesc IS NOT NULL AND @performanceDesc <> 'Select All'
 		BEGIN
 			 SELECT @sql = @sql + ' AND evalScore.PSDescription=@performanceDesc'
 		END
 		IF @overallDesc IS NOT NULL AND @overallDesc <> 'Select All'
 		BEGIN
 			 SELECT @sql = @sql + ' AND evalScore.OSDescription=@overallDesc'
 		END

 		--employye scores
 		IF @empcoreDesc IS NOT NULL AND @empcoreDesc <> 'Select All'
 		BEGIN
 			 SELECT @sql = @sql + ' AND empScore.CSDescription=@empcoreDesc'
 		END
 		IF @empgoalsDesc IS NOT NULL AND @empgoalsDesc <> 'Select All'
 		BEGIN
 			 SELECT @sql = @sql + ' AND empScore.GSDescription=@empgoalsDesc'
 		END
 		IF @empleadershipDesc IS NOT NULL AND @empleadershipDesc <> 'Select All'
 		BEGIN
 			 SELECT @sql = @sql + ' AND empScore.LSDescription=@empleadershipDesc'
 		END
 		IF @empperformanceDesc IS NOT NULL AND @empperformanceDesc <> 'Select All'
 		BEGIN
 			 SELECT @sql = @sql + ' AND empScore.PSDescription=@empperformanceDesc'
 		END
 		IF @empoverallDesc IS NOT NULL AND @empoverallDesc <> 'Select All'
 		BEGIN
 			 SELECT @sql = @sql + ' AND empScore.OSDescription=@empoverallDesc'
 		END


		 IF @actualAvgFlag  IS NOT NULL AND @actualAvgFlag= 0
			BEGIN	 
				IF @hasGoals IS NOT NULL AND @hasGoals <> ''
 				BEGIN
 					 SELECT @sql = CASE WHEN @hasGoals='Yes' THEN
					 @sql + ' AND (evalScore.GScore>0 or empScore.GScore>0)'
					ELSE
					 @sql + ' AND (evalScore.GScore=0 AND empScore.GScore=0)'
 					END
 				END

				IF @isManager IS NOT NULL AND @isManager <> ''
 				BEGIN
					SELECT @sql = CASE WHEN @isManager='Yes' THEN
					 @sql + ' AND E.ManagesTeam=1 '
					ELSE
					 @sql + ' AND E.ManagesTeam=0 '
 					END
 				END
			END

 		SELECT @sql= @sql+' GROUP BY RLM.empnotarget,eval.family_name, eval.first_name, ScoreScale.Scale1, ScoreScale.Scale2, ScoreScale.Scale3, ScoreScale.Scale4, ScoreScale.ScaleDesc1, ScoreScale.ScaleDesc2, ScoreScale.ScaleDesc3, ScoreScale.ScaleDesc4';
		
		 IF @typeOfStats = 2 -- Comparison Report, order by gap.
		 BEGIN
			 SELECT @sql= @sql+' ORDER BY CAST(AVG(evalScore.OverallScore) as decimal(5,2)) - CAST(AVG(empScore.OverallScore) as decimal(5,2)) desc ';
		 END

 		EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @evaluator=@evaluator, @form=@form, @position=@position, @region=@region, @employee=@employee, @coreDesc=@coreDesc,
 		@goalsDesc=@goalsDesc,@leadershipDesc=@leadershipDesc,@performanceDesc=@performanceDesc,@overallDesc=@overallDesc, @empcoreDesc=@coreDesc,
 		@empgoalsDesc=@empgoalsDesc,@empleadershipDesc=@empleadershipDesc,@empperformanceDesc=@empperformanceDesc,@empoverallDesc=@empoverallDesc, @hasGoals=@hasGoals,
		@isManager=@isManager, @reviewer=@reviewer, @myStatistics=@myStatistics, @actualAvgFlag=@actualAvgFlag, @cycleID=@cycleID, @typeOfStats=@typeOfStats,
		@calibrated=@calibrated
 	";
 	$query = $this->connection->prepare($queryString);
 	$query->bindValue(':evaluator', $filters['evaluator'], PDO::PARAM_STR);
 	$query->bindValue(':reviewer', $filters['loggedin_user'], PDO::PARAM_STR);
 	$query->bindValue(':employee', $filters['employee'], PDO::PARAM_STR);
 	$query->bindValue(':form', $filters['grade'], PDO::PARAM_STR);
 	$query->bindValue(':position', $filters['position'], PDO::PARAM_STR);
 	$query->bindValue(':region', $filters['region'], PDO::PARAM_STR);
 	$query->bindValue(':coreDesc', $filters['core_comp_descr'], PDO::PARAM_STR);
 	$query->bindValue(':goalsDesc', $filters['goals_descr'], PDO::PARAM_STR);
 	$query->bindValue(':leadershipDesc', $filters['lead_comp_descr'], PDO::PARAM_STR);
 	$query->bindValue(':performanceDesc', $filters['perf_stand_descr'], PDO::PARAM_STR);
 	$query->bindValue(':overallDesc', $filters['over_perf_descr'], PDO::PARAM_STR);
 	$query->bindValue(':empcoreDesc', $filters['emp_core_comp_descr'], PDO::PARAM_STR);
 	$query->bindValue(':empgoalsDesc', $filters['emp_goals_descr'], PDO::PARAM_STR);
 	$query->bindValue(':empleadershipDesc', $filters['emp_lead_comp_descr'], PDO::PARAM_STR);
 	$query->bindValue(':empperformanceDesc', $filters['emp_perf_stand_descr'], PDO::PARAM_STR);
 	$query->bindValue(':empoverallDesc', $filters['emp_over_perf_descr'], PDO::PARAM_STR);
	$query->bindValue(':hasGoals', $filters['has_goals'], PDO::PARAM_STR);
	$query->bindValue(':isManager', $filters['is_manager'], PDO::PARAM_STR);
	$query->bindValue(':myStatistics', $filters['myStatistics'], PDO::PARAM_INT);
	$query->bindValue(':actualAvgFlag', $filters['res_type'], PDO::PARAM_INT);
	$query->bindValue(':cycleid', $filters['cycleid'], PDO::PARAM_INT);
	$query->bindValue(':typeOfStats', $filters['type_of_statistics'], PDO::PARAM_INT);
	$query->bindValue(':calibrated', $filters['calibrated'], PDO::PARAM_INT);
 	$result["success"] = $query->execute();
 	$result["errorMessage"] = $query->errorInfo();
 	$query->setFetchMode(PDO::FETCH_ASSOC);
 	$result["evaluations"] = $query->fetchAll();
 	return $result;
 }

 public function GetChartsDataAvgTendency($filters)
 {
 	$queryString = "
 		DECLARE @sql NVARCHAR(max)='';
 		DECLARE @ParmDefinition NVARCHAR(max);
 		DECLARE  @evaluator NVARCHAR(5)=:evaluator,@reviewer VARCHAR(5)=:reviewer, @form NVARCHAR(10)=:form, @employee NVARCHAR(100)=:employee, @position NVARCHAR(100)=:position, @region NVARCHAR(4) =:region,
 		@coreDesc NVARCHAR(100)=:coreDesc, @goalsDesc NVARCHAR(100)=:goalsDesc, @leadershipDesc NVARCHAR(100)=:leadershipDesc, @performanceDesc NVARCHAR(100)=:performanceDesc, @overallDesc NVARCHAR(100)=:overallDesc,
 		@empcoreDesc NVARCHAR(100)=:empcoreDesc, @empgoalsDesc NVARCHAR(100)=:empgoalsDesc, @empleadershipDesc NVARCHAR(100)=:empleadershipDesc, @empperformanceDesc NVARCHAR(100)=:empperformanceDesc,
		 @empoverallDesc NVARCHAR(100)=:empoverallDesc,  @hasGoals NVARCHAR(3)=:hasGoals, @isManager NVARCHAR(3)=:isManager, @myStatistics INT =:myStatistics, 
		 @actualAvgFlag INT =:actualAvgFlag, @cycleID INT=:cycleid, @calibrated INT=:calibrated;
		 SELECT @sql=N'
		 DECLARE @ProductAvgs TABLE
		 (
		   Evaluator VARCHAR(5), 
		   Name VARCHAR(150),
		   EvaluatedCount INT,
 
		   AVGPScore FLOAT,
		   AVGPDesc VARCHAR(200),
		   MINPScore FLOAT,
		   MAXPscore FLOAT,
		   PPerfImproNeededCount INT,
		   PBuildingCapabilityCount INT,
		   PAchievingPerformanceCount INT,
		   PleadingPerformanceCount INT,
 
		   AVGGScore FLOAT,
		   AVGGDesc VARCHAR(200),
		   MINGScore FLOAT,
		   MAXGscore FLOAT,
		   GPerfImproNeededCount INT,
		   GBuildingCapabilityCount INT,
		   GAchievingPerformanceCount INT,
		   GleadingPerformanceCount INT,
 
		   AVGCScore FLOAT,
		   AVGCDesc VARCHAR(200),
		   MINCScore FLOAT,
		   MAXCscore FLOAT,
		   CPerfImproNeededCount INT,
		   CBuildingCapabilityCount INT,
		   CAchievingPerformanceCount INT,
		   CleadingPerformanceCount INT,
 
		   AVGLScore FLOAT,
		   AVGLDesc VARCHAR(200),
		   MINLScore FLOAT,
		   MAXLscore FLOAT,
		   LPerfImproNeededCount INT,
		   LBuildingCapabilityCount INT,
		   LAchievingPerformanceCount INT,
		   LleadingPerformanceCount INT,
 
		   AVGOScore FLOAT,
		   AVGODesc VARCHAR(200),
		   MINOScore FLOAT,
		   MAXOscore FLOAT,
		   OPerfImproNeededCount INT,
		   OBuildingCapabilityCount INT,
		   OAchievingPerformanceCount INT,
		   OleadingPerformanceCount INT
		 )
		 -- Declare @cycleid as int;
		  --SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
		  INSERT INTO @ProductAvgs
		 (
			 Evaluator,
			 Name,
			 EvaluatedCount,
			 AVGPScore,
			 AVGPDesc,
			 MINPScore,
			 MAXPscore,
			 PPerfImproNeededCount,
			 PBuildingCapabilityCount,
			 PAchievingPerformanceCount,
			 PleadingPerformanceCount,
			 AVGGScore,
			 AVGGDesc,
			 MINGScore,
			 MAXGscore,
			 GPerfImproNeededCount,
			 GBuildingCapabilityCount,
			 GAchievingPerformanceCount,
			 GleadingPerformanceCount,
			 AVGCScore,
			 AVGCDesc,
			 MINCScore,
			 MAXCscore,
			 CPerfImproNeededCount,
			 CBuildingCapabilityCount,
			 CAchievingPerformanceCount,
			 CleadingPerformanceCount,
			 AVGLScore,
			 AVGLDesc,
			 MINLScore,
			 MAXLscore,
			 LPerfImproNeededCount,
			 LBuildingCapabilityCount,
			 LAchievingPerformanceCount,
			 LleadingPerformanceCount,
			 AVGOScore,
			 AVGODesc,
			 MINOScore,
			 MAXOscore,
			 OPerfImproNeededCount,
			 OBuildingCapabilityCount,
			 OAchievingPerformanceCount,
			 OleadingPerformanceCount
		 )
		 
		   SELECT 
		  RLM.empnotarget as ''Evaluator'', LTrim(RTrim(eval.family_name))+'' ''+LTrim(RTrim(eval.first_name)) AS Name, COUNT(emp.empno) as ''EvaluatedCount'',
		  CAST(AVG(evalScore.PScore) as decimal(5,2)) as AVGPScore,
		 [dbo].[ConvertScoreToTextPCStandards](AVG(evalScore.PScore)) as AVGPDesc,
		 MIN(evalScore.PScore) as MINPScore, MAX(evalScore.PScore) as MAXPScore,
		 SUM( CASE evalScore.PSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS PPerfImproNeededCount,
		   SUM( CASE evalScore.PSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS PBuildingCapabilityCount,
		   SUM( CASE evalScore.PSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS PAchievingPerformanceCount,
		   SUM( CASE evalScore.PSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS PLeadingPerformanceCount,
		  -- CAST(AVG(evalScore.PScore) as decimal(5,2)) - CAST(AVG(empScore.PScore) as decimal(5,2)) as PGap,
 
		 CAST(AVG(NULLIF(evalScore.GScore,0)) as decimal(5,2)) as AVGGScore,
		 [dbo].[ConvertScoreToTextGoals](AVG(evalScore.GScore)) as AVGGDesc,
		 MIN(NULLIF(evalScore.GScore,0)) as MINGScore, MAX(NULLIF(evalScore.GScore,0)) as MAXGScore,
		 SUM( CASE evalScore.GSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS GPerfImproNeededCount,
		   SUM( CASE evalScore.GSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS GBuildingCapabilityCount,
		   SUM( CASE evalScore.GSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS GAchievingPerformanceCount,
		   SUM( CASE evalScore.GSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS GLeadingPerformanceCount,
		  -- CAST(AVG(evalScore.GScore) as decimal(5,2)) - CAST(AVG(empScore.GScore) as decimal(5,2)) as GGap,
 
		 CAST(AVG(evalScore.CScore) as decimal(5,2)) as AVGCScore,
		 [dbo].[ConvertScoreToTextPCStandards](AVG(evalScore.CScore)) as AVGCDesc,
		 MIN(evalScore.CScore) as MINCScore, MAX(evalScore.CScore) as MAXCScore,
		 SUM( CASE evalScore.CSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS CPerfImproNeededCount,
		   SUM( CASE evalScore.CSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS CBuildingCapabilityCount,
		   SUM( CASE evalScore.CSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS CAchievingPerformanceCount,
		   SUM( CASE evalScore.CSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS CLeadingPerformanceCount,
		   --CAST(AVG(evalScore.CScore) as decimal(5,2)) - CAST(AVG(empScore.CScore) as decimal(5,2)) as CGap,
 
		 CAST(AVG(NULLIF(evalScore.LScore,0)) as decimal(5,2)) as AVGLScore,
		 [dbo].[ConvertScoreToTextPCStandards](AVG(evalScore.LScore)) as AVGLDesc,
		 MIN(NULLIF(evalScore.LScore,0)) as MINLScore, MAX(NULLIF(evalScore.LScore,0)) as MAXLScore,
		 SUM( CASE evalScore.LSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS LPerfImproNeededCount,
		   SUM( CASE evalScore.LSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS LBuildingCapabilityCount,
		   SUM( CASE evalScore.LSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS LAchievingPerformanceCount,
		   SUM( CASE evalScore.LSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS LLeadingPerformanceCount,
		  -- CAST(AVG(evalScore.LScore) as decimal(5,2)) - CAST(AVG(empScore.LScore) as decimal(5,2)) as LGap,
 
		 CAST(AVG(evalScore.OverallScore) as decimal(5,2)) as AVGOScore,
		  CASE
			  WHEN AVG(evalScore.OverallScore) <ScoreScale.Scale1 THEN ScoreScale.ScaleDesc1
			  WHEN AVG(evalScore.OverallScore) <ScoreScale.Scale2 THEN ScoreScale.ScaleDesc2
			  WHEN AVG(evalScore.OverallScore) <ScoreScale.Scale3 THEN ScoreScale.ScaleDesc3
			  ELSE ScoreScale.ScaleDesc4 END  as AVGODesc,
		 MIN(evalScore.OverallScore) as MINOScore, MAX(evalScore.OverallScore) as MAXOScore,
		 SUM( CASE evalScore.OSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS OPerfImproNeededCount,
		   SUM( CASE evalScore.OSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END)  AS OBuildingCapabilityCount,
		   SUM( CASE evalScore.OSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END)  AS OAchievingPerformanceCount,
		   SUM( CASE evalScore.OSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS OLeadingPerformanceCount --,
		 --  CAST(AVG(evalScore.OverallScore) as decimal(5,2)) - CAST(AVG(empScore.OverallScore) as decimal(5,2)) as OGap
 
		  FROM dbo.Evaluations E
		  INNER JOIN dbo.vw_arco_employee emp ON emp.empno = e.EmployeeID
		  INNER JOIN reportingLine RLM on RLM.empnosource=e.EmployeeID AND RLM.State=4
		 INNER JOIN dbo.vw_arco_employee eval ON eval.empno = RLM.empnotarget
		  OUTER APPLY(
		  SELECT * FROM dbo.EvaluationScores
		  WHERE EvaluationID=E.EvaluationID AND State=2
		  )empScore
		  OUTER APPLY(
		  SELECT * FROM dbo.EvaluationScores
		  WHERE EvaluationID=E.EvaluationID AND State=CASE WHEN @calibrated=0 THEN 4 WHEN @calibrated=1 THEN 5 END
		  )evalScore
		 OUTER APPLY(
		 SELECT SSC.* FROM dbo.[ScoreScales] SSC
		 INNER JOIN dbo.ScoreGroups SG ON SG.scoreScaleID=SSC.GroupID
		 WHERE SG.gradeLessThan4=CASE WHEN @form=''1_3'' THEN 1 ELSE 0 END
		 AND SG.withGoals=CASE WHEN @hasGoals=''Yes'' THEN 1 ELSE 0 END
		 AND SG.forManager=CASE WHEN @isManager=''Yes'' THEN 1 ELSE 0 END
		 ) ScoreScale
		  WHERE E.CycleID=@cycleid AND E.EmployeeID IN
		  (SELECT RL.empnosource FROM dbo.ReportingLine RL WHERE RL.empnotarget '
		 SELECT @sql=@sql+ CASE WHEN @myStatistics=0 THEN 'in (SELECT emp.empno FROM dbo.ReportingLine RL
				  INNER JOIN dbo.vw_arco_employee emp ON emp.empno = RL.empnosource
				  OUTER APPLY(
				  SELECT COUNT(RL2.empnosource) AS count FROM dbo.ReportingLine RL2 WHERE RL2.empnotarget= RL.empnosource AND RL2.state=4
				  AND ISNULL(RL2.excludeFromCycles,0)<>@cycleid
				  )Evals
				  WHERE RL.empnotarget=@reviewer AND RL.state=4 AND ISNULL(RL.excludeFromCycles,0)<>@cycleid AND Evals.count>0)'
				  WHEN @myStatistics=1 THEN '=@evaluator' END;
 
		 SELECT @sql=@sql+' AND RL.State=4 AND ISNULL(RL.excludeFromCycles,0)<>@cycleid)';
		 --calibrated
		 IF @calibrated=0
		 BEGIN 
		 SELECT @sql=@sql+' AND E.State IN (5,6)';
		 END
		 IF @calibrated=1
		 BEGIN 
		 SELECT @sql=@sql+' AND E.State=6';
		 END
		  SET @ParmDefinition = N'@form NVARCHAR(10), @evaluator NVARCHAR(5),@employee NVARCHAR(100), @position NVARCHAR(100), @region NVARCHAR(4),
		  @coreDesc NVARCHAR(100), @goalsDesc NVARCHAR(100), @leadershipDesc NVARCHAR(100), @performanceDesc NVARCHAR(100), @overallDesc NVARCHAR(100),
		  @empcoreDesc NVARCHAR(100), @empgoalsDesc NVARCHAR(100), @empleadershipDesc NVARCHAR(100), @empperformanceDesc NVARCHAR(100), @empoverallDesc NVARCHAR(100), @hasGoals NVARCHAR(3),
		 @isManager NVARCHAR(3), @reviewer NVARCHAR(5), @myStatistics INT, @actualAvgFlag INT, @cycleID INT, @calibrated INT'
 
		  --main filters
		 
		  IF @evaluator IS NOT NULL AND @evaluator <> ''
		  BEGIN
				   SELECT @sql = @sql + ' AND RLM.empnotarget=@evaluator'
		  END
 
		  IF @form IS NOT NULL AND @form <> '' AND @form <> 'Select All'
		  BEGIN
			   SELECT @sql = @sql + ' AND E.empGrade>= CASE WHEN @form=''1_3'' THEN 0 WHEN @form=''4_9'' THEN 4 WHEN @form=''10'' THEN 10 END AND  E.empGrade<= CASE WHEN @form=''1_3'' THEN 3 WHEN @form=''4_9'' THEN 9 WHEN @form=''10'' THEN 20 END'
		  END
		  IF @employee IS NOT NULL AND @employee<>''
		  BEGIN
			   SELECT @sql = @sql + ' AND (emp.family_name like ''%'+@employee+'%'' OR emp.first_name like ''%'+@employee+'%'' OR emp.empno=@employee)'
		  END
		  IF @position IS NOT NULL AND  @position<>''
		  BEGIN
			   SELECT @sql = @sql + ' AND emp.job_desc like ''%'+@position+'%'''
		  END
		  IF @region IS NOT NULL AND @region<>''
		  BEGIN
			   SELECT @sql = @sql + ' AND emp.region like ''%'+@region+'%'''
		  END
		  --evaluator scores
		  IF @coreDesc IS NOT NULL AND @coreDesc <> 'Select All'
		  BEGIN
			   SELECT @sql = @sql + ' AND evalScore.CSDescription=@coreDesc'
		  END
		  IF @goalsDesc IS NOT NULL AND @goalsDesc <> 'Select All'
		  BEGIN
			   SELECT @sql = @sql + ' AND evalScore.GSDescription=@goalsDesc'
		  END
		  IF @leadershipDesc IS NOT NULL AND @leadershipDesc <> 'Select All'
		  BEGIN
			   SELECT @sql = @sql + ' AND evalScore.LSDescription=@leadershipDesc'
		  END
		  IF @performanceDesc IS NOT NULL AND @performanceDesc <> 'Select All'
		  BEGIN
			   SELECT @sql = @sql + ' AND evalScore.PSDescription=@performanceDesc'
		  END
		  IF @overallDesc IS NOT NULL AND @overallDesc <> 'Select All'
		  BEGIN
			   SELECT @sql = @sql + ' AND evalScore.OSDescription=@overallDesc'
		  END
 
		  --employye scores
		  IF @empcoreDesc IS NOT NULL AND @empcoreDesc <> 'Select All'
		  BEGIN
			   SELECT @sql = @sql + ' AND empScore.CSDescription=@empcoreDesc'
		  END
		  IF @empgoalsDesc IS NOT NULL AND @empgoalsDesc <> 'Select All'
		  BEGIN
			   SELECT @sql = @sql + ' AND empScore.GSDescription=@empgoalsDesc'
		  END
		  IF @empleadershipDesc IS NOT NULL AND @empleadershipDesc <> 'Select All'
		  BEGIN
			   SELECT @sql = @sql + ' AND empScore.LSDescription=@empleadershipDesc'
		  END
		  IF @empperformanceDesc IS NOT NULL AND @empperformanceDesc <> 'Select All'
		  BEGIN
			   SELECT @sql = @sql + ' AND empScore.PSDescription=@empperformanceDesc'
		  END
		  IF @empoverallDesc IS NOT NULL AND @empoverallDesc <> 'Select All'
		  BEGIN
			   SELECT @sql = @sql + ' AND empScore.OSDescription=@empoverallDesc'
		  END
 
 
		  IF @actualAvgFlag  IS NOT NULL AND @actualAvgFlag= 0
			 BEGIN	 
				 IF @hasGoals IS NOT NULL AND @hasGoals <> ''
				  BEGIN
					   SELECT @sql = CASE WHEN @hasGoals='Yes' THEN
					  @sql + ' AND (evalScore.GScore>0 or empScore.GScore>0)'
					 ELSE
					  @sql + ' AND (evalScore.GScore=0 AND empScore.GScore=0)'
					  END
				  END
 
				 IF @isManager IS NOT NULL AND @isManager <> ''
				  BEGIN
					 SELECT @sql = CASE WHEN @isManager='Yes' THEN
					  @sql + ' AND E.ManagesTeam=1 '
					 ELSE
					  @sql + ' AND E.ManagesTeam=0 '
					  END
				  END
			 END
 
		  SELECT @sql= @sql+' GROUP BY RLM.empnotarget,eval.family_name, eval.first_name, ScoreScale.Scale1, ScoreScale.Scale2, ScoreScale.Scale3, ScoreScale.Scale4, ScoreScale.ScaleDesc1, ScoreScale.ScaleDesc2, ScoreScale.ScaleDesc3, ScoreScale.ScaleDesc4';
		 
		 SELECT @sql=@sql+'
		  SELECT 
			 
			 CAST(AVG(AVGPScore) as DECIMAL(5,2)) as AVGPScore,
			 CAST(MIN(AVGPScore) as DECIMAL(5,2)) as MINPScore,
			 CAST(MAX(AVGPScore) as DECIMAL(5,2)) as MAXPScore,
			 --AVGPDesc,
 
			 CAST(AVG(AVGGScore) as DECIMAL(5,2)) as AVGGScore,
			 CAST(MIN(AVGGScore) as DECIMAL(5,2)) as MINGScore,
			 CAST(MAX(AVGGScore) as DECIMAL(5,2)) as MAXGScore,
			 --AVGGDesc,
			 
			 CAST(AVG(AVGCScore) as DECIMAL(5,2)) as AVGCScore,
			 CAST(MIN(AVGCScore) as DECIMAL(5,2)) as MINCScore,
			 CAST(MAX(AVGCScore) as DECIMAL(5,2)) as MAXCScore,
			 --AVGCDesc,
 
			 CAST(AVG(AVGLScore) as DECIMAL(5,2)) as AVGLScore,
			 CAST(MIN(AVGLScore) as DECIMAL(5,2)) as MINLScore,
			 CAST(MAX(AVGLScore) as DECIMAL(5,2)) as MAXLScore,
			 --AVGLDesc,
			
			 CAST(AVG(AVGOScore) as DECIMAL(5,2)) as AVGOScore,
			 CAST(MIN(AVGOScore) as DECIMAL(5,2)) as MINOScore,
			 CAST(MAX(AVGOScore) as DECIMAL(5,2)) as MAXOScore,
			  --AVGODesc,
			 CAST(CAST(SUM(OPerfImproNeededCount) AS FLOAT) / CAST(SUM( EvaluatedCount) AS FLOAT) AS DECIMAL (5,2)) as OPerfImproNeeded,
			 CAST(CAST(SUM(OBuildingCapabilityCount) AS FLOAT) / CAST(SUM( EvaluatedCount) AS FLOAT) AS DECIMAL (5,2)) as OBuildingCapability,
			 CAST(CAST(SUM(OAchievingPerformanceCount) AS FLOAT) / CAST(SUM( EvaluatedCount) AS FLOAT) AS DECIMAL (5,2)) as OAchievingPerformance,
			 CAST(CAST(SUM(OLeadingPerformanceCount) AS FLOAT) / CAST(SUM( EvaluatedCount) AS FLOAT) AS DECIMAL (5,2)) as OLeadingPerformance,
			 SUM(OPerfImproNeededCount) as OPerfImproNeededCount,
			 SUM(OBuildingCapabilityCount) as OBuildingCapabilityCount,
			 SUM(OAchievingPerformanceCount) as OAchievingPerformanceCount,
			 SUM(OLeadingPerformanceCount)  as OLeadingPerformanceCount
			 
			 FROM @ProductAvgs
 
		 '
		
		  EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @evaluator=@evaluator, @form=@form, @position=@position, @region=@region, @employee=@employee, @coreDesc=@coreDesc,
		  @goalsDesc=@goalsDesc,@leadershipDesc=@leadershipDesc,@performanceDesc=@performanceDesc,@overallDesc=@overallDesc, @empcoreDesc=@coreDesc,
		  @empgoalsDesc=@empgoalsDesc,@empleadershipDesc=@empleadershipDesc,@empperformanceDesc=@empperformanceDesc,@empoverallDesc=@empoverallDesc, @hasGoals=@hasGoals,
		 @isManager=@isManager, @reviewer=@reviewer, @myStatistics=@myStatistics, @actualAvgFlag=@actualAvgFlag, @cycleID=@cycleID, @calibrated=@calibrated
 	";
 	$query = $this->connection->prepare($queryString);
 	$query->bindValue(':evaluator', $filters['evaluator'], PDO::PARAM_STR);
 	$query->bindValue(':reviewer', $filters['loggedin_user'], PDO::PARAM_STR);
 	$query->bindValue(':employee', $filters['employee'], PDO::PARAM_STR);
 	$query->bindValue(':form', $filters['grade'], PDO::PARAM_STR);
 	$query->bindValue(':position', $filters['position'], PDO::PARAM_STR);
 	$query->bindValue(':region', $filters['region'], PDO::PARAM_STR);
 	$query->bindValue(':coreDesc', $filters['core_comp_descr'], PDO::PARAM_STR);
 	$query->bindValue(':goalsDesc', $filters['goals_descr'], PDO::PARAM_STR);
 	$query->bindValue(':leadershipDesc', $filters['lead_comp_descr'], PDO::PARAM_STR);
 	$query->bindValue(':performanceDesc', $filters['perf_stand_descr'], PDO::PARAM_STR);
 	$query->bindValue(':overallDesc', $filters['over_perf_descr'], PDO::PARAM_STR);
 	$query->bindValue(':empcoreDesc', $filters['emp_core_comp_descr'], PDO::PARAM_STR);
 	$query->bindValue(':empgoalsDesc', $filters['emp_goals_descr'], PDO::PARAM_STR);
 	$query->bindValue(':empleadershipDesc', $filters['emp_lead_comp_descr'], PDO::PARAM_STR);
 	$query->bindValue(':empperformanceDesc', $filters['emp_perf_stand_descr'], PDO::PARAM_STR);
 	$query->bindValue(':empoverallDesc', $filters['emp_over_perf_descr'], PDO::PARAM_STR);
	$query->bindValue(':hasGoals', $filters['has_goals'], PDO::PARAM_STR);
	$query->bindValue(':isManager', $filters['is_manager'], PDO::PARAM_STR);
	$query->bindValue(':myStatistics', $filters['myStatistics'], PDO::PARAM_INT);
	$query->bindValue(':actualAvgFlag', $filters['res_type'], PDO::PARAM_INT);
	$query->bindValue(':cycleid', $filters['cycleid'], PDO::PARAM_INT);
	$query->bindValue(':calibrated', $filters['calibrated'], PDO::PARAM_INT);
	
 	$result["success"] = $query->execute();
 	$result["errorMessage"] = $query->errorInfo();
	$query->setFetchMode(PDO::FETCH_ASSOC);
	$query->nextRowset(); // send back the select not the insert.
 	$result["evaluations"] = $query->fetchAll();
 	return $result;
 }

 /*****
  *	Get Completed Pies
  *
  */

 public function GetCompanyStats($filters)
{
	 $queryString = "
	 DECLARE @sql NVARCHAR(max);
	 DECLARE @sqlFilters NVARCHAR(max)='',@sqlFilters2 NVARCHAR(max)='';
	 DECLARE @ParmDefinition NVARCHAR(max);
	 DECLARE  @region NVARCHAR(15)=:region, @projectCode varchar(5)=:projectCode, @cycleID INT=:cycleid, @calibrated INT=:calibrated, @family varchar(10)=:family,
	 @empno varchar(5)=:userid;
	 SET @ParmDefinition = N'@region NVARCHAR(15), @projectCode varchar(5), @cycleID INT, @calibrated INT, @family varchar(10), @empno varchar(5)'
	 
		  IF @region IS NOT NULL AND @region <> '' AND @region = 'na'
		   BEGIN
					SELECT @sqlFilters = @sqlFilters + ' AND (emp.region in (''RAIS'', ''RAIC'', ''RAUS'', ''REGY'') or emp.pay_cs in (''5171'', ''5173'', ''5509'')) '
					 SELECT @sqlFilters2 = @sqlFilters2 + ' AND (emp2.region in (''RAIS'', ''RAIC'', ''RAUS'', ''REGY'') or emp2.pay_cs in (''5171'', ''5173'', ''5509'')) ';
			  END
		 
		  IF @region IS NOT NULL AND @region <> '' AND @region = 'europe'
			  BEGIN
				  SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''RGRE'', ''RGEN'', ''RHOL'',''RZ01'',''RTRN'', ''RKAZ'') ';
				  SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.region in (''RGRE'', ''RGEN'', ''RHOL'',''RZ01'',''RTRN'', ''RKAZ'') ';
			  END
	 
		  IF @region IS NOT NULL AND @region <> '' AND @region = 'gulf'
			  BEGIN
				  SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''ROMN'', ''RQAT'', ''RUAE'', ''RDRD'',''RIRQ'',''RMUR'') and emp.pay_cs not in (''5171'', ''5173'', ''5509'') ';
				  SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.region in (''ROMN'', ''RQAT'', ''RUAE'', ''RDRD'',''RIRQ'',''RMUR'') and emp2.pay_cs not in (''5171'', ''5173'', ''5509'') ';
			  END
	 
		  IF @region IS NOT NULL AND @region <> '' AND @region = 'ksa'
			  BEGIN
				  SELECT @sqlFilters = @sqlFilters + ' AND emp.region in ( ''RSAR'', ''RCYP'')';
				  SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.region in ( ''RSAR'', ''RCYP'')';
			  END
	 
		  IF @projectCode IS NOT NULL AND @projectCode <> '' AND @projectCode <> 'Select All'
			  BEGIN
				  SELECT @sqlFilters = @sqlFilters + ' AND emp.pay_cs=@projectCode ';
			  SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.pay_cs=@projectCode ';
			  END
			
		  IF @family IS NOT NULL AND @family <> '' AND @family <> 'Select All'
			BEGIN
				SELECT @sqlFilters = @sqlFilters + ' AND emp.family_Code=@family ';
			SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.family_code=@family ';
			END

		  IF @family = 'Select All' AND (SELECT COUNT(*) FROM userDepartmentAccess WHERE empno=@empno)>0
			BEGIN
				SELECT @sqlFilters = @sqlFilters + ' AND emp.family_Code in (SELECT family_code FROM userDepartmentAccess WHERE empno=@empno)';
			SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.family_code in (SELECT family_code FROM userDepartmentAccess WHERE empno=@empno)';
			END

			  
		   SELECT @sql=N'
		  Declare @currentCycleDesc varchar(50), @nextcycleid int, @nextCycleDesc varchar(50);
		  SELECT @nextcycleid=EC.nextCycleID, @currentCycleDesc=EC.CycleDescription,   @nextCycleDesc=ECN.CycleDescription
		  FROM EvaluationsCycle EC 
		  LEFT JOIN EvaluationsCycle ECN on EC.nextCycleID=ECN.ID 
		  WHERE EC.ID = @cycleID;
	 
		  SELECT
		  @currentCycleDesc as currentPeriodDescription,
		  COUNT(*) AS totalAssignedCurrentPeriod, finishedCurrentPeriod.cnt AS completedCurrentPeriod,
		  CAST((CAST(finishedCurrentPeriod.cnt AS DECIMAL(7,2)) / CAST(COUNT(*) AS DECIMAL(7,2))) AS DECIMAL(7,2)) AS precentageCurrentPeriod ,
		  isnull(overallCurrentPeriod.OPerfImproNeededCount, 0) as OPerfImproNeededCount, isnull(overallCurrentPeriod.OBuildingCapabilityCount,0) as OBuildingCapabilityCount, 
		  isnull(overallCurrentPeriod.OAchievingPerformanceCount,0) as OAchievingPerformanceCount, isnull(overallCurrentPeriod.OLeadingPerformanceCount, 0) as OLeadingPerformanceCount,
		  @nextCycleDesc as nextPeriodDescription,
		  countNextPeriod.cnt AS totalAssignedNextPeriod, finishedNextPeriod.cnt AS completedNextPeriod , CAST((CAST(finishedNextPeriod.cnt AS DECIMAL(7,2)) / CAST(countNextPeriod.cnt AS DECIMAL(7,2))) AS DECIMAL(7,2)) AS precentageNextPeriod
		  FROM dbo.ReportingLine RL
		  INNER JOIN dbo.vw_arco_employee emp ON emp.empno=rl.empnosource AND rl.state=4
		  OUTER APPLY(
		  SELECT COUNT(*) AS cnt 
		  FROM dbo.Evaluations E
		  INNER JOIN dbo.ReportingLine RL2 ON rl2.empnosource=E.EmployeeID AND RL2.state=4
		  INNER JOIN dbo.vw_arco_employee emp2 ON emp2.empno=rl2.empnosource AND rl2.state=4 
		  WHERE E.CycleID=@cycleID AND RL2.excludeFromCycles<>@cycleID';
		  IF @calibrated=0
		  BEGIN 
		  SELECT @sql=@sql+' AND E.State IN (5,6)';
		  END
		  IF @calibrated=1
		  BEGIN 
		  SELECT @sql=@sql+' AND E.State=6';
		  END

		  SELECT @sql=@sql+@sqlFilters2+'
		  ) finishedCurrentPeriod
		  OUTER APPLY(
		  SELECT SUM( CASE OSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS OPerfImproNeededCount,
				SUM( CASE OSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS OBuildingCapabilityCount,
				SUM( CASE OSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS OAchievingPerformanceCount,
				SUM( CASE OSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS OLeadingPerformanceCount
				FROM EvaluationScores ES
		  INNER JOIN dbo.Evaluations E ON E.EvaluationID =ES.EvaluationID
		  INNER JOIN dbo.vw_arco_employee emp2 ON emp2.empno=e.employeeid 
		  WHERE E.CycleID=@cycleID';
		  IF @calibrated=0
		  BEGIN 
		  SELECT @sql=@sql+' AND E.State IN (5,6) AND ES.State=4';
		  END
		  IF @calibrated=1
		  BEGIN 
		  SELECT @sql=@sql+' AND E.State=6 AND ES.State=5';
		  END

		  SELECT @sql=@sql+@sqlFilters2+'
		  ) overallCurrentPeriod
		  OUTER APPLY(
		  SELECT COUNT(*) AS cnt FROM dbo.ReportingLine rl2
		  INNER JOIN dbo.vw_arco_employee emp2 ON emp2.empno=rl2.empnosource AND rl2.state=4
		  WHERE rl2.excludeFromCycles<>@nextcycleid AND rl2.state=4
		  '+@sqlFilters2+'
		  ) countNextPeriod
		  OUTER APPLY(
		  SELECT COUNT(*) AS cnt FROM dbo.Evaluations  E
		  INNER JOIN dbo.ReportingLine RL2 ON rl2.empnosource=E.EmployeeID AND RL2.state=4
		  INNER JOIN dbo.vw_arco_employee emp2 ON emp2.empno=rl2.empnosource AND rl2.state=4 
		  WHERE E.CycleID=@nextcycleid AND E.State>=2 AND RL2.excludeFromCycles<>@nextcycleid 
		  '+@sqlFilters2+'
		  ) finishedNextPeriod
		  WHERE RL.state=4 AND RL.excludeFromCycles<>@cycleID ';
		   
		   --main filters
		  SELECT @sql = @sql + @sqlFilters
		  --group by
		  SELECT @sql= @sql+' GROUP BY finishedCurrentPeriod.cnt, countNextPeriod.cnt, finishedNextPeriod.cnt, overallCurrentPeriod.OPerfImproNeededCount, overallCurrentPeriod.OBuildingCapabilityCount, overallCurrentPeriod.OAchievingPerformanceCount, overallCurrentPeriod.OLeadingPerformanceCount';
		  
		  EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @region=@region, @projectCode=@projectCode, @cycleID=@cycleID, @calibrated=@calibrated, @family=@family, @empno=@empno";
	 $query = $this->connection->prepare($queryString);
	 $query->bindValue(':region', $filters['region'], PDO::PARAM_STR);
	 $query->bindValue(':projectCode', $filters['project'], PDO::PARAM_STR);
	 $query->bindValue(':family', $filters['family'], PDO::PARAM_STR);
	 $query->bindValue(':cycleid', $filters['cycleid'], PDO::PARAM_INT);
	 $query->bindValue(':calibrated', $filters['calibrated'], PDO::PARAM_INT);
	 $query->bindValue(':userid', $filters['loggedin_user'], PDO::PARAM_STR);
	 $result["success"] = $query->execute();
	 $result["errorMessage"] = $query->errorInfo();
	 $query->setFetchMode(PDO::FETCH_ASSOC);
	 $result["completedPies"] = $query->fetchAll();
	 return $result;
}

/*****
 *	Get stats by region
 *
 */
 public function GetCompanyStatsByRegion($filters)
 {
	  $queryString = "
	DECLARE @sql NVARCHAR(max);
	DECLARE @sqlFilters NVARCHAR(max)='';
	DECLARE @ParmDefinition NVARCHAR(max);
	DECLARE  @region NVARCHAR(15)=:region, @projectCode varchar(5)=:projectCode, @cycleID INT=:cycleID, @calibrated INT=:calibrated, @family varchar(10)=:family,
	@empno varchar(5)=:userid;
	SET @ParmDefinition = N'@region NVARCHAR(15), @projectCode varchar(5), @cycleID INT, @calibrated INT, @family varchar(10), @empno varchar(5)'
	
	IF @region IS NOT NULL AND @region <> '' AND @region = 'na'
	BEGIN
		SELECT @sqlFilters = @sqlFilters + ' AND (emp.region in (''RAIS'', ''RAIC'', ''RAUS'', ''REGY'') or emp.pay_cs in (''5171'', ''5173'', ''5509'')) ';
	END

IF @region IS NOT NULL AND @region <> '' AND @region = 'europe'
	BEGIN
		SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''RGRE'', ''RGEN'', ''RHOL'',''RZ01'',''RTRN'', ''RKAZ'') ';
	END

IF @region IS NOT NULL AND @region <> '' AND @region = 'gulf'
	BEGIN
		SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''ROMN'', ''RQAT'', ''RUAE'', ''RDRD'',''RIRQ'',''RMUR'') and emp.pay_cs not in (''5171'', ''5173'', ''5509'') ';
   END

IF @region IS NOT NULL AND @region <> '' AND @region = 'ksa'
	BEGIN
		SELECT @sqlFilters = @sqlFilters + ' AND emp.region in ( ''RSAR'', ''RCYP'')';
	END

IF @projectCode IS NOT NULL AND @projectCode <> '' AND @projectCode <> 'Select All'
	BEGIN
		SELECT @sqlFilters = @sqlFilters + ' AND emp.pay_cs=@projectCode ';
	END

IF @family IS NOT NULL AND @family <> '' AND @family <> 'Select All'
	BEGIN
		SELECT @sqlFilters = @sqlFilters + ' AND emp.family_code=@family ';
	END

IF @family = 'Select All' AND (SELECT COUNT(*) FROM userDepartmentAccess WHERE empno=@empno)>0
	BEGIN
		SELECT @sqlFilters = @sqlFilters + ' AND emp.family_Code in (SELECT family_code FROM userDepartmentAccess WHERE empno=@empno)';
	END

SELECT @sql=N'
Declare @currentCycleDesc varchar(50), @nextcycleid int, @nextCycleDesc varchar(50);
SELECT @nextcycleid=EC.nextCycleID, @currentCycleDesc=EC.CycleDescription,   @nextCycleDesc=ECN.CycleDescription
FROM EvaluationsCycle EC 
LEFT JOIN EvaluationsCycle ECN on EC.nextCycleID=ECN.ID 
WHERE EC.ID = @cycleID;
WITH regions_CTE( regionCode, OPerfImproNeededCount, OBuildingCapabilityCount, OAchievingPerformanceCount, OLeadingPerformanceCount)
AS
(
SELECT 
CASE WHEN (emp.region in (''RAIS'', ''RAIC'', ''RAUS'', ''REGY'') or emp.pay_cs in (''5171'', ''5173'', ''5509'')) THEN ''NA'' 
WHEN emp.region in (''RGRE'', ''RGEN'', ''RHOL'',''RZ01'',''RTRN'', ''RKAZ'') THEN ''EUROPE''
WHEN emp.region in (''ROMN'', ''RQAT'', ''RUAE'', ''RDRD'',''RIRQ'',''RMUR'', ''RLIB'') and emp.pay_cs not in (''5171'', ''5173'', ''5509'') THEN ''GULF''
WHEN emp.region in ( ''RSAR'', ''RCYP'') THEN ''KSA'' ELSE ''OTHER'' END as regionCode,
SUM( CASE ES.OSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS OPerfImproNeededCount,
SUM( CASE ES.OSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS OBuildingCapabilityCount,
SUM( CASE ES.OSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS OAchievingPerformanceCount,
SUM( CASE ES.OSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS OLeadingPerformanceCount
FROM dbo.EvaluationScores ES
INNER JOIN dbo.Evaluations E ON E.EvaluationID=ES.EvaluationID
INNER JOIN dbo.vw_arco_employee EMP ON E.EmployeeID=EMP.empno
WHERE E.CycleID=@cycleID';
--calibrated
IF @calibrated=0
BEGIN 
SELECT @sql=@sql+' AND E.State IN (5,6) AND ES.State=4 ';
END
IF @calibrated=1
BEGIN 
SELECT @sql=@sql+' AND E.State=6 AND ES.State=5 ';
END

SELECT @sql = @sql + @sqlFilters
SELECT @sql = @sql + ' GROUP BY emp.region, emp.pay_cs
)
SELECT regions_CTE.regionCode, 
@currentCycleDesc as currentPeriodDescription, 
SUM(regions_CTE.OPerfImproNeededCount) AS OPerfImproNeededCount,
CAST(ROUND(CAST (
	SUM(regions_CTE.OPerfImproNeededCount)/
	CAST(
		SUM(regions_CTE.OPerfImproNeededCount)+SUM(regions_CTE.OBuildingCapabilityCount)+
		SUM(regions_CTE.OAchievingPerformanceCount)+SUM(regions_CTE.OLeadingPerformanceCount) 
		AS float
		)
	AS DECIMAL(5,5)
	)
* 100
,2) as DECIMAL(4,2)) 
AS OPerfImproNeededPerc,
SUM(regions_CTE.OBuildingCapabilityCount) AS OBuildingCapabilityCount,
	CAST(ROUND(CAST (
		SUM(regions_CTE.OBuildingCapabilityCount)/
		CAST(
			SUM(regions_CTE.OPerfImproNeededCount)+SUM(regions_CTE.OBuildingCapabilityCount)+
			SUM(regions_CTE.OAchievingPerformanceCount)+SUM(regions_CTE.OLeadingPerformanceCount) 
			AS float
			)
		AS DECIMAL(5,5)
		)
	* 100
	,2) as DECIMAL(4,2)) 
	AS OBuildingCapabilityPerc,
SUM(regions_CTE.OAchievingPerformanceCount) AS OAchievingPerformanceCount,
CAST(ROUND(CAST (
	SUM(regions_CTE.OAchievingPerformanceCount)/
	CAST(
		SUM(regions_CTE.OPerfImproNeededCount)+SUM(regions_CTE.OBuildingCapabilityCount)+
		SUM(regions_CTE.OAchievingPerformanceCount)+SUM(regions_CTE.OLeadingPerformanceCount) 
		AS float
		)
	AS DECIMAL(5,5)
	)
* 100
,2) as DECIMAL(4,2)) 
AS OAchievingPerformancePerc,
SUM(regions_CTE.OLeadingPerformanceCount) AS OLeadingPerformanceCount,
CAST(ROUND(CAST (
	SUM(regions_CTE.OLeadingPerformanceCount)/
	CAST(
		SUM(regions_CTE.OPerfImproNeededCount)+SUM(regions_CTE.OBuildingCapabilityCount)+
		SUM(regions_CTE.OAchievingPerformanceCount)+SUM(regions_CTE.OLeadingPerformanceCount) 
		AS float
		)
	AS DECIMAL(5,5)
	)
* 100
,2) as DECIMAL(4,2)) 
AS OLeadingPerformancePerc
FROM Regions_CTE
GROUP BY regions_CTE.regionCode
';
EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @region=@region, @projectCode=@projectCode, @cycleID=@cycleID, @calibrated=@calibrated, @family=@family, @empno=@empno
	  ";
		
	  $query = $this->connection->prepare($queryString);
	  $query->bindValue(':region', $filters['region'], PDO::PARAM_STR);
	  $query->bindValue(':projectCode', $filters['project'], PDO::PARAM_STR);
	  $query->bindValue(':family', $filters['family'], PDO::PARAM_STR);
	  $query->bindValue(':cycleID', $filters['cycleid'], PDO::PARAM_INT);
	  $query->bindValue(':calibrated', $filters['calibrated'], PDO::PARAM_INT);
	  $query->bindValue(':userid', $filters['loggedin_user'], PDO::PARAM_STR);
	  $result["success"] = $query->execute();
	  $result["errorMessage"] = $query->errorInfo();
	  $query->setFetchMode(PDO::FETCH_ASSOC);
	  //$query->nextRowset(); // send back the select not the insert.
	  $result["statsRegion"] = $query->fetchAll();
	  return $result;
 }

/*****
 *	Get stats per section
 *
 */
 public function GetCompanyStatsBySection($filters)
 {
	  $queryString = "
	DECLARE @sql NVARCHAR(max);
	DECLARE @sqlFilters NVARCHAR(max)='',@sqlFilters2 NVARCHAR(max)='';
	DECLARE @ParmDefinition NVARCHAR(max);
	DECLARE  @region NVARCHAR(15)=:region, @projectCode varchar(5)=:projectCode, @cycleID INT=:cycleID, @calibrated INT=:calibrated, @family varchar(10)=:family,
	@empno varchar(5)=:userid;
	SET @ParmDefinition = N'@region NVARCHAR(15), @projectCode varchar(5), @cycleID INT, @calibrated INT, @family varchar(10), @empno varchar(5)'
	
	 IF @region IS NOT NULL AND @region <> '' AND @region = 'na'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND (emp.region in (''RAIS'', ''RAIC'', ''RAUS'', ''REGY'') or emp.pay_cs in (''5171'', ''5173'', ''5509'')) ';
			 SELECT @sqlFilters2 = @sqlFilters2 + ' AND (emp2.region in (''RAIS'', ''RAIC'', ''RAUS'', ''REGY'') or emp2.pay_cs in (''5171'', ''5173'', ''5509'')) ';
		 END
	
	 IF @region IS NOT NULL AND @region <> '' AND @region = 'europe'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''RGRE'', ''RGEN'', ''RHOL'',''RZ01'',''RTRN'', ''RKAZ'') ';
			 SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.region in (''RGRE'', ''RGEN'', ''RHOL'',''RZ01'',''RTRN'', ''RKAZ'') ';
		 END

	 IF @region IS NOT NULL AND @region <> '' AND @region = 'gulf'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''ROMN'', ''RQAT'', ''RUAE'', ''RDRD'',''RIRQ'',''RMUR'') and emp.pay_cs not in (''5171'', ''5173'', ''5509'') ';
			 SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.region in (''ROMN'', ''RQAT'', ''RUAE'', ''RDRD'',''RIRQ'',''RMUR'') and emp2.pay_cs not in (''5171'', ''5173'', ''5509'') ';
		 END

	 IF @region IS NOT NULL AND @region <> '' AND @region = 'ksa'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.region in ( ''RSAR'', ''RCYP'')';
			 SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.region in ( ''RSAR'', ''RCYP'')';
		 END

	 IF @projectCode IS NOT NULL AND @projectCode <> '' AND @projectCode <> 'Select All'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.pay_cs=@projectCode ';
		 SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.pay_cs=@projectCode ';
		 END
	IF @family IS NOT NULL AND @family <> '' AND @family <> 'Select All'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.family_code=@family ';
		 SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.family_code=@family ';
		 END
	
	IF @family = 'Select All' AND (SELECT COUNT(*) FROM userDepartmentAccess WHERE empno=@empno)>0
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.family_Code in (SELECT family_code FROM userDepartmentAccess WHERE empno=@empno)';
		 SELECT @sqlFilters2 = @sqlFilters2 + ' AND emp2.family_code in (SELECT family_code FROM userDepartmentAccess WHERE empno=@empno)';
		 END
 SELECT @sql=N'
 Declare @currentCycleDesc varchar(50), @nextcycleid int, @nextCycleDesc varchar(50);
 SELECT @nextcycleid=EC.nextCycleID, @currentCycleDesc=EC.CycleDescription,   @nextCycleDesc=ECN.CycleDescription
 FROM EvaluationsCycle EC 
 LEFT JOIN EvaluationsCycle ECN on EC.nextCycleID=ECN.ID 
 WHERE EC.ID = @cycleID;
 SELECT @currentCycleDesc as currentPeriodDescription, 
 SUM( CASE ES.PSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS PPerfImproNeededCount,
 SUM( CASE ES.PSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS PBuildingCapabilityCount,
 SUM( CASE ES.PSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS PAchievingPerformanceCount,
 SUM( CASE ES.PSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS PLeadingPerformanceCount,
 SUM( CASE ES.GSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS GPerfImproNeededCount,
 SUM( CASE ES.GSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS GBuildingCapabilityCount,
 SUM( CASE ES.GSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS GAchievingPerformanceCount,
 SUM( CASE ES.GSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS GLeadingPerformanceCount,
 SUM( CASE ES.CSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS CPerfImproNeededCount,
 SUM( CASE ES.CSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS CBuildingCapabilityCount,
 SUM( CASE ES.CSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS CAchievingPerformanceCount,
 SUM( CASE ES.CSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS CLeadingPerformanceCount,
 SUM( CASE ES.LSDescription WHEN ''Performance Improvement Needed'' THEN 1 ELSE 0 END) AS LPerfImproNeededCount,
 SUM( CASE ES.LSDescription WHEN ''Building Capability'' THEN 1 ELSE 0 END) AS LBuildingCapabilityCount,
 SUM( CASE ES.LSDescription WHEN ''Achieving Performance'' THEN 1 ELSE 0 END) AS LAchievingPerformanceCount,
 SUM( CASE ES.LSDescription WHEN ''Leading Performance'' THEN 1 ELSE 0 END) AS LLeadingPerformanceCount 
 FROM dbo.EvaluationScores ES
INNER JOIN dbo.Evaluations E ON E.EvaluationID=ES.EvaluationID
INNER JOIN dbo.vw_arco_employee EMP ON E.EmployeeID=EMP.empno
WHERE E.CycleID=@cycleID';
--calibrated
IF @calibrated=0
BEGIN 
SELECT @sql=@sql+' AND E.State IN (5,6) AND ES.State=4';
END
IF @calibrated=1
BEGIN 
SELECT @sql=@sql+' AND E.State=6 AND ES.State=5';
END
 SELECT @sql = @sql + @sqlFilters
 EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @region=@region, @projectCode=@projectCode, @cycleID=@cycleID, @calibrated=@calibrated, @family=@family, @empno=@empno			 
	  ";
	  $query = $this->connection->prepare($queryString);
	  $query->bindValue(':region', $filters['region'], PDO::PARAM_STR);
	  $query->bindValue(':projectCode', $filters['project'], PDO::PARAM_STR);
	  $query->bindValue(':family', $filters['family'], PDO::PARAM_STR);
	  $query->bindValue(':cycleID', $filters['cycleid'], PDO::PARAM_INT);
	  $query->bindValue(':calibrated', $filters['calibrated'], PDO::PARAM_INT);
	  $query->bindValue(':userid', $filters['loggedin_user'], PDO::PARAM_STR);
	  $result["success"] = $query->execute();
	  $result["errorMessage"] = $query->errorInfo();
	  $query->setFetchMode(PDO::FETCH_ASSOC);
	  //$query->nextRowset(); // send back the select not the insert.
	  $result["scoresPerSection"] = $query->fetchAll();
	  return $result;
	
 }


/*****
 *	Get stats per question
 *
 */
 public function GetCompanyStatsByQuestion($filters)
 {
	  $queryString = "
	DECLARE @sql NVARCHAR(max);
	DECLARE @sqlFilters NVARCHAR(max)='';
	DECLARE @ParmDefinition NVARCHAR(max);
	DECLARE  @region NVARCHAR(15)=:region, @projectCode varchar(5)=:projectCode, @cycleID INT=:cycleID, @calibrated INT=:calibrated, @family varchar(10)=:family,
	@empno varchar(5)=:userid;
	SET @ParmDefinition = N'@region NVARCHAR(15), @projectCode varchar(5), @cycleID INT, @calibrated INT, @family varchar(10), @empno varchar(5)'
	
	 IF @region IS NOT NULL AND @region <> '' AND @region = 'na'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND (emp.region in (''RAIS'', ''RAIC'', ''RAUS'', ''REGY'') or emp.pay_cs in (''5171'', ''5173'', ''5509'')) ';
			
		 END
	
	 IF @region IS NOT NULL AND @region <> '' AND @region = 'europe'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''RGRE'', ''RGEN'', ''RHOL'',''RZ01'',''RTRN'', ''RKAZ'') ';
			
		 END

	 IF @region IS NOT NULL AND @region <> '' AND @region = 'gulf'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''ROMN'', ''RQAT'', ''RUAE'', ''RDRD'',''RIRQ'',''RMUR'') and emp.pay_cs not in (''5171'', ''5173'', ''5509'') ';
			
		 END

	 IF @region IS NOT NULL AND @region <> '' AND @region = 'ksa'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.region in ( ''RSAR'', ''RCYP'')';
			
		 END

	 IF @projectCode IS NOT NULL AND @projectCode <> '' AND @projectCode <> 'Select All'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.pay_cs=@projectCode ';
		 
		 END

	IF @family IS NOT NULL AND @family <> '' AND @family <> 'Select All'
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.family_code=@family ';
		 
		 END
	
	IF @family = 'Select All' AND (SELECT COUNT(*) FROM userDepartmentAccess WHERE empno=@empno)>0
		 BEGIN
			 SELECT @sqlFilters = @sqlFilters + ' AND emp.family_Code in (SELECT family_code FROM userDepartmentAccess WHERE empno=@empno)';
		 END
	
		SELECT @sql=N'
		SELECT QS.ID, QS.SectionDescription, Q.QuestionDescripton, AVG(
		CASE WHEN A.Answer NOT LIKE ''%[^0-9]%''
					THEN CAST(A.Answer AS INT)
					ELSE 0
				END) AS average
			FROM dbo.Answers A 
			INNER JOIN dbo.Evaluations E ON E.EvaluationID=A.EvaluationID
			INNER JOIN	dbo.vw_arco_employee emp ON emp.empno=E.EmployeeID
			INNER JOIN dbo.Questions Q ON A.QuestionID=Q.ID AND q.QuestionTypeID=1
			INNER JOIN dbo.QuestionSections QS ON QS.ID=Q.SectionID
			WHERE QS.ID<>3 --Not Goals
			AND Q.QuestionTypeID =1 --only questions with 1-4 input
			';
			--calibrated
			IF @calibrated=0
			BEGIN 
			SELECT @sql=@sql+' AND E.State IN (5,6) AND A.State=4';
			END
			IF @calibrated=1
			BEGIN 
			SELECT @sql=@sql+' AND E.State=6 AND A.State=5';
			END			 
		--main filters
		SELECT @sql = @sql + @sqlFilters
		--group by
		SELECT @sql= @sql+' 
		GROUP BY QS.ID, QS.SectionDescription, Q.ID, Q.QuestionDescripton
		ORDER BY QS.ID, Q.ID';
		EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @region=@region, @projectCode=@projectCode, @cycleID=@cycleID, @calibrated=@calibrated, @family=@family, 
		@empno=@empno		 
	  ";
	  $query = $this->connection->prepare($queryString);
	  $query->bindValue(':region', $filters['region'], PDO::PARAM_STR);
	  $query->bindValue(':projectCode', $filters['project'], PDO::PARAM_STR);
	  $query->bindValue(':family', $filters['family'], PDO::PARAM_STR);
	  $query->bindValue(':cycleID', $filters['cycleid'], PDO::PARAM_INT);
	  $query->bindValue(':calibrated', $filters['calibrated'], PDO::PARAM_INT);
	  $query->bindValue(':userid', $filters['loggedin_user'], PDO::PARAM_STR);
	  $result["success"] = $query->execute();
	  $result["errorMessage"] = $query->errorInfo();
	  $query->setFetchMode(PDO::FETCH_ASSOC);
	  //$query->nextRowset(); // send back the select not the insert.
	  $result["avgScorePerQuestion"] = $query->fetchAll();
	  return $result;
 }

/*****
 *	Get Satisfcation by question Analysis
 *
 */

public function GetSatisfactionByQuestion($filters)
{
	$queryString = "
	DECLARE @sql NVARCHAR(max);
	DECLARE @sqlFilters NVARCHAR(max)='',@sqlFilters2 NVARCHAR(max)='';
	DECLARE @ParmDefinition NVARCHAR(max);
	DECLARE  @region NVARCHAR(15)=:region, @projectCode varchar(5)=:projectCode, @cycleID INT=:cycleid,  @calibrated INT =:calibrated,  
	@family varchar(10)=:family, @empno varchar(5)=:userid;
	
	
	SET @ParmDefinition = N'@region NVARCHAR(15), @projectCode varchar(5),  @cycleID INT, @calibrated INT, @family varchar(10), @empno varchar(5)'

	IF @region IS NOT NULL AND @region <> '' AND @region = 'NA'
	 BEGIN
			  SELECT @sqlFilters = @sqlFilters + ' AND (emp.region in (''RAIS'', ''RAIC'', ''RAUS'', ''REGY'') or emp.pay_cs in (''5171'', ''5173'', ''5509'')) '
	 END

	IF @region IS NOT NULL AND @region <> '' AND @region = 'EUROPE'
	 BEGIN
			  SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''RGRE'', ''RGEN'', ''RHOL'',''RZ01'',''RTRN'', ''RKAZ'') '
	 END

	IF @region IS NOT NULL AND @region <> '' AND @region = 'GULF'
	 BEGIN
			  SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''ROMN'', ''RQAT'', ''RUAE'', ''RDRD'',''RIRQ'',''RMUR'') and emp.pay_cs not in (''5171'', ''5173'', ''5509'') '
	 END

	IF @region IS NOT NULL AND @region <> '' AND @region = 'KSA'
	 BEGIN
			  SELECT @sqlFilters = @sqlFilters + ' AND emp.region in ( ''RSAR'', ''RCYP'')'
	 END

	 IF @projectCode IS NOT NULL AND @projectCode <> '' AND @projectCode <> 'Select All'
	 BEGIN
		  SELECT @sqlFilters = @sqlFilters + ' AND emp.pay_cs=@projectCode '
	 END

	 IF @family IS NOT NULL AND @family <> '' AND @family <> 'Select All'
	 BEGIN
		  SELECT @sqlFilters = @sqlFilters + ' AND emp.family_code=@family '
	 END

	 IF @family = 'Select All' AND (SELECT COUNT(*) FROM userDepartmentAccess WHERE empno=@empno)>0
	 BEGIN
		 SELECT @sqlFilters = @sqlFilters + ' AND emp.family_Code in (SELECT family_code FROM userDepartmentAccess WHERE empno=@empno)';
	 END

	 SELECT @sql=N'
	SELECT
		 REPLACE(REPLACE(Q.QuestionDescripton, ''“to be completed by the employee only”'',''''),''(to be completed by the employee only)'','''') as Question,
		 COUNT(a.Answer) AS populationAnswered,
		 CAST( CAST(SUM( CASE a.Answer WHEN ''Very dissatisfied'' THEN 1 ELSE 0 END) AS FLOAT)/ CAST(COUNT(a.Answer) AS float) AS DECIMAL (4,3)) * 100 AS vdisatisfied,
		 CAST( CAST(SUM( CASE a.Answer WHEN ''Dissatisfied'' THEN 1 ELSE 0 END) AS FLOAT)/ CAST(COUNT(a.Answer)  AS float) AS DECIMAL (4,3))  * 100 AS disatisfied,
		 CAST( CAST(SUM( CASE a.Answer WHEN ''Neither satisfied nor dissatisfied'' THEN 1 ELSE 0 END)AS FLOAT)/ CAST(COUNT(a.Answer)  AS float) AS DECIMAL (4,3)) * 100  AS nsatisfied,
		 CAST( CAST(SUM( CASE a.Answer WHEN ''Satisfied'' THEN 1 ELSE 0 END)AS FLOAT)/ CAST(COUNT(a.Answer)  AS float) AS DECIMAL (4,3))  * 100 AS satisfied,
		 CAST( CAST(SUM( CASE a.Answer WHEN ''Very Satisfied'' THEN 1 ELSE 0 END)AS FLOAT)/ CAST(COUNT(a.Answer) AS float) AS DECIMAL (4,3)) * 100  AS vsatisfied,
		 SUM( CASE a.Answer WHEN ''Very dissatisfied'' THEN 1 ELSE 0 END)  AS vdisatisfiedCnt,
		 SUM( CASE a.Answer WHEN ''Dissatisfied'' THEN 1 ELSE 0 END) AS disatisfiedCnt,
		 SUM( CASE a.Answer WHEN ''Neither satisfied nor dissatisfied'' THEN 1 ELSE 0 END) AS nsatisfiedCnt,
		 SUM( CASE a.Answer WHEN ''Satisfied'' THEN 1 ELSE 0 END) AS satisfiedCnt,
		 SUM( CASE a.Answer WHEN ''Very Satisfied'' THEN 1 ELSE 0 END)  AS vsatisfiedCnt
	FROM dbo.Answers A
	INNER JOIN dbo.Questions Q ON Q.ID=A.QuestionID
	INNER JOIN dbo.Evaluations E ON E.EvaluationID=A.EvaluationID
	INNER JOIN dbo.vw_arco_employee EMP ON EMP.empno=E.EmployeeID
	WHERE A.QuestionID IN (12,14) AND E.CycleID=@cycleID';
	--calibrated
	IF @calibrated=0
	BEGIN 
	SELECT @sql=@sql+' AND E.State IN (5,6)';
	END
	IF @calibrated=1
	BEGIN 
	SELECT @sql=@sql+' AND E.State=6';
	END
	 
	 --main filters
	SELECT @sql = @sql + @sqlFilters
	--group by
	 SELECT @sql= @sql+' Group By Q.QuestionDescripton';
	
	 EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @region=@region, @projectCode=@projectCode, @cycleID=@cycleID, @calibrated=@calibrated, @family=@family, @empno=@empno
	";
	$query = $this->connection->prepare($queryString);
	$query->bindValue(':region', $filters['region'], PDO::PARAM_STR);
	$query->bindValue(':projectCode', $filters['project'], PDO::PARAM_STR);
	$query->bindValue(':family', $filters['family'], PDO::PARAM_STR);
	$query->bindValue(':cycleid', $filters['cycleid'], PDO::PARAM_INT);
	$query->bindValue(':calibrated', $filters['calibrated'], PDO::PARAM_INT);
	$query->bindValue(':userid', $filters['loggedin_user'], PDO::PARAM_STR);
	$result["success"] = $query->execute();
	$result["errorMessage"] = $query->errorInfo();
	$query->setFetchMode(PDO::FETCH_ASSOC);
	$result["satisfactionByQuestion"] = $query->fetchAll();
	return $result;
}


/*****
 *	Get Satisfcation by question, by grade Analysis
 *
 */

public function GetSatisfactionByGradeQuestion($filters)
{
	$queryString = "
	DECLARE @sql NVARCHAR(max);
	DECLARE @sqlFilters NVARCHAR(max)='',@sqlFilters2 NVARCHAR(max)='';
	DECLARE @ParmDefinition NVARCHAR(max);
	DECLARE  @region NVARCHAR(15)=:region, @projectCode varchar(5)=:projectCode, @cycleID INT=:cycleid, @questionid INT=:questionid, @calibrated INT=:calibrated,
	@family varchar(10)=:family, @empno varchar(5)=:userid;
	
	
	SET @ParmDefinition = N'@region NVARCHAR(15), @projectCode varchar(5),  @cycleID INT, @questionid INT, @calibrated INT, @family varchar(10), @empno varchar(5)'

	IF @region IS NOT NULL AND @region <> '' AND @region = 'NA'
	 BEGIN
			  SELECT @sqlFilters = @sqlFilters + ' AND (emp.region in (''RAIS'', ''RAIC'', ''RAUS'', ''REGY'') or emp.pay_cs in (''5171'', ''5173'', ''5509'')) '
	 END

	IF @region IS NOT NULL AND @region <> '' AND @region = 'EUROPE'
	 BEGIN
			  SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''RGRE'', ''RGEN'', ''RHOL'',''RZ01'',''RTRN'', ''RKAZ'') '
	 END

	IF @region IS NOT NULL AND @region <> '' AND @region = 'GULF'
	 BEGIN
			  SELECT @sqlFilters = @sqlFilters + ' AND emp.region in (''ROMN'', ''RQAT'', ''RUAE'', ''RDRD'',''RIRQ'',''RMUR'') and emp.pay_cs not in (''5171'', ''5173'', ''5509'') '
	 END

	IF @region IS NOT NULL AND @region <> '' AND @region = 'KSA'
	 BEGIN
			  SELECT @sqlFilters = @sqlFilters + ' AND emp.region in ( ''RSAR'', ''RCYP'')'
	 END

	 IF @projectCode IS NOT NULL AND @projectCode <> '' AND @projectCode <> 'Select All'
	 BEGIN
		  SELECT @sqlFilters = @sqlFilters + ' AND emp.pay_cs=@projectCode '
	 END

	 IF @family IS NOT NULL AND @family <> '' AND @family <> 'Select All'
	 BEGIN
		  SELECT @sqlFilters = @sqlFilters + ' AND emp.family_code=@family '
	 END

	 IF @family = 'Select All' AND (SELECT COUNT(*) FROM userDepartmentAccess WHERE empno=@empno)>0
	 BEGIN
		 SELECT @sqlFilters = @sqlFilters + ' AND emp.family_Code in (SELECT family_code FROM userDepartmentAccess WHERE empno=@empno)';
	 END
	 
	 SELECT @sql=N'
	SELECT
	E.empGrade,REPLACE(REPLACE(Q.QuestionDescripton, ''“to be completed by the employee only”'',''''),'' (to be completed by the employee only)'','''') as Question,
	COUNT(a.Answer) AS populationAnswered,
	CAST( CAST(SUM( CASE a.Answer WHEN ''Very dissatisfied'' THEN 1 ELSE 0 END) AS FLOAT)/ CAST(COUNT(a.Answer) AS float) AS DECIMAL (4,3)) * 100 AS vdisatisfied,
	CAST( CAST(SUM( CASE a.Answer WHEN ''Dissatisfied'' THEN 1 ELSE 0 END) AS FLOAT)/ CAST(COUNT(a.Answer)  AS float) AS DECIMAL (4,3))  * 100 AS disatisfied,
	CAST( CAST(SUM( CASE a.Answer WHEN ''Neither satisfied nor dissatisfied'' THEN 1 ELSE 0 END)AS FLOAT)/ CAST(COUNT(a.Answer)  AS float) AS DECIMAL (4,3)) * 100  AS nsatisfied,
	CAST( CAST(SUM( CASE a.Answer WHEN ''Satisfied'' THEN 1 ELSE 0 END)AS FLOAT)/ CAST(COUNT(a.Answer)  AS float) AS DECIMAL (4,3))  * 100 AS satisfied,
	CAST( CAST(SUM( CASE a.Answer WHEN ''Very Satisfied'' THEN 1 ELSE 0 END)AS FLOAT)/ CAST(COUNT(a.Answer) AS float) AS DECIMAL (4,3)) * 100  AS vsatisfied,
	SUM( CASE a.Answer WHEN ''Very dissatisfied'' THEN 1 ELSE 0 END)  AS vdisatisfiedCnt,
	SUM( CASE a.Answer WHEN ''Dissatisfied'' THEN 1 ELSE 0 END) AS disatisfiedCnt,
	SUM( CASE a.Answer WHEN ''Neither satisfied nor dissatisfied'' THEN 1 ELSE 0 END) AS nsatisfiedCnt,
	SUM( CASE a.Answer WHEN ''Satisfied'' THEN 1 ELSE 0 END) AS satisfiedCnt,
	SUM( CASE a.Answer WHEN ''Very Satisfied'' THEN 1 ELSE 0 END)  AS vsatisfiedCnt
	FROM dbo.Answers A
	INNER JOIN dbo.Questions Q ON Q.ID=A.QuestionID
	INNER JOIN dbo.Evaluations E ON E.EvaluationID=A.EvaluationID
	INNER JOIN dbo.vw_arco_employee EMP ON EMP.empno=E.EmployeeID
	WHERE A.QuestionID IN (@questionid) AND E.CycleID=@cycleID';
	--calibrated
	IF @calibrated=0
	BEGIN 
	SELECT @sql=@sql+' AND E.State IN (5,6)';
	END
	IF @calibrated=1
	BEGIN 
	SELECT @sql=@sql+' AND E.State=6';
	END
	 
	 --main filters
	SELECT @sql = @sql + @sqlFilters
	--group by
	 SELECT @sql= @sql+' GROUP BY e.empGrade,  Q.QuestionDescripton';
	
	 EXEC sp_ExecuteSQL @sql,  @ParmDefinition, @region=@region, @projectCode=@projectCode, @cycleID=@cycleID, @questionid=@questionid, @calibrated=@calibrated, @family=@family,
	 @empno=@empno
	";
	$query = $this->connection->prepare($queryString);
	$query->bindValue(':questionid', $filters['questionid'], PDO::PARAM_INT);
	$query->bindValue(':region', $filters['region'], PDO::PARAM_STR);
	$query->bindValue(':projectCode', $filters['project'], PDO::PARAM_STR);
	$query->bindValue(':family', $filters['family'], PDO::PARAM_STR);
	$query->bindValue(':cycleid', $filters['cycleid'], PDO::PARAM_INT);
	$query->bindValue(':calibrated', $filters['calibrated'], PDO::PARAM_INT);
	$query->bindValue(':userid', $filters['loggedin_user'], PDO::PARAM_STR);
	$result["success"] = $query->execute();
	$result["errorMessage"] = $query->errorInfo();
	$query->setFetchMode(PDO::FETCH_ASSOC);
	$result["satisfactionByQuestion"] = $query->fetchAll();
	return $result;
}

/*****
 *	Get Evaluators; list of evaluators for the reviewer to select.
 *
 */

public function GetEvaluators($reviewer)
{
	$queryString = "
	DECLARE @empno as varchar(5) = :reviewer;
	Declare @cycleid as int;
	SELECT @cycleid = ID FROM EvaluationsCycle WHERE status=1 and questionaireInputStatus=1;
	SELECT emp.empno as 'empNo', rtrim(ltrim(emp.family_name))+' - '+rtrim(ltrim(emp.first_name)) as 'empName', Evals.count AS AssignedAsEvaluator
	FROM dbo.ReportingLine RL
	INNER JOIN dbo.vw_arco_employee emp ON emp.empno = RL.empnosource
	OUTER APPLY(
	SELECT COUNT(RL2.empnosource) AS count FROM dbo.ReportingLine RL2 WHERE RL2.empnotarget= RL.empnosource AND RL2.state=4 AND ISNULL(RL2.excludeFromCycles,0)<>@cycleid
	)Evals
	 WHERE RL.empnotarget=@empno AND RL.state=4 AND ISNULL(RL.excludeFromCycles,0)<>@cycleid AND Evals.count>0
	";
	$query = $this->connection->prepare($queryString);
	$query->bindValue(':reviewer', $reviewer, PDO::PARAM_STR);
	$result["success"] = $query->execute();
	$result["errorMessage"] = $query->errorInfo();
	$query->setFetchMode(PDO::FETCH_ASSOC);
	$result["evaluators"] = $query->fetchAll();
	return $result;
}



/*****
	 *	Get Available projects for input
	 *
	 */
	public function GetFamilies($userID)
	{
		$queryString="
		DECLARE @empno as varchar(5) = :userid;
		SELECT DISTINCT EMP.family_code, EMP.family_desc, DA.empno 
		FROM dbo.ReportingLine RL
		INNER JOIN dbo.vw_arco_employee EMP ON EMP.empno=RL.empnosource 
		LEFT JOIN dbo.userdepartmentAccess DA ON DA.family_code=EMP.family_code
		WHERE ISNULL(DA.empno, '')= CASE WHEN (SELECT COUNT(*) FROM userDepartmentAccess WHERE empno=@empno)>0 THEN @empno ELSE '' END
		ORDER BY 1
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':userid', $userID, PDO::PARAM_STR);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result["familyList"] = $query->fetchAll();
		return $result;
	}

/*****
 *	Get available evaluation periods for reports. Only provide evaluation periods for searching based on the period configuration.
 *
 */

 public function GetEvaluationPeriods()
 {
	 $queryString = "
	 SELECT EC.ID as CycleID, EC.CycleDescription, ECN.ID as NextCycleID , ECN.CycleDescription as NextCycleDescription
	 FROM EvaluationsCycle EC
	 LEFT JOIN EvaluationsCycle ECN on EC.nextCycleID=ECN.ID
	 WHERE EC.status=1 and EC.questionaireInputStatus=1
	 ";
	 $query = $this->connection->prepare($queryString);
	 $result["success"] = $query->execute();
	 $result["errorMessage"] = $query->errorInfo();
	 $query->setFetchMode(PDO::FETCH_ASSOC);
	 $result["evaluationPeriods"] = $query->fetchAll();
	 return $result;
 }

} // END OF CLASS

?>
