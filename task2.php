
PRACTICAL TEST 2

2. The following code has security vulnerability issues, provide fixes/solutions.

2.1

<?php

$file = $_GET['view'];
$file = str_replace("..", "", $file);
include("/var/www/html/" .$file);

?>


2.2


<?php
$Dbdriver = "mysql";
$Dbserver = "localhost";
//$Dbport = "1433";
$Dbuser = "dbuser";
$Dbpass = "dbpasswd";
$Dbname = "dbname";

function DBOpenConnection($Db = null)
{
	try
	{
		// be still mindful: https://stackoverflow.com/questions/134099/are-pdo-prepared-statements-sufficient-to-prevent-sql-injection
		global $Dbdriver, $Dbserver, $Dbname, $Dbuser, $Dbpass;
		$Dbdsn = $Dbdriver . ":host=" . $Dbserver . ";dbname=";
		if (is_null($Db)) {
			$Dbdsn .= $Dbname;
		} else {
			$Dbdsn .= $Db;
		}
		$conn = new PDO($Dbdsn, $Dbuser, $Dbpass);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch(Exception $e)
	{
		$Noticetitle = "Error";
		$Noticetype = "Error";
		$Notice = "Error establishing connection to Database server.:<br />";
		$Notice .= "<li>" . $e->getMessage() . "</li>";
		echo $Noticetitle . "<br />" . $Noticetype . "<br />" . $Notice;
	}
	return $conn;
}

$id = $_GET['id'];

try {
	$conn = DBOpenConnection();
	$sql = "SELECT username FROM users WHERE id = :id";
	$stmt = $conn->prepare($sql);
	$stmt->bindValue(':id', $id, PDO::PARAM_INT);

	$stmt->execute(); // true or false
	$stmt->setFetchMode(PDO::FETCH_ASSOC); // true or false

	$res = $stmt->fetchAll();
	print_r($res);
}
catch(PDOException $e) {
	// log exception maybe $e->getMessage();
}
$conn = null;

?>