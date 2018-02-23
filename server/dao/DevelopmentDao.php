<?php

class DevelopmentDAO{

	private $connection = NULL;


	public function __construct($conn)
	{
		$this->connection = $conn;
	}


    //  public function getDevelopmentPlan($evalid, $state, $userid)
	// {
	//  $queryString = "
	//  Declare @evalid int = :evalid;
	//  Declare @userid varchar(5) = :userid;
	//  Declare @state int = :state;
    //     SELECT * FROM dbo.DevelopmentPlan WHERE EvaluationID=@evalid and State=@state ORDER BY State DESC;
	// 	IF @@ROWCOUNT = 0
	// 	BEGIN
	// 		INSERT INTO dbo.DevelopmentPlan
	// 		SELECT EvaluationID, Objective, Action, AdditionalInfo, ByWhen, ByWhom, @userid, @state, ApprovedByEvaluator
	// 		FROM dbo.DevelopmentPlan WHERE State = CASE WHEN @state=3 THEN 1 WHEN @state=5 THEN 2 END
	// 	END
    //     ";
    //     $query = $this->connection->prepare($queryString);
    //     $query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
	// 	$query->bindValue(':state', $state, PDO::PARAM_INT);
	// 	$query->bindValue(':userid', $userID, PDO::PARAM_STR);
	// 	$result["success"] = $query->execute();
	// 	$result["errorMessage"] = $query->errorInfo();
    //     $query->setFetchMode(PDO::FETCH_ASSOC);
    //     $result["developmentPlan"] = $query->fetchAll();
	// 	return $result;
	// }
	
	public function getDevelopmentPlanHistory($evalid)
   {
	$queryString = "
	   SELECT * FROM dbo.DevelopmentPlan WHERE EvaluationID=:evalid ORDER BY State ASC;
	   ";
	   $query = $this->connection->prepare($queryString);
	   $query->bindValue(':evalid', $evalid, PDO::PARAM_INT);
	   $result["success"] = $query->execute();
	   $result["errorMessage"] = $query->errorInfo();
	   $query->setFetchMode(PDO::FETCH_ASSOC);
	   $result["developmentPlan"] = $query->fetchAll();
	   return $result;
   }
    public function saveDevelopmentPlan($evalid, $evalPlan, $userID, $state)
	{
	 $queryString = "
		    INSERT INTO dbo.DevelopmentPlan VALUES(:evalid, :Objective, :Action, :AdditionalInfo, :ByWhen, :ByWhom, :userid, :state, 1);";
			$query = $this->connection->prepare($queryString);
			$query->bindValue(':evalid',  $evalid, PDO::PARAM_INT);
            $query->bindValue(':Objective', $evalPlan["Objective"], PDO::PARAM_STR);
            $query->bindValue(':Action', $evalPlan["Action"], PDO::PARAM_INT);
            $query->bindValue(':AdditionalInfo', $evalPlan["AdditionalInfo"], PDO::PARAM_STR);
            $query->bindValue(':ByWhen', $evalPlan["ByWhen"], PDO::PARAM_STR);
			$query->bindValue(':ByWhom', $evalPlan["ByWhom"], PDO::PARAM_STR);
            $query->bindValue(':userid', $userID, PDO::PARAM_STR);
			$query->bindValue(':state', $state, PDO::PARAM_INT);
            $result["success"] = $query->execute();
            $result["errorMessage"] = $query->errorInfo();
            return $result;
	}
     public function updateDevelopmentPlan($evalPlan, $userID)
	{
        $queryString = "UPDATE dbo.DevelopmentPlan SET Objective = :Objective, Action= :Action, AdditionalInfo= :AdditionalInfo, ByWhen=:ByWhen, ByWhom=:ByWhom, UserID=:userid
        WHERE DevelopmentPlanID= :id";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':id', $evalPlan["DevelopmentPlanID"], PDO::PARAM_INT);
        $query->bindValue(':Objective', $evalPlan["Objective"], PDO::PARAM_STR);
        $query->bindValue(':Action', $evalPlan["Action"], PDO::PARAM_INT);
        $query->bindValue(':AdditionalInfo', $evalPlan["AdditionalInfo"], PDO::PARAM_STR);
        $query->bindValue(':ByWhen', $evalPlan["ByWhen"], PDO::PARAM_STR);
		$query->bindValue(':ByWhom', $evalPlan["ByWhom"], PDO::PARAM_STR);
        $query->bindValue(':userid', $userID, PDO::PARAM_STR);
        $result["success"] = $query->execute();
        $result["errorMessage"] = $query->errorInfo();
		return $result;
	}

	public function updateDevelopmentPlanStatus($evalPlan, $userID)
	{
        $queryString = "UPDATE dbo.DevelopmentPlan SET ApprovedByEvaluator = :ApprovedByEvaluator
        WHERE DevelopmentPlanID= :id";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':id', $evalPlan["DevelopmentPlanID"], PDO::PARAM_INT);
        $query->bindValue(':ApprovedByEvaluator', $evalPlan["ApprovedByEvaluator"], PDO::PARAM_STR);
        $result["success"] = $query->execute();
        $result["errorMessage"] = $query->errorInfo();
		return $result;
	}

    public function deleteDevelopmentPlan($devPlanID){
        $queryString = "DELETE FROM dbo.DevelopmentPlan WHERE DevelopmentPlanID = :id";
        $query = $this->connection->prepare($queryString);
        $query->bindValue(':id', $devPlanID, PDO::PARAM_INT);
        $result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        return $result;
    }


} // END OF CLASS

?>
