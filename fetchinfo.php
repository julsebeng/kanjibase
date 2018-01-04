<?php
//Fetches all relevant information on a kanji and returns it as an associative array
function fetch_kanji_info($kanji) {

	$return = array();

	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		$return["error"] = "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
		return $return;
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		$errormsg["error"] = "Error: could not change charset to UTF-8, aborting...";
		return $return;
	}	

	//First, search for the kanji to make sure it even exists
	$query = "SELECT Chara.primitive, Chara.strokes, Chara.genki_ch, Meaning.kanji_meaning FROM Chara INNER JOIN Meaning ON Chara.kanji = Meaning.kanji WHERE Chara.kanji='{$kanji}' GROUP BY Chara.kanji;";
	mysqli_real_escape_string($connection, $query);

	$chara_return = mysqli_query($connection, $query);
	if(!$chara_return) {
		$return["error"] = "Query error: " . mysqli_error($connection);
		return $return;
	}
	//If no rows returned, return an error
	elseif(!mysqli_num_rows($chara_return)) {
		echo mysqli_num_rows($chara_return);
		$return["error"] = "Error in fetch_kanji_info: kanji doesn't exist in database, aborting...";
		return $return;
	}

	//Populate info from Chara table
	$return["kanji"] = $kanji;
	$row = mysqli_fetch_assoc($chara_return);

	$return["primitive"] = $row["primitive"];
	$return["strokes"]   = $row["strokes"];
	$return["meaning"]   = $row["kanji_meaning"];
	$return["genki_ch"]  = $row["genki_ch"];

	mysqli_free_result($chara_return);


	//Next, pull info from the onyomi and kunyomi tables
	$query = "SELECT onyomi, o_romaji FROM O_pron WHERE kanji='{$kanji}';";
	mysqli_real_escape_string($connection, $query);

	$o_pron_return = mysqli_query($connection, $query);
	if(!$o_pron_return) {
		$return["error"] = "Query error: " . mysqli_error($connection);
		return $return;
	}
	//If some rows returned, process them, else set onyomi and r_onyomi to NULL
	if(mysqli_num_rows($o_pron_return)) {
		$row = mysqli_fetch_assoc($o_pron_return);

		$return['onyomi'] = $row['onyomi'];
		$return['r_onyomi'] = $row['o_romaji'];

		while($row = mysqli_fetch_assoc($o_pron_return)) {
			$return['onyomi'] .= "、" . $row['onyomi'];
			$return['r_onyomi'] .= "," . $row['o_romaji'];
		}
	}
	else {
		$return['onyomi'] = NULL;
		$return['r_onyomi'] = NULL;
	}

	mysqli_free_result($o_pron_return);


	//kunyomi
	$query = "SELECT kunyomi, k_romaji FROM K_pron WHERE kanji='{$kanji}';";
	mysqli_real_escape_string($connection, $query);

	$k_pron_return = mysqli_query($connection, $query);
	if(!$k_pron_return) {
		$return["error"] = "Query error: " . mysqli_error($connection);
		return $return;
	}
	//If some rows returned, process them, else set onyomi and r_onyomi to NULL
	if(mysqli_num_rows($k_pron_return)) {
		$row = mysqli_fetch_assoc($k_pron_return);

		$return['kunyomi'] = $row['kunyomi'];
		$return['r_kunyomi'] = $row['k_romaji'];

		while($row = mysqli_fetch_assoc($k_pron_return)) {
			$return['kunyomi'] .= "、" . $row['kunyomi'];
			$return['r_kunyomi'] .= "," . $row['k_romaji'];
		}
	}
	else {
		$return['kunyomi'] = NULL;
		$return['r_kunyomi'] = NULL;
	}

	mysqli_free_result($k_pron_return);


	//Next, go through contains_radicals and find any radicals associated with the kanji
	$query = "SELECT radical FROM contains_radicals WHERE kanji='{$kanji}';";
	mysqli_real_escape_string($connection, $query);

	$contains_radicals_return = mysqli_query($connection, $query);
	if(!$contains_radicals_return) {
		$return["error"] = "Query error: " . mysqli_error($connection);
		return $return;
	}

	//Process returned rows
	if(mysqli_num_rows($contains_radicals_return)) {
		$row = mysqli_fetch_assoc($contains_radicals_return);

		$return['radicals'] = $row['radical'];

		while($row = mysqli_fetch_assoc($contains_radicals_return))
			$return['radicals'] .= "、" . $row['radical'];
	
	}
	else
		$return['radicals'] = NULL;

	mysqli_free_result($contains_radicals_return);

	//Next, check to see if the kanji is also a radical, and if so populate the correct information
	$query = "SELECT * FROM Radical WHERE radical='{$kanji}' LIMIT 1;";
	mysqli_real_escape_string($connection, $query);

	$radical_return = mysqli_query($connection, $query);
	if(!$radical_return) {
		echo "error";
		$return["error"] = "Query error: " . mysqli_error($connection);
		return $return;
	}

	//If some row returned, populate the data
	if(mysqli_num_rows($radical_return)) {
		$row = mysqli_fetch_assoc($radical_return);
		$return['rad_meaning'] = $row['rad_meaning'];
		$return['is_primitive'] = $row['is_primitive'];
	}
	else {
		$return['rad_meaning'] = NULL;
		$return['is_primitive'] = NULL;
	}

	mysqli_free_result($radical_return);


	//Finally, dig up any associated vocab words and put them into a semicolon-separated list
	$query = "SELECT Vocab.word, Vocab.kana, Vocab.romaji, Vocab.genki_ch, Vocab.meaning FROM is_in_vocab INNER JOIN Vocab ON is_in_vocab.word = Vocab.word WHERE is_in_vocab.kanji='{$kanji}';";
	mysqli_real_escape_string($connection, $query);

	$vocab_return = mysqli_query($connection, $query);
	if(!$vocab_return) {
		$return["error"] = "Query error: " . mysqli_error($connection);
		return $return;
	}

	//If some row returned, populate the data
	if(mysqli_num_rows($vocab_return)) {

		$row = mysqli_fetch_assoc($vocab_return);

		$return['vocab_words'] = $row['word'];
		$return['vocab_kana'] = $row['kana'];
		$return['vocab_romaji'] = $row['romaji'];
		$return['vocab_meaning'] = $row['meaning'];
		$return['vocab_ch'] = $row['genki_ch'];

		while($row = mysqli_fetch_assoc($vocab_return)) {
			$return['vocab_words'] .= "；" . $row['word'];
			$return['vocab_kana'] .= "；" . $row['kana'];
			$return['vocab_romaji'] .= ";" . $row['romaji'];
			$return['vocab_meaning'] .= ";" . $row['meaning'];
			$return['vocab_ch'] .= ";" . $row['genki_ch'];
		}
	}
	else {
		$return['vocab_words'] = NULL;
		$return['vocab_kana'] = NULL;
		$return['vocab_romaji'] = NULL;
		$return['vocab_meaning'] = NULL;
		$return['vocab_ch'] = NULL;
	}

	mysqli_free_result($vocab_return);

	return $return;

}

?>