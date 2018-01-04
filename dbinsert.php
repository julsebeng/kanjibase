<?php

//Opens a connection to the database and attempts to insert into four different tables:
	//Chara table: $kanji, $radical, $strokes, $genki_ch used
	//Meaning table: $kanji, $meaning used
	//O_pron: $kanji, $onyomi, $r_onyomi, used
	//K_pron: $kanji, $kunyomi, $r_kunyomi used
function insert_kanji($kanji, $primitive, $strokes, $genki_ch, $onyomi, $kunyomi, $r_onyomi, $r_kunyomi, $meaning) {

	//Array to hold any error messages
	$db_insert_kanji_err = array();

	//Establish a DB connection
	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		$db_insert_kanji_err["Connection_failure"] = "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
		return $db_insert_kanji_err;
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		mysqli_close($connection);
		$db_insert_kanji_err["charset_error"] = "Error: could not change charset to UTF-8, aborting...";
		return $db_insert_kanji_err;
	}


	//Construct the query for inserting into Chara
	$query = "INSERT INTO Chara VALUES('{$kanji}', '{$primitive}', {$strokes}, {$genki_ch});";
	//Sanitize the query to ensure it's formatted correctly and ready to be processed by MySQL
	mysqli_real_escape_string($connection, $query);

	//Run query
	if(!($result = mysqli_query($connection, $query))) {
		$db_insert_kanji_err["insert_chara_error"] =  mysqli_error($connection);
		mysqli_close($connection);
		return $db_insert_kanji_err;
	}


	//Construct the query for inserting into O_pron; note that $onyomi and $r_onyomi are given as an array
	for($i = 0; $i < count($onyomi); $i++) {
		$onyomi_temp = $onyomi[$i];
		$r_onyomi_temp = $r_onyomi[$i];

		//Trim strings, just in case
		$onyomi_temp = trim($onyomi_temp);
		$r_onyomi_temp = trim($r_onyomi_temp);

		$query = "INSERT INTO O_pron VALUES('{$kanji}', '{$onyomi_temp}', '{$r_onyomi_temp}');";
		//Sanitize the query to ensure it's formatted correctly and ready to be processed by MySQL
		mysqli_real_escape_string($connection, $query);

		//Run query
		if(!($result = mysqli_query($connection, $query))) {
			$db_insert_kanji_err["insert_opron_error"] =  mysqli_error($connection);
			mysqli_close($connection);
			return $db_insert_kanji_err;
		}

	}


	//Construct the query for inserting into O_pron; note that $kunyomi and $r_kunyomi are given as an array
	for($i = 0; $i < count($kunyomi); $i++) {
		$kunyomi_temp = $kunyomi[$i];
		$r_kunyomi_temp = $r_kunyomi[$i];

		//Trim strings, just in case
		$kunyomi_temp = trim($kunyomi_temp);
		$r_kunyomi_temp = trim($r_kunyomi_temp);

		$query = "INSERT INTO K_pron VALUES('{$kanji}', '{$kunyomi_temp}', '{$r_kunyomi_temp}');";
		//Sanitize the query to ensure it's formatted correctly and ready to be processed by MySQL
		mysqli_real_escape_string($connection, $query);

		//Run query
		if(!($result = mysqli_query($connection, $query))) {
			$db_insert_kanji_err["insert_kpron_error"] =  mysqli_error($connection);
			mysqli_close($connection);
			return $db_insert_kanji_err;
		}

	}


	//Insert into meaning
	$query = "INSERT INTO Meaning VALUES('{$kanji}', '{$meaning}');";
	//Sanitize the query to ensure it's formatted correctly and ready to be processed by MySQL
	mysqli_real_escape_string($connection, $query);

	//Run query
	if(!($result = mysqli_query($connection, $query))) {
		$db_insert_kanji_err["insert_meaning_error"] =  mysqli_error($connection);
		mysqli_close($connection);
		return $db_insert_kanji_err;
	}
	//Close connection
	mysqli_close($connection);

	return $db_insert_kanji_err;
}	

//Query for a particular character, and return the relevant info in an array; this is just used for testing inserted characters
//Will print out the results to the screen as well
//Note: this function assumes all input is correct, so no validation is done within
function return_kanji_info($kanji) {

	$db_return_kanji_err = array();
	$kanji_info = array("kanji" => NULL, 
					"primitive" => NULL, 
					"strokes"   => NULL, 
					"Genki_ch"  => NULL,
					"meaning"   => NULL,
					"onyomi"    => NULL,
					"r_onyomi"  => NULL,
					"kunyomi"   => NULL,
					"r_kunyomi" => NULL);


	//Establish a DB connection
	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		$db_return_kanji_err["Connection_failure"] = "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
		return $db_insert_kanji_err;
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		mysqli_close($connection);
		$db_return_kanji_err["charset_error"] = "Error: could not change charset to UTF-8, aborting...";
		return $db_return_kanji_err;
	}

	//Make query; note that KanjiInfo is a view that has already been created via MySQL
	$query = "SELECT * ";
	$query .= "FROM KanjiInfo ";
	$query .= "WHERE kanji='{$kanji}';";
	mysqli_real_escape_string($connection, $query);

	//Run query
	$result = mysqli_query($connection, $query);

	//Check to see if query ran
	if(!$result) {
		//If query failed, exit and return error info
		$db_return_kanji_err["retrieval_error"] = mysqli_error($connection);
		mysqli_close($connection);
		return $db_return_kanji_err;
	}

	mysqli_free_result($result);
	mysqli_close($connection);
	return $db_return_kanji_err;

}


//This function will insert vocabulary words provided to it in $vocab (as a normal array) into the table Vocab
//It will then call a secondary function to insert into the table contains_radicals with the specified kanji
//Note: fields are indexed in this order:
	//[0] => the word itself
	//[1] => kana
	//[2] => romaji
	//[3] => meaning
	//[4] => genki chapter
//(an associative array was not used so that elements could still be looped through with incrementing integers)
function insert_vocab($kanji, $vocab_array) {
	$errormsg = array();

	//Establish a DB connection
	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		$errormsg["Connection_failure"] = "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
		return $errormsg;
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		mysqli_close($connection);
		$errormsg["charset_error"] = "Error: could not change charset to UTF-8, aborting...";
		return $errormsg;
	}

	//Loop through each vocab word, searching the DB first to make sure the word doesn't already exist, then insert it
	$count = count($vocab_array);
	for ($i=0; $i < $count; $i++) { 
		
		$query  = "SELECT Vocab.word ";
		$query .= "FROM Vocab ";
		$query .= "WHERE Vocab.word='{$vocab_array[$i][0]}';";
		mysqli_real_escape_string($connection, $query);

		$result = mysqli_query($connection, $query);

		if(!$result) {
			//If query failed, exit and return error info
			$errormsg["vocab_insert_error"] = mysqli_error($connection);
			mysqli_close($connection);
			return $errormsg;
		}
		//If no rows returned, insert into table Vocab
		if(!mysqli_num_rows($result)) {
			$query  = "INSERT INTO Vocab VALUES(";
			$query .= "'{$vocab_array[$i][0]}', "; //The word
			$query .= "'{$vocab_array[$i][1]}', "; //The kana
			$query .= "'{$vocab_array[$i][3]}', "; //The meaning
			$query .= "'{$vocab_array[$i][4]}', "; //The Genki chapter
			$query .= "'{$vocab_array[$i][2]}');"; //The romaji

			mysqli_real_escape_string($connection, $query);

			//Free the previous result
			mysqli_free_result($result);

			$result = mysqli_query($connection, $query);
			if(!$result) {
				//If query failed, exit and return error info
				$errormsg["vocab_insert_error"] = mysqli_error($connection);
				mysqli_close($connection);
				return $errormsg;
			}
			else
				echo "Successfully inserted into table Vocab: {$query}.<br />";
		}


		//Insert into table is_in_vocab
		$query  = "INSERT INTO is_in_vocab VALUES(";
		$query .= "'{$kanji}', ";
		$query .= "'{$vocab_array[$i][0]}');";
		mysqli_real_escape_string($connection, $query);

		//Run query for is_in_vocab
		$result = mysqli_query($connection, $query);
			if(!$result) {
				//If query failed, exit and return error info
				$errormsg["vocab_insert_error"] = mysqli_error($connection);
				mysqli_close($connection);
				return $errormsg;
			}
			else
				echo "Successfully inserted into table is_in_vocab: {$query}.";
	}

	mysqli_close($connection);
	return $errormsg;

}


//This function will add a character to the radical table
function insert_radical($kanji, $meaning, $is_primitive) {
	$db_insert_rad_err = array();

	//Establish a DB connection
	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		echo "Connection error.<br />";
		$db_insert_rad_err["Connection_failure"] = "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
		return $db_insert_rad_err;
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		echo "Charset error. <br />";
		mysqli_close($connection);
		$db_insert_rad_err["charset_error"] = "Error: could not change charset to UTF-8, aborting...";
		return $db_insert_rad_err;
	}

	//Create query
	$query = "INSERT INTO Radical VALUES('{$kanji}', '{$is_primitive}', '{$meaning}');";

	//Run query
	$result = mysqli_query($connection, $query);

	//Check to see if query ran
	if(!$result) {
		//If query failed, exit and return error info
		$db_insert_rad_err["retrieval_error"] = mysqli_error($connection);
		mysqli_close($connection);
		return $db_insert_rad_err;
	}

	mysqli_close($connection);
	return $db_insert_rad_err;
}


//Queries the DB to make sure certain radicals exist
function check_rads_exist($rad) {
$errormsg = array();

	//Establish a DB connection
	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		echo "Connection error.<br />";
		$errormsg["Connection_failure"] = "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
		return $errormsg;
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		echo "Charset error. <br />";
		mysqli_close($connection);
		$errormsg["charset_error"] = "Error: could not change charset to UTF-8, aborting...";
		return $errormsg;
	}

	//Loop through array and find any radicals that don't exist
	foreach ($rad as $value) {
		$query = "SELECT * ";
		$query .= "FROM Radical ";
		$query .= "WHERE radical='{$value}';";
		mysqli_real_escape_string($connection, $query);

		//Run query
		$result = mysqli_query($connection, $query);

		if(!$result) {
			//If query failed, exit and return error info
			$db_insert_rad_err["rads_retrieval_error"] = mysqli_error($connection);
			mysqli_close($connection);
			return $db_insert_rad_err;
		}
		//If no rows returned, make note of it in another array
		if(!mysqli_num_rows($result)) 
			$errormsg["missing_rad_{$value}"] = "Radical {$value} doesn't exist in table Radical.";

		//Free result
		mysqli_free_result($result);
	}


	mysqli_close($connection);
	return $errormsg;

}


//This function will attept to insert all radicals given for a certain kanji into the contains_radicals table
//If the kanji doesn't exist in the Radicals table, return the radicals back as an error
function pair_radical($kanji, $rad) {
	$db_insert_rad_err = array();

	//Establish a DB connection
	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		echo "Connection error.<br />";
		$db_insert_rad_err["Connection_failure"] = "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
		return $db_insert_rad_err;
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		echo "Charset error. <br />";
		mysqli_close($connection);
		$db_insert_rad_err["charset_error"] = "Error: could not change charset to UTF-8, aborting...";
		return $db_insert_rad_err;
	}

	//First, check to make sure a radical exists; if not, make a note of it and redirect
	foreach ($rad as $value) {
	
			$query = "INSERT INTO contains_radicals VALUES(\"{$kanji}\", \"{$value}\");";
			mysqli_real_escape_string($connection, $query);

			if(!($rad_insert_result = mysqli_query($connection, $query))) {
				$db_insert_kanji_err["insert_contains_radicals_error"] =  mysqli_error($connection);
				mysqli_close($connection);
				return $db_insert_kanji_err;
			}
		}	

	mysqli_close($connection);
	return $db_insert_rad_err;
}


//Delete $value from $table.$attr
//note that deletions to the Chara table will cascade to all other fields but WILL NOT IMPACT THE Vocab TABLE DIRECTLY
//Meaning that vocabulary words who are keys for the table is_in_vocab will still exist even if no entries in that table reference it
//However, there is currently a trigger in place in the DB that, after deletion from table is_in_vocab, will check for these orphaned vocab words
function delete_tuple($table, $attr, $value) {

	$errormsg = array();

	//Establish a DB connection
	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		echo "Connection error.<br />";
		$errormsg["Connection_failure"] = "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
		return $errormsg;
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		echo "Charset error. <br />";
		mysqli_close($connection);
		$errormsg["charset_error"] = "Error: could not change charset to UTF-8, aborting...";
		return $errormsg;
	}

	//Create deletion query
	$query = "DELETE FROM {$table} WHERE {$attr}='{$value}';";
	mysqli_real_escape_string($connection, $query);

	$result = mysqli_query($connection, $query);

	if(!$result) {
		//If query failed, exit and return error info
		$errormsg["{$table}.{$attr}_deletion_error"] = mysqli_error($connection);
		mysqli_close($connection);
		return $errormsg;
	}

	mysqli_close($connection);

	return $errormsg;
}

//This function deletes any vocab words connected to a particular kanji
//because of triggers set in place in MySQL, the only deletions that need to be made are in is_in_vocab - any changes will cascade to Vocab
function wipe_vocab($kanji) {

	$errormsg = array();

	//Establish a DB connection
	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		echo "Connection error.<br />";
		$errormsg["Connection_failure"] = "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
		return $errormsg;
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		echo "Charset error. <br />";
		mysqli_close($connection);
		$errormsg["charset_error"] = "Error: could not change charset to UTF-8, aborting...";
		return $errormsg;
	}

	$query = "DELETE FROM is_in_vocab WHERE kanji='{$kanji}';"; 
	mysqli_real_escape_string($connection, $query);

	$result = mysqli_query($connection, $query);
	if(!$result) {
		//If query failed, exit and return error info
		$errormsg["{$table}.{$attr}_deletion_error"] = mysqli_error($connection);
		mysqli_close($connection);
		return $errormsg;
	}
	 mysqli_close($connection);

	 return $errormsg;

}
?>