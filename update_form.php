<?php 
	require_once("header.php");
	require_once("form_validation.php");
	require_once("dbinsert.php");
	$kanji = NULL;

	if(isset($_POST['update_submit']) && isset($_POST['kanji'])) {
		$kanji = $_POST['kanji'];

		$dbhost = "localhost";
		$dbuser = "php_access";
		$dbpass = "cReT7a2EkApHere";
		$dbname = "kanjibase";
		$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

		//Check for connection errors
		if(mysqli_connect_errno()) {
		echo "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
		mysqli_close($connection);
		}

		//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
		if (!mysqli_set_charset($connection, "utf8")) {
			echo "Error: could not change charset to UTF-8, aborting...";
			mysqli_close($connection);
		}

		$query = "SELECT kanji FROM Chara WHERE kanji='{$kanji}';";
		mysqli_real_escape_string($connection, $query);

		$result = mysqli_query($connection, $query);

		if(!mysqli_num_rows($result)) {
			echo "Kanji doesn't exist. <br />";
			$kanji = NULL;
		}
		else {
			header("Location: insert_form.php?update=" . $kanji);
		}


	}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Update Form</title>
	<link href="inputform.css" rel="stylesheet" type="text/css">
</head>
<body>
	<?php make_header(); ?>
	<h1>Update Form</h1>

		<div id="form">
		<form action="update_form.php" method="post" accept-charset="UTF-8">
			<span id="KanjiInsert">
			<h3>Insert Character to update:</h3>
			Kanji: <br /><input size ="50" type="text" name="kanji" value="<?php echo htmlspecialchars($kanji); ?>" /><br />
			<input type="submit" name="update_submit" value="Submit" />
		</form>
	</div>
</body>