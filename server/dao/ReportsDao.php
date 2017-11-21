<?php

class ReportsDAO{

	private $connection = NULL;


	public function __construct($conn)
	{
		$this->connection = $conn;
	}


	public function getMyReportingLine($filters)
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
			WHERE (COALESCE(RLE.empnosource, Rl.empnosource)=@empno) AND (COALESCE(RLE.State, RL.state) =4)
		 ) -- end of delclration to get evaluator of the employee
		 SELECT CASE WHEN COALESCE(RLE.STATE,RL.STATE)=4 THEN 2
		 WHEN COALESCE(RLE.STATE,RL.STATE)=3 THEN 3 END AS ReportingOrder,0,  CASE WHEN COALESCE(RLE.STATE,RL.STATE)=4 THEN 'MY EVALUATOR'
					 WHEN COALESCE(RLE.STATE,RL.STATE)=3 THEN 'MY DOTTED LINE MANAGER' END as ParentRelation, '' AS ParentID, COALESCE(RLE.STATE,RL.STATE) AS relState, CASE WHEN COALESCE(RLE.STATE,RL.STATE)=4 THEN 'MY EVALUATOR'
					 WHEN COALESCE(RLE.STATE,RL.STATE)=3 THEN 'MY DOTTED LINE MANAGER'
				 END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		 VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc', 0 AS AssignedEvaluations,0 AS CompletedEvaluations,0 AS Calibrated
		 FROM  vw_arco_employee VEM
		 LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnotarget AND RLE.empnosource=@empno AND RLE.goalCycle=@CycleID
		 LEFT JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@empno AND RL.empnosource NOT IN
		 (SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource=@empno AND goalCycle=@CycleID)
		 WHERE (COALESCE(RLE.empnosource, Rl.empnosource)=@empno) AND (COALESCE(RLE.State, RL.state) in (3,4))
		 -- union to get reviwer
		 UNION
		  SELECT 1 AS ReportingOrder, 0, 'MY REVIEWER' as ParentRelation, '' AS ParentID, 5 AS relState, 'MY REVIEWER' as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		 VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc', 0 AS AssignedEvaluations, 0 AS CompletedEvaluations, 0 AS Calibrated
		 FROM  vw_arco_employee VEM
		 LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnotarget AND RLE.empnosource=@evaluator AND RLE.goalCycle=@CycleID
		 LEFT JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@evaluator AND RL.empnosource NOT IN
		 (SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource=@evaluator AND goalCycle=@CycleID)
		 WHERE (COALESCE(RLE.empnosource, Rl.empnosource)=@evaluator) AND (COALESCE(RLE.State, RL.state) =4)
		 --get reporting to me
		 UNION
		 SELECT 4 AS ReportingOrder, 0, CASE WHEN COALESCE(RLE.STATE,RL.STATE)=4 THEN 'EVALUATOR'
		 WHEN COALESCE(RLE.STATE,RL.STATE)=3 THEN 'DOTTED LINE MANAGER'
	 	 END as 'ParentRelation',  COALESCE(RLE.empnotarget,RL.empnotarget) AS ParentID, COALESCE(RLE.STATE,RL.STATE) AS relState, CASE WHEN COALESCE(RLE.STATE,RL.STATE)=4 THEN 'EVALUATOR'
					 WHEN COALESCE(RLE.STATE,RL.STATE)=3 THEN 'DOTTED LINE MANAGER'
				 END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		 VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc', assigned.totalAssigned AS AssignedEvaluations, completed.Completed AS CompletedEvaluations, calibrated.Calibrated
		 FROM  vw_arco_employee VEM
		 LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnosource AND RLE.goalCycle=@CycleID
		 LEFT JOIN dbo.ReportingLine RL ON VEM.empno = RL.empnosource AND RL.empnosource NOT IN
		 (SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource= rl.empnosource AND goalCycle=@CycleID)
		 OUTER APPLY (
		 SELECT COUNT(*) AS totalAssigned
		 FROM dbo.ReportingLine ERL
		 WHERE ERL.empnotarget=VEM.empno AND ERL.state=4 AND ERL.excludeFromCycles<>@CycleID
		 ) assigned
		 OUTER APPLY (
		 SELECT COUNT(*) AS Completed
		 FROM dbo.ReportingLine ERL
		 INNER JOIN dbo.Evaluations EE ON ERL.empnosource=EE.EmployeeID 
		 WHERE ERL.empnotarget=VEM.empno AND ERL.state=4 AND ERL.excludeFromCycles<>@CycleID AND ee.State in (5,6) AND EE.CycleID=@CycleID
		 ) completed 
		 OUTER APPLY (
			SELECT COUNT(*) AS Calibrated
			FROM dbo.ReportingLine ERL
			INNER JOIN dbo.Evaluations EE ON ERL.empnosource=EE.EmployeeID 
			WHERE ERL.empnotarget=VEM.empno AND ERL.state=4 AND ERL.excludeFromCycles<>@CycleID AND ee.State in (6) AND EE.CycleID=@CycleID
			) calibrated
		 WHERE (COALESCE(RLE.empnotarget, Rl.empnotarget)=@empno) AND (COALESCE(RLE.State, RL.state) in (3,4)) AND (COALESCE(RLE.goalCycle, RL.excludeFromCycles) <> @CycleID) 
		 
		 UNION

		 SELECT 5 AS ReportingOrder, chkRelationship.state, CASE WHEN  COALESCE(RLE.STATE,RL.STATE) = 4 AND chkRelationship.state=4 THEN 'REVIEWER' ELSE 'REV DOTTED' END as ParentRelation,  
		 COALESCE(RLE.empnotarget,RL.empnotarget) AS ParentID, 
		 COALESCE(RLE.STATE,RL.STATE) AS relState, CASE WHEN chkRelationship.state=3 THEN 'EVALUATOR' WHEN COALESCE(RLE.STATE,RL.STATE)=4 THEN 'REVIEWER'
					 WHEN COALESCE(RLE.STATE,RL.STATE)=3 THEN 'DOTTED LINE MANAGER'
				 END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		 VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc', assigned.totalAssigned AS AssignedEvaluations, completed.Completed AS CompletedEvaluations, calibrated.Calibrated
		 FROM  vw_arco_employee VEM
		 LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnosource AND RLE.goalCycle=@CycleID
		 LEFT JOIN dbo.ReportingLine RL ON VEM.empno = RL.empnosource AND RL.empnosource NOT IN
		 (SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource= rl.empnosource AND goalCycle=@CycleID)
		
		 OUTER APPLY (
		 SELECT COUNT(*) AS totalAssigned
		 FROM dbo.ReportingLine ERL
		 WHERE ERL.empnotarget=VEM.empno AND ERL.state=4 AND ERL.excludeFromCycles<>@CycleID
		 ) assigned
		 OUTER APPLY (
		 SELECT COUNT(*) AS Completed
		 FROM dbo.ReportingLine ERL
		 INNER JOIN dbo.Evaluations EE ON ERL.empnosource=EE.EmployeeID 
		 WHERE ERL.empnotarget=VEM.empno AND ERL.state=4 AND ERL.excludeFromCycles<>@CycleID AND ee.State in (5,6) AND EE.CycleID=@CycleID
		 ) completed 
		 OUTER APPLY (
			SELECT COUNT(*) AS Calibrated
			FROM dbo.ReportingLine ERL
			INNER JOIN dbo.Evaluations EE ON ERL.empnosource=EE.EmployeeID 
			WHERE ERL.empnotarget=VEM.empno AND ERL.state=4 AND ERL.excludeFromCycles<>@CycleID AND ee.State in (6) AND EE.CycleID=@CycleID
			) calibrated 
		  OUTER APPLY(
		 SELECT state FROM dbo.ReportingLine WHERE empnosource=COALESCE(RLE.empnotarget,RL.empnotarget) AND empnotarget=@empno
		 )chkRelationship
		 WHERE (COALESCE(RLE.empnotarget, Rl.empnotarget) IN (
		SELECT VEM.empno as 'empNo'
		 FROM  vw_arco_employee VEM
		 LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnosource AND RLE.goalCycle=@CycleID
		 LEFT JOIN dbo.ReportingLine RL ON VEM.empno = RL.empnosource AND RL.empnosource NOT IN
		 (SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource= rl.empnosource AND goalCycle=@CycleID)
		 WHERE (COALESCE(RLE.empnotarget, Rl.empnotarget)=@empno) AND (COALESCE(RLE.State, RL.state) in (3,4)) AND (COALESCE(RLE.goalCycle, RL.excludeFromCycles) <> @CycleID) 
		 )
		 ) AND (COALESCE(RLE.State, RL.state) in (3,4)) AND (COALESCE(RLE.goalCycle, RL.excludeFromCycles) <> @CycleID) 
		 ORDER BY 1 ASC, 3 DESC
	 ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue('empno', $filters['loggedin_user'], PDO::PARAM_STR);
		$query->bindValue(':cycleid',$filters['cycleid'], PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["myReportingLine"] = $query->fetchAll();
		return $result;
	}

/*****
 *	Get reporting line of one evaluator.
 *
 */

	public function getEvaluatorReportingLine($filters)
	{
	 $queryString = "
	 	DECLARE @empno as varchar(5) = :empno;
		DECLARE @CycleID AS INT =:cycleid;
		SELECT 'Reporting to me:' AS ReportingType,COALESCE(RLE.STATE,RL.STATE) AS State, CASE WHEN COALESCE(RLE.STATE,RL.STATE)=4 THEN 'EVALUATOR'
		WHEN COALESCE(RLE.STATE,RL.STATE)=3 THEN 'DOTTED LINE MANAGER'
			END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc', assigned.totalAssigned AS AssignedEvaluations, completed.Completed AS CompletedEvaluations
		FROM  vw_arco_employee VEM
		LEFT JOIN dbo.ReportingLineExceptions RLE ON VEM.empno=RLE.empnosource AND RLE.goalCycle=@CycleID
		LEFT JOIN dbo.ReportingLine RL ON VEM.empno = RL.empnosource AND RL.empnosource NOT IN
		(SELECT empnosource FROM dbo.ReportingLineExceptions WHERE empnosource= rl.empnosource AND goalCycle=@CycleID)
		OUTER APPLY (
		SELECT COUNT(*) AS totalAssigned
		FROM dbo.ReportingLine ERL
		WHERE ERL.empnotarget=VEM.empno AND ERL.state=4 AND ERL.excludeFromCycles<>@CycleID
		) assigned
		OUTER APPLY (
		SELECT COUNT(*) AS Completed
		FROM dbo.ReportingLine ERL
		INNER JOIN dbo.Evaluations EE ON ERL.empnosource=EE.EmployeeID 
		WHERE ERL.empnotarget=VEM.empno AND ERL.state=4 AND ERL.excludeFromCycles<>@CycleID AND ee.State=5 AND EE.CycleID=@CycleID
		) completed 
		WHERE (COALESCE(RLE.empnotarget, Rl.empnotarget)=@empno) AND (COALESCE(RLE.State, RL.state) in (3,4)) AND (COALESCE(RLE.goalCycle, RL.excludeFromCycles) <> @CycleID) 
		ORDER BY 1 ASC, 2 desc
	 ";
        $query = $this->connection->prepare($queryString);
        $query->bindValue('empno', $filters['loggedin_user'], PDO::PARAM_STR);
		$query->bindValue(':cycleid',$filters['cycleid'], PDO::PARAM_INT);
		$result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result["myReportingLine"] = $query->fetchAll();
		return $result;
	}

/*****
 *	Get available evaluation periods for reports.
 *
 */

 public function GetEvaluationPeriods()
 {
	 $queryString = "
	 SELECT EC.ID as CycleID, EC.CycleDescription, ECN.ID as NextCycleID , ECN.CycleDescription as NextCycleDescription
	 FROM EvaluationsCycle EC
	 LEFT JOIN EvaluationsCycle ECN on EC.nextCycleID=ECN.ID
	 --WHERE EC.status=1 and EC.questionaireInputStatus=1
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
