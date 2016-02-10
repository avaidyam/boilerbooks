<?php
	session_start();
	if (!isset($_SESSION['user']))
	{
		header("Location: ../index.php");
		die();
	}
?>
<?php //header('Location: /request/newpurchasesubmitted.php '); ?>
<?php
include '../dbinfo.php';

// define variables and set to empty values
$item = $reason = $vendor = $committee = $cost = $comments = $category = "";

$processing = test_input($_POST["processing"]);
$reimbursed = test_input($_POST["reimbursed"]);
$usr = $_SESSION['user'];
echo $processing;
echo $reimbursed;

if ($processing != '') {
	$stat = 'Processing Reimbursement';
	$purchaseid = $processing;
}
else if ($reimbursed != '') {
	$stat = 'Reimbursed';
	$purchaseid = $reimbursed;

}
else {
	$stat = '';
	echo 'None';
	header('Location: /treasurer/index.php');
	return 1;
}
echo $stat;



try {
	$cost = test_input(str_replace('$','',$cost));
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "UPDATE Purchases SET modifydate = NOW(), status='$stat' WHERE Purchases.purchaseID = '$purchaseid'";

    //$sql = "INSERT INTO Purchases (item)
	//VALUES ('$item')";

	// use exec() because no results are returned
    $conn->exec($sql);
    echo "Updated";
    }
catch(PDOException $e)
    {
    echo $sql . "<br>" . $e->getMessage();
    }

$conn = null;



try {
	$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
	// set the PDO error mode to exception
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$sql = "SELECT U.email, item, committee, status FROM Purchases P
			INNER JOIN Users U ON U.username = P.username
			WHERE P.purchaseID = '$purchaseid'";
	//$stmt->execute();


	foreach ($conn->query($sql) as $row) {


		$email = $row['email'];
		$item = $row['item'];
		$committee =  $row['committee'];
		$status =  $row['status'];
	}

	}
catch(PDOException $e)
	{
	echo $sql . "<br>" . $e->getMessage();
	}

$conn = null;




 $to = $email;
 $subject = "Your purchased item is now $stat";
 
 $message = "<p>$item for $committee is now $stat. 
 Feel free to visit money.pieee.org or contact the IEEE treasurere for more information.</p>";
 
 $header = "From:ieeeboilerbooks@gmail.com \r\n";
 $header .= "MIME-Version: 1.0\r\n";
 $header .= "Content-type: text/html\r\n";
 
 $retval = mail ($to,$subject,$message,$header);
 
 if( $retval == true ) {
	//echo "Message sent successfully...";
 }else {
	//echo "Message could not be sent...";
 }
	


header('Location: /treasurer/index.php');

?>
