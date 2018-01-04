<?php

//Draws a card on the screen, based on values stored in $_SESSION
function draw_card($value, $position = "front") {
	echo "<div id=\"Card\">";
	echo 	"<div id=\"CardHeader\">";
	echo 		"<h3>";
	echo 			$value;
	echo 		"</h3>";
	echo 	"</div>";

	//Draw the front of the card, which includes a submit button
	if($position == "front") {
		echo   "<form action=\"flashcards.php\" method=\"post\" accept-charset=\"UTF-8\">
					Meaning: <br /><input size =\"50\" type=\"text\" name=\"guess\" value=\"\" /><br />
					<input type=\"submit\" name=\"submit\" value=\"Submit\" />
				</form>";

	}
	elseif($position == "back") {
	
		$guess = trim($_POST['guess']);

		$correct_answer = FALSE;

		echo "<div id=\"CardAnswer\"";
		echo 	"<p>";
		echo 		$_SESSION["cards"]["{$value}"]["back"];
		echo 	"</p>";
		echo "</div>";
		
			//If needed, compare the user's input with other possible formats - onyomi, kunyomi, romaji, etc
			if(!empty($guess) && (strtolower($guess) !== strtolower($_SESSION["cards"]["{$value}"]["back"]))) {

				//Compare to onyomi, kunyomi, and respective romaji fields
				if(($_SESSION['cards']["{$value}"]['type'] == 0) && (!empty($kana = return_kana($_SESSION['cards']["{$value}"]['back'], 0)))) {

					if(stristr($kana['onyomi'], $guess) || stristr($kana['kunyomi'], $guess) || stristr($kana['o_romaji'], $guess) || stristr($kana['k_romaji'], $guess)) {
						$correct_answer = TRUE;
					}

				}
				elseif(($_SESSION['cards']["{$value}"]['type'] == 1) && (!empty($kana = return_kana($_SESSION['cards']["{$value}"]['back'], 1)))) {
					if(stristr($kana['kana'], $guess) || stristr($kana['romaji'], $guess))
						$correct_answer = TRUE;

				}

			}
			else {
				//If there is a perfect match, still want kana to be set so that any relevant kana will be printed regardless
				$kana = return_kana($_SESSION['cards']["{$value}"]['back'], $_SESSION['cards']["{$value}"]['type']);
				
				if(!empty($guess))
					$correct_answer = TRUE;

				if(is_null($guess))
					echo "guess is empty.<br />";
			}


			//Draw the kana, if there are any
			foreach ($kana as $kana_name => $kana_content) {
				echo "<p>" . ucfirst($kana_name) . ": " . $kana_content . "</p>";
			}

		echo "<div id=\"CardUserAnswer\"";
		echo 	"<p>";
		echo 		"<em>You wrote:</em> <br />" . $guess;
		echo 	"</p>";

		if($correct_answer)
			echo "<h3>Correct!</h3>";

		echo "</div>";

		echo "<div id=\"Buttons\"";
		echo 	"<ul>";
		echo 		"<li><a href=\"flashcard_processing.php?time=2&card=" . urlencode($value) . "\">Again (show again in 1 minute)</a></li>";
		echo 		"<li><a href=\"flashcard_processing.php?time=1&card=" . urlencode($value) . "\">Pretty good (show again in 5 minutes)</a></li>";
		echo 		"<li><a href=\"flashcard_processing.php?time=0&card=" . urlencode($value) . "\">Great (remove from deck)</a></li>";
		echo 	"</ul>";
		echo "</div>";
	}

	echo "</div>";

}


//Returns the meaning of $value
//$type indicates where to look:
// 0 = for kanji info
// 1 = for vocab info
function get_meaning($value, $type) {

	$to_return = NULL;

	//Establish a DB connection
	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		echo "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error() . "<br />";
		return $to_return;
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		mysqli_close($connection);
		echo "Error: could not change charset to UTF-8, aborting...<br />";
		return $to_return;
	}


	//Check what kind of item we're looking for: vocab or kanji
	if($type == 0)
		$query = "SELECT kanji_meaning AS meaning FROM KanjiInfo WHERE kanji='{$value}';";
	elseif($type == 1)
		$query = "SELECT meaning FROM Vocab WHERE word='{$value}';";
	else
		echo "Invalid type, aborting... <br />";


	mysqli_real_escape_string($connection, $query);

	$result = mysqli_query($connection, $query);

	if(!$result) {
		echo mysqli_error($connection) . "<br />";
		return $to_return;
	}

	$row = mysqli_fetch_assoc($result);

	$to_return = $row['meaning'];

	mysqli_free_result($result);
	mysqli_close($connection);

	return $to_return;

}


//Will return an associative array
//If type is 0 (AKA a kanji), return the on-yomi and kun-yomi kana
//If type is 1 (AKA a vocab word), return the kana
function return_kana($value, $type) {

	$to_return = array();

	//Establish a DB connection
	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		echo "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error() . "<br />";
		return $to_return;
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		mysqli_close($connection);
		echo "Error: could not change charset to UTF-8, aborting...<br />";
		return $to_return;
	}

	//If looking for a kanji, query the on-yomi and kun-yomi tables
	if($type == 0) {
		$query = "SELECT onyomi, o_romaji FROM O_pron WHERE kanji='{$value}'";
		mysqli_real_escape_string($connection, $query);

		$result = mysqli_query($connection, $query);

		if(!$result) {
			echo mysqli_error($connection) . "<br />";
		return $to_return;
		}

		//If there are actually rows to return, process them
		if(mysqli_num_rows($result)) {
			$row = mysqli_fetch_assoc($result);

			$to_return['onyomi'] = $row['onyomi'];
			$to_return['o_romaji'] = $row['o_romaji'];

			while($row = mysqli_fetch_assoc($result)) {
				$to_return['onyomi'] .= "、" . $row['onyomi'];
				$to_return['o_romaji'] .= ", " . $row['o_romaji'];
			}

		}

		mysqli_free_result($result);


		//kunyomi
		$query = "SELECT kunyomi, k_romaji FROM K_pron WHERE kanji='{$value}'";

		mysqli_real_escape_string($connection, $query);

		$result = mysqli_query($connection, $query);

		if(!$result) {
			echo mysqli_error($connection) . "<br />";
			return $to_return;
		}

		//If there are actually rows to return, process them
		if(mysqli_num_rows($result)) {
			$row = mysqli_fetch_assoc($result);

			$to_return['kunyomi'] = $row['kunyomi'];
			$to_return['k_romaji'] = $row['k_romaji'];

			while($row = mysqli_fetch_assoc($result)) {
				$to_return['kunyomi'] .= "、" . $row['kunyomi'];
				$to_return['k_romaji'] .= ", " . $row['o_romaji'];
			}
		}

		mysqli_free_result($result);

	}


	//If looking for a vocab, only need to run one query 
	elseif($type == 1) {
		$query = "SELECT kana, romaji FROM Vocab WHERE word='{$value}';";
		mysqli_real_escape_string($connection, $query);

		$result = mysqli_query($connection, $query);

		if(!$result) {
			echo mysqli_error($connection) . "<br />";
			return $to_return;
		}

		//If there is a row to return, process it
		if(mysqli_num_rows($result)) {
			$row = mysqli_fetch_assoc($result);
			$to_return['kana'] = $row['kana'];
			$to_return['romaji'] = $row['romaji'];
		}

	}

	mysqli_close($connection);

	return $to_return;

}
	

?>
