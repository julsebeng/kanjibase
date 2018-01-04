<?php
session_start();

if(isset($_POST['submit'])) {
	if ($_POST['username'] === "admin" && $_POST['password'] === "password") {
		$_SESSION['login'] = TRUE;
		header("Location: search_form.php");
	}
	else
		echo "<p>Incorrect username/password.</p>";
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Admin Login</title>
	<link href="searchform.css" rel="stylesheet" type="text/css">
</head>
<body>
<h1>Login</h1>
	<form action="login.php" method="post" accept-charset="UTF-8">
		Username: <br /><input size ="50" type="text" name="username" value="<?php if(isset($_POST['username'])) echo htmlspecialchars($_POST['username']) ?>" /><br />
		Password: <br /><input size ="50" type="password" name="password" value="" /><br />
		<input type="submit" name="submit" value="Search" />

	</form>

</body>