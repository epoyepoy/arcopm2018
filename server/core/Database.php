<?php
 # Require the MsSQL class
 require_once("dao/MsSQL.php");

 # Users Data Access Object (DAO)
 require_once("dao/UserDAO.php");

 # Evaluations Data Access Object (DAO)
 require_once("dao/EvaluationsDAO.php");
 # Goals Data Access Object (DAO)
 require_once("dao/GoalsDAO.php");
 # Developement Data Access Object (DAO)
 require_once("dao/DevelopmentDAO.php");
  # Statistics Data Access Object (DAO)
 require_once("dao/StatisticsDao.php");
   # Statistics Data Access Object (DAO)
 require_once("dao/AdminDao.php");
 # Initialize and Create a connection
 require_once("dao/ReportsDao.php");
 # Initialize and Create a connection
 $mssql = new MsSQL();
 $mssql->connect();

 # Create the DAOs
 $userDao = new UserDAO($mssql->getConnection());
 $statisticsDao = new StatisticsDao($mssql->getConnection());
 $evaluationsDao = new EvaluationsDAO($mssql->getConnection());
 $goalsDao = new GoalsDAO($mssql->getConnection());
 $developmentDao = new DevelopmentDAO($mssql->getConnection());
 $adminDao = new AdminDAO($mssql->getConnection());
 $reportsDao = new ReportsDao($mssql->getConnection());


?>
