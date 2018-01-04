<?php 
session_start();

//If results are already being stored, wipe them for the new results
if(isset($_SESSION['results']))
	unset($_SESSION['results']);
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Search Kanjibase</title>
	<link href="searchresults.css" rel="stylesheet" type="text/css">
</head>
<body>
	<h1>Results</h1>
	<a href="flashcard_processing.php">Study these cards</a>
	<?php
	//Require for the print_errors function
	require_once("form_validation.php");

	//Open up a DB connection
	$errormsg = array();

	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		echo "Connection error.<br />";
		$errormsg["Connection_failure"] = "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		echo "Charset error. <br />";
		mysqli_close($connection);
		$errormsg["charset_error"] = "Error: could not change charset to UTF-8, aborting...";
	}

	//Check if a modifier is set; 1 means sort asc and 2 means sort desc
	//If none given, default to asc
	//This is relevant to the chapter and radical searches
	if(isset($_GET['mod'])) {
		if($_GET['mod'] == 1)
			$mod = 1;
		else 
			$mod = 2;
	}
	else 
		$mod = 1;


//--------------------------------------------------------------------------------------------------------------------
//Chapter Query
//--------------------------------------------------------------------------------------------------------------------
	if(isset($_GET['ch'])) {
		$ch = $_GET['ch'];
		$kanji_return = TRUE;
		$vocab_return = TRUE;

		$query = "SELECT kanji, kanji_meaning FROM KanjiInfo WHERE genki_ch={$ch} GROUP BY kanji ";
		//If the user specifies, sort by asc or desc
		if($mod == 2)
			$query .= "ORDER BY strokes DESC;";
		else
			$query .= "ORDER BY strokes ASC;";

		mysqli_real_escape_string($connection, $query);

		//Run query
		$ch_result = mysqli_query($connection, $query);

		//Check for any errors in the query
		if(!$ch_result) {
			echo "Query error: " . mysqli_error($connection);
			mysqli_close($connection);
		}
		else {

			//If no rows returned, make note of it
			if(!mysqli_num_rows($ch_result))
				$kanji_return = FALSE;
			else {

				while($row = mysqli_fetch_assoc($ch_result)) {
					$_SESSION['results'][] = $row['kanji']; 
					echo "<div class=\"KanjiResult\">";
					echo "<h3><a href=\"info.php?kanji={$row['kanji']}\">{$row['kanji']}</a></h3>";
					echo "<p>{$row['kanji_meaning']}</p>";
					echo "</div>";
				}	

			}

		}

		mysqli_free_result($ch_result);

		//Print any vocab from that chapter as well
		$query = "SELECT Vocab.word, Vocab.meaning FROM Vocab WHERE genki_ch={$ch};";

		mysqli_real_escape_string($connection, $query);

		//Run query
		$ch_result = mysqli_query($connection, $query);

		//Check for any errors in the query
		if(!$ch_result) {
			echo "Query error: " . mysqli_error($connection);
			mysqli_close($connection);
		}
		else {

			//If no rows returned, make note of it
			if(!mysqli_num_rows($ch_result))
				$vocab_return = FALSE;
			else {

				while($row = mysqli_fetch_assoc($ch_result)) {
					$_SESSION['results'][] = $row['word']; 
					echo "<div class=\"VocabResult\">";
					echo "<h3><a href=\"info.php?word={$row['word']}\">{$row['word']}</a></h3>";
					echo "<p>{$row['meaning']}</p>";
					echo "</div>";
				}	

			}

			//If no results returned, print out message
			if(!$kanji_return && !$vocab_return) 
				echo "<h2>No results found.</h2>";

		}
		
	}


//--------------------------------------------------------------------------------------------------------------------
//Strokes Query
//--------------------------------------------------------------------------------------------------------------------
	elseif(isset($_GET['str'])) {

		$str = $_GET['str'];
		
		$query = "SELECT kanji, kanji_meaning FROM KanjiInfo WHERE strokes='{$str}' GROUP BY kanji;";
		mysqli_real_escape_string($connection, $query);

		//Run query 

		$str_result = mysqli_query($connection, $query);

				//Check for any errors in the query
		if(!$str_result) {
			echo "Query error: " . mysqli_error($connection);
			mysqli_close($connection);
		}
		else {

			//If no rows returned, print message
			if(!mysqli_num_rows($str_result))
				echo "<h2>No results found.</h2>";
			else {

				while($row = mysqli_fetch_assoc($str_result)) {
					$_SESSION['results'][] = $row['kanji']; 
					echo "<div class=\"KanjiResult\">";
					echo "<h3><a href=\"info.php?kanji={$row['kanji']}\">{$row['kanji']}</a></h3>";
					echo "<p>{$row['kanji_meaning']}</p>";
					echo "</div>";
				}	

			}

		}

		mysqli_free_result($str_result);

	}


//--------------------------------------------------------------------------------------------------------------------
//Radicals Query
//--------------------------------------------------------------------------------------------------------------------
	elseif(isset($_GET['rad'])) {
		$rad = $_GET['rad'];
		
		$query = "SELECT Chara.kanji, Chara.strokes, Meaning.kanji_meaning FROM contains_radicals INNER JOIN Chara ON Chara.kanji = contains_radicals.kanji INNER JOIN Meaning ON Chara.kanji = Meaning.kanji WHERE contains_radicals.radical='{$rad}' GROUP BY Chara.kanji ";
		mysqli_real_escape_string($connection, $query);

		if($mod == 2)
			$query .= "ORDER BY strokes DESC;";
		else
			$query .= "ORDER BY strokes ASC;";

		mysqli_real_escape_string($connection, $query);

		//Run query
		$rad_result = mysqli_query($connection, $query);

		//Check for any errors in the query
		if(!$rad_result) {
			echo "Query error: " . mysqli_error($connection);
			mysqli_close($connection);
		}
		else {

			//If no rows returned, print message
			if(!mysqli_num_rows($rad_result))
				echo "<h2>No results found.</h2>";
			else {

				while($row = mysqli_fetch_assoc($rad_result)) {
					$_SESSION['results'][] = $row['kanji']; 
					echo "<div class=\"KanjiResult\">";
					echo "<h3><a href=\"info.php?kanji={$row['kanji']}\">{$row['kanji']}</a></h3>";
					echo "<p>{$row['kanji_meaning']}</p>";
					echo "</div>";
				}	

			}

		}

		mysqli_free_result($rad_result);
		

	}


//--------------------------------------------------------------------------------------------------------------------
//Normal Query
//--------------------------------------------------------------------------------------------------------------------
	elseif(isset($_GET['search'])) {
		$search = $_GET['search'];
		$kanji_return = TRUE;
		$vocab_return = TRUE;

		//Scan each column of the KanjiInfo table for matches
		$query = "SELECT kanji, kanji_meaning FROM KanjiInfo WHERE kanji='{$search}' OR kanji_meaning='{$search}' OR kunyomi='{$search}' OR k_romaji='{$search}' OR onyomi='{$search}' OR o_romaji='{$search}' GROUP BY kanji;";
		mysqli_real_escape_string($connection, $query);

		//Run query 
		$kanji_result = mysqli_query($connection, $query);

		if(!$kanji_result) {
			//If query failed, exit and return error info
			echo "Query error: " . mysqli_error($connection);
			mysqli_close($connection);
		}
		else {

			//If nothing returned, set a flag
			if(!mysqli_num_rows($kanji_result))
				$kanji_return = FALSE;
			//Otherwise, print out each kanji found
			else {
				while($row = mysqli_fetch_assoc($kanji_result)) {
					$_SESSION['results'][] = $row['kanji']; 
					echo "<div class=\"KanjiResult\">";
					echo "<h3><a href=\"info.php?kanji={$row['kanji']}\">{$row['kanji']}</a></h3>";
					echo "<p>{$row['kanji_meaning']}</p>";
					echo "</div>";

				}

			}
		}

		mysqli_free_result($kanji_result);
		//Next, search through the vocab table and print results
		$query = "SELECT word, meaning FROM Vocab WHERE word LIKE '%{$search}%' OR meaning LIKE '%{$search}%' OR kana LIKE '%{$search}%' OR romaji LIKE '%{$search}%' GROUP BY word;";
		mysqli_real_escape_string($connection, $query);

		$vocab_result = mysqli_query($connection, $query);

		if(!$vocab_result) {
			//If query failed, exit and return error info
			echo "Query error: " . mysqli_error($connection);
			mysqli_close($connection);
		}
		else {
			if(!mysqli_num_rows($vocab_result))
				$vocab_return = FALSE;
			else {
				while($row = mysqli_fetch_assoc($vocab_result)) {
					$_SESSION['results'][] = $row['word']; 
					echo "<div class=\"VocabResult\">";
					echo "<h3><a href=\"info.php?word={$row['word']}\">{$row['word']}</a></h3>";
					echo "<p>{$row['meaning']}</p>";
					echo "</div>";
				}	
			}
		}

		//If no results returned, print out message
		if(!$kanji_return && !$vocab_return) 
			echo "<h2>No results found.</h2>";

		mysqli_free_result($vocab_result);		
	}

	mysqli_close($connection);

	if(!empty($errormsg))
		print_errors($errormsg);

?>
<p><a href="search_form.php">Return</a></p>
	
</body>