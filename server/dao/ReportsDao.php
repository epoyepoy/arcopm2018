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
	 	DECLARE @empno as varchar(5) = :empno, @CycleID AS INT =:cycleid;
		DECLARE @evaluator as varchar(5) = (
			SELECT VEM.empno
			FROM vw_arco_employee VEM
			INNER JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget
			WHERE  RL.empnosource=@empno AND RL.state=5 AND RL.cycleid=@CycleID
		 ) -- end of delclration to get evaluator of the employee

		SELECT 
		CASE 
			WHEN RL.STATE=5 
				THEN 2
			WHEN RL.STATE=4 
				THEN 4 
			END AS ReportingOrder,
			0,  
		CASE 
			WHEN RL.STATE=5 
				THEN 'MY EVALUATOR'
			WHEN RL.STATE=4 
				THEN 'MY DOTTED LINE MANAGER' 
		END as ParentRelation, 
		'' AS ParentID, RL.STATE AS relState, 
		CASE 
			WHEN RL.STATE=5 
				THEN 'MY EVALUATOR'
			WHEN RL.STATE=4 
				THEN 'MY DOTTED LINE MANAGER'
			END as 'RelationshipDesc', 
		VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc', 0 AS AssignedEvaluations,0 AS CompletedEvaluations,0 AS Calibrated
		FROM  vw_arco_employee VEM
		INNER JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@empno AND RL.cycleid=@CycleID
		-- union to get reviwer
		UNION
		SELECT 1 AS ReportingOrder, 0, 'MY REVIEWER' as ParentRelation, '' AS ParentID, 6 AS relState, 'MY REVIEWER' as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc', 0 AS AssignedEvaluations, 0 AS CompletedEvaluations, 0 AS Calibrated
		FROM  vw_arco_employee VEM
		INNER JOIN dbo.ReportingLine RL ON VEM.empno=RL.empnotarget AND RL.empnosource=@evaluator AND RL.cycleid=@CycleID
		--get reporting to me
		UNION
		SELECT 5 AS ReportingOrder, 0, 
		CASE 
			WHEN RL.STATE=5 
				THEN 'EVALUATOR'
			WHEN RL.STATE=4 
				THEN 'DOTTED LINE MANAGER'
	 	END as 'ParentRelation',  RL.empnotarget AS ParentID, RL.STATE AS relState, 
		CASE 
			WHEN RL.STATE=5 
				THEN 'EVALUATOR'
			WHEN RL.STATE=4 
				THEN 'DOTTED LINE MANAGER'
		END as 'RelationshipDesc', VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc', assigned.totalAssigned AS AssignedEvaluations, completed.Completed AS CompletedEvaluations, calibrated.Calibrated
		FROM  vw_arco_employee VEM
		INNER JOIN dbo.ReportingLine RL ON VEM.empno = RL.empnosource AND RL.cycleid=@CycleID
		OUTER APPLY (
		SELECT COUNT(*) AS totalAssigned
		FROM dbo.ReportingLine ERL
		WHERE ERL.empnotarget=VEM.empno AND ERL.state=5 AND ERL.cycleid=@CycleID
		) assigned
		OUTER APPLY (
		SELECT COUNT(*) AS Completed
		FROM dbo.ReportingLine ERL
		INNER JOIN dbo.Evaluations EE ON ERL.empnosource=EE.EmployeeID 
		WHERE ERL.empnotarget=VEM.empno AND ERL.state=5 AND ERL.cycleid=@CycleID AND ee.State in (6,7) AND EE.CycleID=@CycleID
		) completed 
		OUTER APPLY (
		SELECT COUNT(*) AS Calibrated
		FROM dbo.ReportingLine ERL
		INNER JOIN dbo.Evaluations EE ON ERL.empnosource=EE.EmployeeID 
		WHERE ERL.empnotarget=VEM.empno AND ERL.state=5 AND ERL.cycleid=@CycleID AND ee.State in (7) AND EE.CycleID=@CycleID
		) calibrated
		WHERE Rl.empnotarget=@empno AND RL.state in (4,5) AND RL.cycleid = @CycleID 
		 
		UNION

		SELECT 6 AS ReportingOrder, chkRelationship.state, 
		CASE 
			WHEN RL.STATE = 5 AND chkRelationship.state=5 
				THEN 'REVIEWER' 
			ELSE 'REV DOTTED' 
		END as ParentRelation,  
		RL.empnotarget AS ParentID, RL.STATE AS relState, 
		CASE 
			WHEN chkRelationship.state=4 
				THEN 'EVALUATOR' 
			WHEN RL.STATE=5 
				THEN 'REVIEWER'
			WHEN RL.STATE=4 
				THEN 'DOTTED LINE MANAGER'
		END as 'RelationshipDesc', 
		VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
		VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc', assigned.totalAssigned AS AssignedEvaluations, completed.Completed AS CompletedEvaluations, calibrated.Calibrated
		FROM  vw_arco_employee VEM
		INNER JOIN dbo.ReportingLine RL ON VEM.empno = RL.empnosource AND RL.cycleid=@CycleID
		
		OUTER APPLY (
		SELECT COUNT(*) AS totalAssigned
		FROM dbo.ReportingLine ERL
		WHERE ERL.empnotarget=VEM.empno AND ERL.state=5 AND ERL.cycleid=@CycleID
		) assigned
		OUTER APPLY (
		SELECT COUNT(*) AS Completed
		FROM dbo.ReportingLine ERL
		INNER JOIN dbo.Evaluations EE ON ERL.empnosource=EE.EmployeeID 
		WHERE ERL.empnotarget=VEM.empno AND ERL.state=5 AND ERL.cycleid=@CycleID AND ee.State in (6,7) AND EE.CycleID=@CycleID
		) completed 
		OUTER APPLY (
		SELECT COUNT(*) AS Calibrated
		FROM dbo.ReportingLine ERL
		INNER JOIN dbo.Evaluations EE ON ERL.empnosource=EE.EmployeeID 
		WHERE ERL.empnotarget=VEM.empno AND ERL.state=5 AND ERL.cycleid=@CycleID AND ee.State in (7) AND EE.CycleID=@CycleID
		) calibrated 
		OUTER APPLY(
		SELECT state FROM dbo.ReportingLine WHERE empnosource=RL.empnotarget AND empnotarget=@empno
		)chkRelationship
		WHERE Rl.empnotarget IN (
								SELECT VEM.empno as 'empNo'
								FROM  vw_arco_employee VEM
								INNER JOIN dbo.ReportingLine RL ON VEM.empno = RL.empnosource AND RL.cycleid=@CycleID AND rl.empnotarget=@empno
								) 
				AND RL.state in (4,5) AND RL.cycleid= @CycleID 
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
	 DECLARE @empno as varchar(5) = :empno, @CycleID AS INT =:cycleid;
	 SELECT 'Reporting to me:' AS ReportingType,
	 RL.STATE AS State, 
	 CASE WHEN RL.STATE=5 
			 THEN 'EVALUATOR'
		 WHEN RL.STATE=4 
			 THEN 'DOTTED LINE MANAGER'
	 END as 'RelationshipDesc', 
	 VEM.empno as 'empNo', rtrim(ltrim(VEM.family_name))+' - '+rtrim(ltrim(VEM.first_name)) as 'empName', VEM.job_desc as 'empPosition',
	 VEM.family_desc as 'empDepartment', VEM.pay_cs as 'empSite', VEM.site_desc as 'empSiteDesc', assigned.totalAssigned AS AssignedEvaluations, completed.Completed AS CompletedEvaluations
	 FROM  vw_arco_employee VEM
	 INNER JOIN dbo.ReportingLine RL ON VEM.empno = RL.empnosource AND RL.cycleid=@CycleID
	 OUTER APPLY (
	 SELECT COUNT(*) AS totalAssigned
	 FROM dbo.ReportingLine ERL
	 WHERE ERL.empnotarget=VEM.empno AND ERL.state=5 AND ERL.cycleid=@CycleID
	 ) assigned
	 OUTER APPLY (
	 SELECT COUNT(*) AS Completed
	 FROM dbo.ReportingLine ERL
	 INNER JOIN dbo.Evaluations EE ON ERL.empnosource=EE.EmployeeID 
	 WHERE ERL.empnotarget=VEM.empno AND ERL.state=5 AND ERL.cycleid=@CycleID AND ee.State=6 AND EE.CycleID=@CycleID
	 ) completed 
	 WHERE Rl.empnotarget=@empno AND RL.state IN (4,5) AND RL.cycleid = @CycleID 
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
