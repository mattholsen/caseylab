<?php

 echo "SCRIPT IS DISABLED"; exit;
require_once("includes/classService.php");

if (!isset($_POST['submit'])) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html>
	<head>
		<title>MINDBODY API Simple Demo</title>
		<link rel="stylesheet" type="text/css" href="../styles/site.css" />
	</head>
	
	<body>
	<form method="post" action="#">
		Source Name:
		<input type="text" size="25" name="sName" value="AccelerantStudiosLLC"/><br/>
		Password:
		<input type="password" size="25" name="password" value="oMh1ajTlIwhtxZsHomLprIdxS9Q="/><br/>
		SiteID:
		<input type="text" size="5" name="siteID" value="-99"/><br/>
		<input type="submit" value="submit" name="submit"/>
	</form>
<?php
} else {
$sourcename = $_POST["sName"];
$password = $_POST["password"];
$siteID = $_POST["siteID"];

// initialize default credentials
$creds = new SourceCredentials($sourcename, $password, array($siteID));

$classService = new MBClassService();
$classService->SetDefaultCredentials($creds);

$result = $classService->GetClassDescriptions(array(), array(), array(), null, null, 10, 0);

$cdsHtml = '<table><tr><td>ID</td><td>Name</td></tr>';
echo '<pre>';
//print_r($result->GetClassDescriptionsResult->ClassDescriptions->ClassDescription);
print_r($result);
echo '</pre>';
$cds = toArray($result->GetClassDescriptionsResult->ClassDescriptions->ClassDescription);
foreach ($cds as $cd) {
	$cdsHtml .= sprintf('<tr><td>%d</td><td>%s</td></tr>', $cd->ID, $cd->Name);
}
$cdsHtml .= '</table>';
	
//echo($cdsHtml); 
}
?>
	</body>
</html>