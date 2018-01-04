<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Search Kanjibase</title>
	<link href="info.css" rel="stylesheet" type="text/css">
</head>
<body>
<?php

	//Require for the print_errors function
	require_once("form_validation.php");


//--------------------------------------------------------------------------------------------------------------------
//Printing Kanji Info
//--------------------------------------------------------------------------------------------------------------------
	if(isset($_GET['kanji'])) {
		$kanji = $_GET['kanji'];

		//Query tables separately for info on the kanji. the view KanjiInfo isn't used because kanji can have varying 
		//numbers of pronunciations and radicals, so those tables are to be queried separately
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

		//Query Chara
		//--------------------------------------------------------------
		$query = "SELECT * FROM Chara WHERE kanji='{$kanji}' LIMIT 1;";
		mysqli_real_escape_string($connection, $query);

		$chara_result = mysqli_query($connection, $query);
		if(!$chara_result) {
			echo "Query error: " . mysqli_error($connection);
			mysqli_close($connection);
		}
		//If for whatever reason, no rows returned, stop trying and print an error; this should never happen
		elseif(!mysqli_num_rows($chara_result)) {
			echo "Kanji doesn't exist, something went wrong. <br />";
			mysqli_close($connection);
		}
		//Otherwise, continue with the printing of info
		else {
			$row = mysqli_fetch_assoc($chara_result);
			echo "<div class=\"CharaResult\">";
			echo "<h2>{$row['kanji']}</h2>";
			echo "<p>Primitive: {$row['primitive']}</p>";
			echo "<p>Strokes: {$row['strokes']}</p>";
			echo "<p>From chapter {$row['genki_ch']}</p>";
			echo "</div>";

			mysqli_free_result($chara_result);

		//Query Meaning
		//--------------------------------------------------------------
			$query = "SELECT kanji_meaning FROM Meaning WHERE kanji='{$kanji}' LIMIT 1;";
			mysqli_real_escape_string($connection, $query);

			$meaning_result = mysqli_query($connection, $query);
			if(!$meaning_result) {
				echo "Query error: " . mysqli_error($connection);
				mysqli_close($connection);
			}
			//If for whatever reason, no rows returned, stop trying and print an error; this should never happen
			elseif(!mysqli_num_rows($meaning_result)) {
				echo "Kanji doesn't exist, something went wrong. <br />";
				mysqli_close($connection);
			}
			//Otherwise, continue with the printing of info
			else {
				$row = mysqli_fetch_assoc($meaning_result);
				echo "<div class=\"MeaningResult\">";
				echo "<p>Meaning: {$row['kanji_meaning']}</p>";
				echo "</div>";

				mysqli_free_result($meaning_result);

				//Query O_pron
				//--------------------------------------------------------------
				$query = "SELECT onyomi, o_romaji FROM O_pron WHERE kanji='{$kanji}';";
				mysqli_real_escape_string($connection, $query);

				$o_pron_result = mysqli_query($connection, $query);
				if(!$o_pron_result) {
					echo "Query error: " . mysqli_error($connection);
					mysqli_close($connection);
				}
				//continue with the printing of info
				else {
					echo "<div class=\"O_pronResult\">";
					echo "<h3>Onyomi: </h3>";
					while($row = mysqli_fetch_assoc($o_pron_result)) {
						echo "<p>{$row['onyomi']} ({$row['o_romaji']})</p>";
					}
					echo "</div>";
					mysqli_free_result($o_pron_result);

					//Query K_pron
					//--------------------------------------------------------------
					$query = "SELECT kunyomi, k_romaji FROM K_pron WHERE kanji='{$kanji}';";
					mysqli_real_escape_string($connection, $query);

					$k_pron_result = mysqli_query($connection, $query);
					if(!$k_pron_result) {
						echo "Query error: " . mysqli_error($connection);
						mysqli_close($connection);
					}
					//continue with the printing of info
					else {
						echo "<div class=\"K_pronResult\">";
						echo "<h3>Kunyomi: </h3>";
						while($row = mysqli_fetch_assoc($k_pron_result)) {
							echo "<p>{$row['kunyomi']} ({$row['k_romaji']})</p>";
						}
						echo "</div>";
						mysqli_free_result($k_pron_result);

						//Query RadicalInfo
						//--------------------------------------------------------------
						$query = "SELECT radical, rad_meaning FROM RadicalInfo WHERE kanji='{$kanji}';";
						mysqli_real_escape_string($connection, $query);

						$radicalinfo_result = mysqli_query($connection, $query);
						if(!$radicalinfo_result) {
							echo "Query error: " . mysqli_error($connection);
							mysqli_close($connection);
						}
						//continue with the printing of info
						else {
							echo "<div class=\"RadicalInfoResult\">";
							echo "<h3>Radicals: </h3><p>";

							//Print out radical info
							$row = mysqli_fetch_assoc($radicalinfo_result);
							echo "<a href=\"search_results.php?rad={$row['radical']}\">" . ucwords($row['rad_meaning']) . " {$row['radical']} </a>";
							while($row = mysqli_fetch_assoc($radicalinfo_result)) {
								echo "... <a href=\"search_results.php?rad={$row['radical']}\">" . ucwords($row['rad_meaning']) . " {$row['radical']}</a>";
							}
							
							echo "</p></div>";
							mysqli_free_result($radicalinfo_result);

							//Query Radicals to see if current kanji is also a radical
							//--------------------------------------------------------------
							$query = "SELECT is_primitive, rad_meaning FROM Radical WHERE radical='{$kanji}' LIMIT 1;";
							mysqli_real_escape_string($connection, $query);

							$radical_result = mysqli_query($connection, $query);
							if(!$radical_result) {
								echo "Query error: " . mysqli_error($connection);
								mysqli_close($connection);
							}
							//If rows returned, print info
							elseif(mysqli_num_rows($radical_result)) {
								$row = mysqli_fetch_assoc($radical_result);
								echo "<div class=\"RadicalResult\"><h3>As a radical, this character represents {$row['rad_meaning']}.</h3>";
								if($row['is_primitive'] == "Y")
									echo "<p>This character is also a primitive.</p>";
								echo "</div>";
							}

							mysqli_free_result($radical_result);

						}


					}

				}

			}

		}

		mysqli_close($connection);
	}	


//--------------------------------------------------------------------------------------------------------------------
//Printing Vocab Info
//--------------------------------------------------------------------------------------------------------------------
	elseif(isset($_GET['word'])) {
		$word = $_GET['word'];
		$errormsg = array();

		//If we get a word, search for its meaning and also return any related kanji 
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

		//Query Vocab
		//--------------------------------------------------------------
		$query = "SELECT * FROM Vocab WHERE word='{$word}' LIMIT 1;";
		mysqli_real_escape_string($connection, $query);

		$vocab_result = mysqli_query($connection, $query);

		if(!$vocab_result) {
			echo "Query error: " . mysqli_error($connection);
			mysqli_close($connection);
		}
		//If for whatever reason, no rows returned, stop trying and print an error; this should never happen
		elseif(!mysqli_num_rows($vocab_result)) {
			echo "Word doesn't exist, something went wrong. <br />";
			mysqli_close($connection);
		}
		else {

			//Print Vocab info
			$row = mysqli_fetch_assoc($vocab_result);
			echo "<h2>{$row['word']}</h2>";
			echo "<p>{$row['kana']} ({$row['romaji']})</p>";
			echo "<p>Meaning: " . ucfirst($row['meaning']) . "</p>";
			echo "<p>From chapter {$row['genki_ch']}</p>";

			mysqli_free_result($vocab_result);

			//Query is_in_vocab
			//--------------------------------------------------------------
			$query = "SELECT kanji FROM is_in_vocab WHERE word='{$word}';";
			mysqli_real_escape_string($connection, $query);

			$is_in_vocab_result = mysqli_query($connection, $query);

			if(!$is_in_vocab_result) {
				echo "Query error: " . mysqli_error($connection);
				mysqli_close($connection);
			}
			//If there are actually results, print them
			elseif(mysqli_num_rows($is_in_vocab_result)) {
				echo "<div id=\"RelatedKanji\">";
				echo "<h3>Related Kanji: </h3>";
				echo "<ul>";
				while($row = mysqli_fetch_assoc($is_in_vocab_result)) 
					echo "<li><a href=\"search_results.php?search={$row['kanji']}\">{$row['kanji']}</a></li>";
				echo "</ul></div>";
				
			}

		}

		mysqli_close($connection);

	}


//--------------------------------------------------------------------------------------------------------------------
//Catch-all
//--------------------------------------------------------------------------------------------------------------------
	else
		//If this page is loaded without anything in $_GET, redirect to the search form
		header("Location: " . "search_form.php");

	if(!empty($errormsg))
		print_errors($errormsg);
?>

</body>