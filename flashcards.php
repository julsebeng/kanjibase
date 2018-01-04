<?php
session_start();

require_once("makecard.php");

//If a card is passed in through $_GET, make that the new current card
if(isset($_GET['card'])) {
	$card = $_GET['card'];
	$_SESSION['currentcard'] = $card;
}
else
	$card = $_SESSION['currentcard'];

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Kanjibase Study Mode</title>
</head>
<body>
<p><a href="search_form.php">Back to Search</a></p>

<?php

	//If there are no more cards to show, display different content
	//This is a value sent to this page from flashcard_processing.php when all cards in $_SESSION['cards'] have been unset
	if($card == "end") {
		echo "<h1>Congradulations! You've completed the deck!</h1>";
		echo "<ul>";
		echo 	"<li><a href=\"flashcard_processing.php\">Rebuild deck</a></li>";
		echo 	"<li><a href=\"search_form.php\">Return to search</a></li>";
		echo "</ul>";
	}
	else {

		if(isset($_POST['submit'])) {
			draw_card($card, "back");
		}
		else {
			draw_card($card, "front");
		}

	}

?>

</body>