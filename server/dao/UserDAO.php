<?php

class UserDAO{

	private $connection = NULL;


	public function __construct($conn)
	{
		$this->connection = $conn;
	}


    public function getDirectoryUser($username)
	{
		//$arcometUsername=str_replace("@archirodon.net", "@arcomet.ae", $username);
		//$aiciUsername=str_replace("@archirodon.net", "@aici.com.eg", $username);
		//$aicihoUsername=str_replace("@archirodon.net", "@aici-ho.com", $username);
		if(strpos($username,"@"))
			{$userid=substr($username,0, strpos($username,"@"));}
			else {$userid=$username;}
		$queryString = "
		SELECT E.empno as id, E.first_name,E.family_name,E.emailaddress as email,E.[post_title_code] as jobPositionId,E.[job_desc] as jobPositionName, grade, 0 as isLocal,
			CASE WHEN ISNULL(UAA.admintype, '')='all' THEN 1 ELSE 0 END as isAdmin,  ISNULL(URA.region, '') as reportsAccess
			FROM [dbo].[vw_arco_employee] E
			INNER JOIN vw_ADUsers AD on AD.EmployeeID=E.empno
			LEFT JOIN dbo.UserAdminAccess UAA on UAA.empno=E.empno
			LEFT JOIN dbo.UserRegionAccess URA on URA.empno=E.empno
			WHERE AD.sAMAccountName=:aduserid
		AND (E.empstatus='A')
		GROUP BY E.empno, E.first_name,E.family_name,E.emailaddress,E.[post_title_code],E.[job_desc], E.grade, UAA.admintype, URA.region";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':aduserid', $userid, PDO::PARAM_STR);
		//$query->bindValue(':userArco', $username, PDO::PARAM_STR);
		//$query->bindValue(':userArcomet', $arcometUsername, PDO::PARAM_STR);
		//$query->bindValue(':userAici', $aiciUsername, PDO::PARAM_STR);
		//$query->bindValue(':userAiciho', $aicihoUsername, PDO::PARAM_STR);
        $result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
        $query->setFetchMode(PDO::FETCH_ASSOC);
		$result["user"] = $query->fetch();
        return $result;

	}

	public function getLocalUser($username, $password)
	{

		$queryString = "
		select E.empno as id, E.first_name,E.family_name,E.emailaddress as email,E.[post_title_code] as jobPositionId,E.[job_desc] as jobPositionName, grade, 1 as isLocal,
		CASE WHEN ISNULL(UAA.admintype, '')='all' THEN 1 ELSE 0 END as isAdmin,  ISNULL(URA.region, '') as reportsAccess
		FROM [dbo].[vw_arco_employee] E
		INNER JOIN dbo.Users EE ON EE.empno = E.empno
		LEFT JOIN dbo.UserAdminAccess UAA on UAA.empno=E.empno
		LEFT JOIN dbo.UserRegionAccess URA on URA.empno=E.empno
		WHERE EE.email = :username AND EE.password=HashBytes('SHA1', '".$password."') and isInactive=0
		GROUP BY E.empno, E.first_name,E.family_name,E.emailaddress,E.[post_title_code],E.[job_desc], E.grade, UAA.admintype, URA.region";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':username', $username, PDO::PARAM_STR);
        $result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$result["user"] = $query->fetch(PDO::FETCH_ASSOC);
		$result["pass"] = $password;
		return $result;

	}

	public function updatePassword($empno, $newPassword, $oldPassword)
	{
		$queryString = "
		UPDATE Users SET password=HashBytes('SHA1', '".$newPassword."')
		OUTPUT Inserted.ID
		WHERE password=HashBytes('SHA1', '".$oldPassword."') AND empno=:empno;
		";
		$query = $this->connection->prepare($queryString);
		$query->bindValue(':empno', $empno, PDO::PARAM_STR);
        $result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		if ($result["errorMessage"][1]!=null){
			return $result;
		}
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$id = $query->fetch();
		if(empty($id))
			{
			$result['success']=false;
			}
        return $result;
	}


	public function getUsers()
	{

		$queryString = "
		select E.empno as id, E.first_name,E.family_name,E.emailaddress as email,E.[post_title_code] as jobPositionId,E.[job_desc] as jobPositionName,
		   role = CASE WHEN MAX(CASE WHEN R.user_role='administrator' then 1 else 0 END) = 1 THEN 'administrator'
					   WHEN MAX(CASE WHEN R.user_role='ito' then 1 else 0 END) = 1 THEN 'ito'
					   WHEN MAX(CASE WHEN R.user_role='inventory' then 1 else 0 END) = 1 THEN 'inventory'
					   ELSE 'viewer' END,
		   adminRole = CAST(MAX(CASE WHEN R.user_role='administrator' then 1 else 0 END) AS INT),
		   itoRole = MAX(CASE WHEN R.user_role='ito' then 1 else 0 END),
		   inventoryRole = MAX(CASE WHEN R.user_role='inventory' then 1 else 0 END),
		   viewerRole = MAX(CASE WHEN R.user_role='viewer' then 1 else 0 END)
			FROM [dbo].[vw_arco_employee] E INNER JOIN dbo.user_role R ON R.user_id=E.empno
			group by E.empno, E.first_name,E.family_name,E.emailaddress,E.[post_title_code],E.[job_desc]";
		$query = $this->connection->prepare($queryString);
		//$query->execute();

		//$result = $query->fetchAll(PDO::FETCH_ASSOC);

        $result["success"] = $query->execute();
		$result["errorMessage"] = $query->errorInfo();
		$result["users"] = $query->fetchAll(PDO::FETCH_ASSOC);

		return $result;

	}

    public function createArcoUser($id, $role)
    {
        $queryString = "
		INSERT INTO
        user_role(user_id,user_role)
        VALUES(:id,:role)
		";
		$query = $this->connection->prepare($queryString);
        $query->bindValue(':id', $id, PDO::PARAM_STR);
        $query->bindValue(':role', $role, PDO::PARAM_INT);
		$result = $query->execute();
		return ($result && $query->rowCount());
    }

     public function getSupportUser()
        {

            $queryString = "
			SELECT e.empno,LTRIM(E.first_name) as first_name, rtrim(E.family_name) as family_name,E.emailaddress as email, E.office_tel_no as 'Cisco', E.[job_desc] as jobPositionName,
				CASE WHEN E.empno='47046' THEN 'GULF'
					 WHEN E.empno='53374' THEN 'NORTH AFRICA'
					 WHEN E.empno='69721' THEN 'KSA'
					 ELSE 'GREECE'
				END as 'Region'
				FROM [dbo].[vw_arco_employee] E
                WHERE E.empno in ('88244', '47046', '53374','69721', '88221') ORDER BY Region asc
                ";
            $query = $this->connection->prepare($queryString);
           // $query->bindValue(':userid', $user, PDO::PARAM_STR);
            $result["success"] = $query->execute();
            $result["errorMessage"] = $query->errorInfo();
            $result["supportData"] = $query->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }




} // END OF CLASS

?>
