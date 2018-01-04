<?php
	require_once("header.php");
	require_once("form_validation.php");
	require_once("dbinsert.php");
	require_once("fetchinfo.php");

	//Define all variables to be used in POST request
	//If this page is being redirected to from an insert form, populate info with actual kanji information
	if(isset($_GET['update'])) {
		$kanji = $_GET['update'];
		$to_modify = fetch_kanji_info($kanji);

		if(isset($to_modify['error'])) {
			echo $to_modify['error'] . "<br />";
		}
		else {
			$kanji = $to_modify['kanji'];
			$primitive = $to_modify['primitive'];
			$radicals = $to_modify['radicals'];
			$onyomi = $to_modify['onyomi'];
			$kunyomi = $to_modify['kunyomi'];
			$r_onyomi = $to_modify['r_onyomi'];
			$r_kunyomi = $to_modify['r_kunyomi'];
			$meaning = $to_modify['meaning'];
			$strokes = $to_modify['strokes'];
			$genki_ch = $to_modify['genki_ch'];

			$rad_meaning = $to_modify['rad_meaning'];
			$is_primitive = $to_modify['is_primitive'];

			$vocab_words = $to_modify['vocab_words'];
			$vocab_kana = $to_modify['vocab_kana'];
			$vocab_romaji = $to_modify['vocab_romaji'];
			$vocab_meaning = $to_modify['vocab_meaning'];
			$vocab_ch = $to_modify['vocab_ch'];

			$modify = TRUE;

		}

}
else {
	$kanji = NULL;
	$primitive = NULL;
	$radicals = NULL;
	$onyomi = NULL;
	$kunyomi = NULL;
	$r_onyomi = NULL;
	$r_kunyomi = NULL;
	$meaning = NULL;
	$strokes = NULL;
	$genki_ch = NULL;
	$no_genki_ch = NULL;

	$rad_meaning = NULL;
	$is_primitive = NULL;

	$rad_insert = TRUE;

	$vocab_words = NULL;
	$vocab_kana = NULL;
	$vocab_romaji = NULL;
	$vocab_meaning = NULL;
	$vocab_ch = NULL;

	if(!isset($_POST['modify']))
		$modify = FALSE;
	else
		$modify = TRUE;
	}

	if (isset($_POST['submit'])) { //if form was submitted

		$errormsg = array();

		//Check if the kanji field is valid
		switch(valid_char("kanji")) {
			case 0:	
				$errormsg["kanjierror"] = "Too many characters in <b>Kanji</b> field. You typed \"{$kanji}\".";
				break;
			case 1:
				$kanji = $_POST['kanji'];
				break;
			case 2:
				$errormsg["kanjierror"] = "<b>Kanji</b> field cannot be blank.";
				break;
			default:
				$errormsg["kanjierror"] = "Something unexpected went wrong while verifying the <b>Kanji</b> field. <br />";
		}	


		//Verify the primitive field
		switch(valid_char("primitive")) {
			case 0:
				$errormsg["primitiveerror"] = "Too many characters in <b>Primitive</b> field. You typed \"{$primitive}\".";
				break;
			case 1:
				$primitive = $_POST['primitive'];
				break;
			case 2:
				$errormsg["primitiveerror"] = "<b>Primitive</b> field cannot be blank. <br />";
				break;
			default:
				$errormsg["primitiveerror"] = "Something unexpected went wrong while verifying the <b>Primitive</b> field. <br />";
		}


		//Verify the radical field; this is expected to be a comma-separated list
		//All radicals should exist in the kanji database already; if not, redirect to a different form to process the radical(s)
		//If no radicals are given, that's fine
		if(!empty($_POST['radicals'])) {

			$radicals = $_POST['radicals'];
			$radicals_array = explode("、", $radicals);

			//Trim any whitespace, and make sure each radical is only one char long
			$rad_sentry = true;
			foreach ($radicals_array as $rad) {
				$rad = trim($rad);
				if (mb_strlen($rad, "UTF-8") != 1) {
					$errormsg["{$rad}error"] = "Radical must be one character long.";
				}

			}

			//Finally, just to be safe, make sure there's no more than 5 entries in this array
			if(count($radicals_array) > 5)
				$errormsg['raderror'] = "Too many entries in <b>Associated Radicals</b>, a maximum of 5 is allowed.";

		}


		//Verify the strokes field; there is no commonly used kanji with more than 29 strokes
		//Obviously, we want it to be an int as well
		if(is_numeric($_POST['strokes'])) { //Also ensures the value isn't empty
			if($_POST['strokes'] > 29 || $_POST['strokes'] <= 0)
				$errormsg["strokeerror"] = "Invalid <b>Stroke</b> count, must be greater than 0 but less than 29. <br />";
			else
				$strokes = (int) $_POST['strokes'];
		}
		else
			$errormsg["strokeerror"] = "<b>Stroke</b> count must be numeric. <br />";


		//Verify the genki_ch field - there are a total of 23 genki chapters, but some kanji included don't belong in any
		//NULL or empty is an acceptable value, but for the sake of consistency will be set to NULL if no value is given
		if(empty($_POST['genki_ch'])) {//will catch "", "0", and NULL 
			$no_genki_ch = true; //Don't want to set to "NULL" just yet, as there's a chance it's going to be fed back into the
								 //form if any errors occurred elsewhere
		}
		elseif(is_numeric($_POST['genki_ch'])) {
			if($_POST['genki_ch'] > 23 || $_POST['genki_ch'] < 1)
				$errormsg["genki_cherror"] = "<b>Genki chapter</b> must be between 1 and 23, or 0 for no chapter. <br />";
			else
				$genki_ch = (int) $_POST['genki_ch'];
		}
		else
			$errormsg["genki_cherror"] = "<b>Genki chapter</b> chapter must be numeric. <br />";


		//Verify that either the on-yomi or kun-yomi field is filled in; one or both can be filled in
		if((validate_yomi_length("onyomi") == 2) && (validate_yomi_length("kunyomi") == 2))
			$errormsg['yomierror'] = "At least one pronunciation field must be filled in.";
		else {

			//Verify the kun-yomi (kana) field; since the DB has a max string size of 20
			//Note that the DB stores each pronunciation separately, so an input of
			//いち、いっ is valid, but must be separated beforehand
			switch(validate_yomi_length("onyomi")) {
				case 0:
					$errormsg["onyomi(kana)error"] = "<b>On-yomi (kana)</b> cannot have more than 20 characters.";
					break;
				case 1:
					$onyomi = $_POST['onyomi']; 
					$onyomi_array = explode("、", $onyomi); //Original $onyomi variable is preserved, so that it can still be used as a value in the HTML
															//input field - it won't work with an array
					break;
				case 2:
					break; //If blank, don't worry about it, since we already know one of the two is filled in
				default:
					$errormsg["onyomi(kana)error"] = "Something unexpected happened when validating <b>On-yomi (kana)</b>.";
			}


			//Verify kun-yomi (kana) field; same deal as above
			switch(validate_yomi_length("kunyomi")) {
				case 0:
					$errormsg["kunyomi(kana)error"] = "<b>Kun-yomi (kana)</b> cannot have more than 20 characters.";
					break;
				case 1:
					$kunyomi = $_POST['kunyomi'];
					$kunyomi_array = explode("、", $kunyomi);
					break;
				case 2:
					break;
				default:
					$errormsg["kunyomi(kana)error"] = "Something unexpected happened when validating <b>Kun-yomi (kana)</b>.";
			}		

		}


		//Validate the romaji fields: if one of the kana fields has passed inspection, attempt to process its related romaji
		//Note: if a kana field is populated, it MUST have its romaji field populated as well, otherwise an error is given
		if(!is_null($onyomi)) {
			switch(validate_yomi_length("r_onyomi")) {
				case 0:
					$errormsg["onyomi(romaji)error"] = "<b>On-yomi (romaji)</b> cannot have more than 20 characters.";
					break;
				case 1:
					$r_onyomi = $_POST['r_onyomi'];
					$r_onyomi_array = explode(",", $r_onyomi);
					//Check to make sure we have the same amount of pronunciations in both
					if(count($r_onyomi_array) != count($onyomi_array))
						$errormsg["onyomi(romaji)error"] = "Every pronunciation in <b>Onyomi (kana)</> must have an equivalent in <b>Onyomi (romaji)</b>.";
					break;
				case 2:
					$errormsg["onyomi(romaji)error"] = "<b>On-yomi (romaji)</b> must be filled in.";
					break;
				default:
					$errormsg["onyomi(romaji)error"] = "Something unexpected happened when validating <b>On-yomi (romaji)</b>.";
			}

		}

		if(!is_null($kunyomi)) {
			switch(validate_yomi_length("r_kunyomi")) {
				case 0:
					$errormsg["kunyomi(romaji)error"] = "<b>Kun-yomi (romaji)</b> cannot have more than 20 characters.";
					break;
				case 1:
					$r_kunyomi = $_POST['r_kunyomi'];
					$r_kunyomi_array = explode(",", $r_kunyomi);
					if(count($r_kunyomi_array) != count($kunyomi_array))
						$errormsg["kunyomi(romaji)error"] = "Every pronunciation in <b>Kunyomi (kana)</b> must have an equivalent in <b>Kunyomi (romaji)</b>.";
					break;
				case 2:
					$errormsg["kunyomi(romaji)error"] = "<b>Kun-yomi (romaji)</b> must be filled in.";
					break;
				default:
					$errormsg["kunyomi(romaji)error"] = "Something unexpected happened when validating <b>Kun-yomi (romaji)</b>.";
			}
		}


		//Validate meaning field; can't be longer than 20 characters, must be filled. 
		switch(validate_yomi_length("meaning")) {
			case 0:
				$errormsg["meaningerror"] = "<b>Meaning</b> cannot have more than 20 characters.";
				break;
			case 1:
				$meaning = $_POST['meaning'];
				break;
			case 2:
				$errormsg["meaningerror"] = "<b>Meaning</b> must be filled in.";
				break;
			default:
				$errormsg["meaningerror"] = "Something unexpected happened when validating <b>Meaning</b>.";

		}

		//Validate rad_meaning field
		switch(validate_yomi_length("rad_meaning", 30)) {
			case 0:
				$errormsg["radmeaningerror"] = "<b>Radical Meaning</b> cannot have more than 20 characters.";
				break;
			case 1:
				$rad_meaning = $_POST['rad_meaning'];
				break;
			case 2:
				$rad_insert = false;
				break;
			default:
				$errormsg["radmeaningerror"] = "Something unexpected happened when validating <b>Radial Meaning</b>.";

		}

		//Validate is_primitive field
		if($rad_insert) {
			$is_primitive = $_POST['is_primitive'];
			strtoupper($is_primitive);
			if( (string) $is_primitive != "Y" && (string) $is_primitive != "N")
				$errormsg["is_primitiveerror"] = "<b>Is Primitive</b> must be Y or N.";
		}


	}

	if (isset($_POST['submit']) || isset($_POST['submit_vocab'])) {

		$errormsg = array();

		//Check if the kanji field is valid
		switch(valid_char("kanji")) {
			case 0:	
				$errormsg["kanjierror"] = "Too many characters in <b>Kanji</b> field. You typed \"{$kanji}\".";
				break;
			case 1:
				$kanji = $_POST['kanji'];
				break;
			case 2:
				$errormsg["kanjierror"] = "<b>Kanji</b> field cannot be blank.";
				break;
			default:
				$errormsg["kanjierror"] = "Something unexpected went wrong while verifying the <b>Kanji</b> field. <br />";
		}	

		//Validate the vocab fields; the amount of words, kana, romaji, and genki chapters must all match
		//First, create arrays for all fields, which are put inside of a larger array

		$vocab_words_array   = explode("；", $_POST['vocab_words']); //NOTE: this is NOT a latin semicolon, it is a Japanese one - they WILL NOT BE SEEN AS IDENTICAL
		$vocab_kana_array    = explode("；", $_POST['vocab_kana']); //NOTE: this is NOT a latin semicolon, it is a Japanese one - they WILL NOT BE SEEN AS IDENTICAL
		$vocab_romaji_array  = explode(";", $_POST['vocab_romaji']); //These ARE latin semicolons, however
		$vocab_meaning_array = explode(";", $_POST['vocab_meaning']);
		$vocab_ch_array      = explode(";", $_POST['vocab_ch']);

		//If they are all the same length, continue to process
		$v_count = count($vocab_words_array);
		if($v_count == count($vocab_kana_array) 
		&& $v_count == count($vocab_romaji_array) 
		&& $v_count == count($vocab_meaning_array) 
		&& $v_count == count($vocab_ch_array))
		{	
		
			//Populate the master array
			$master_vocab_array = array();
			for ($k=0; $k < $v_count; $k++) { 
				$master_vocab_array[$k][] = $vocab_words_array[$k];
				$master_vocab_array[$k][] = $vocab_kana_array[$k];
				$master_vocab_array[$k][] = $vocab_romaji_array[$k];
				$master_vocab_array[$k][] = $vocab_meaning_array[$k];
				$master_vocab_array[$k][] = $vocab_ch_array[$k];
			}

			for ($i=0; $i < $v_count; $i++) { 

				//Validate the first three fields for length, and trim them as well, just in case
				$j = 0;
				for ($j=0; $j < 4; $j++) { 
					trim($master_vocab_array[$i][$j]);
					if (mb_strlen($master_vocab_array[$i][$j]) > 20) {
						$errormsg['vocabworderror'] = "No discrete <b>Vocabulary</b> element can contain more than 20 characters.";
						//break from the loop
						$j += 4;
						$i += $v_count;
					}
				}
				//validate Genki chapter; must be a number between 0 and 23 inclusive
				if($master_vocab_array[$i][4] < 0 || $master_vocab_array[$i][4] > 23) {
					$errormsg['vocabcherror'] = "No <b>Vocabulary</b> element can have a Genki chapter less than 0 or greater than 23.";
					//break from loop
					$i += $v_count;
				}
				elseif(count($master_vocab_array[$i][4]) == 0)
					$master_vocab_array[$i][4] = NULL;

			}

		}
		else
			$errormsg['vocabamterror'] = "Number of entries in each <b>Vocab</b> field doesn't match up.";

	}

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Input Form</title>
	<link href="inputform.css" rel="stylesheet" type="text/css">
</head>
<body>
	<?php make_header(); ?>
	<h1>Insertion Form</h1>
	<div id="Console">
	<?php 
		//If $errormsg is set, which only happens once submit is pressed
		if(isset($errormsg)) {
			if(!count($errormsg)) {
				if(isset($_POST['submit_vocab']) && !empty($master_vocab_array)) {
					if($modify) {

						$vocab_modify_errormsg = wipe_vocab($kanji);

						if(!empty($vocab_modify_errormsg))
							print_errors($vocab_modify_errormsg);
					}

					$vocab_insert_result = insert_vocab($kanji, $master_vocab_array);
						//Check for returned errors
						if(count($vocab_insert_result)) {
							print_errors($vocab_insert_result);
						}
						else {
							$vocab_words = NULL;
							$vocab_kana = NULL;
							$vocab_romaji = NULL;
							$vocab_meaning = NULL;
							$vocab_ch = NULL;
						}
				}
				else {
					if($no_genki_ch) 
						$genki_ch = "NULL";
					

					$radcheck = check_rads_exist($radicals_array);
					if(!count(check_rads_exist($radicals_array)) || in_array($kanji, $radicals_array)) {
						if($modify) {

							$kanji_modify_errormsg = delete_tuple("Chara", "kanji", $kanji);

							if(!empty($kanji_modify_errormsg))
								print_errors($kanji_modify_errormsg);
						}

						//Attempt to insert into DB
						$insert_result = insert_kanji($kanji, $primitive, $strokes, $genki_ch, $onyomi_array, $kunyomi_array, $r_onyomi_array, $r_kunyomi_array, $meaning);

						//Check to see if any error messages were returned
						if(count($insert_result)) {
							?>
							<h3>The following error(s) have occurred: </h3>
							<ul>
							<?php
							foreach($insert_result as $insert_errname => $insert_errmsg) {
								echo "<li>" . ucfirst($insert_errname) . ": " . $insert_errmsg . "</li>";
							}
							?>
							</ul>
							<?php
						}
						else {
							//Insert was successful, so do a query to prove it!
							$return_err = return_kanji_info($kanji);

							if(count($return_err)) {
								?>
								<h3>The following error(s) have occurred: </h3>
								<ul>
								<?php
								foreach($return_err as $return_errname => $return_errmsg) {
									echo "<li>" . ucfirst($return_errname) . ": " . $return_errmsg . "</li>";
								}
								?>
								</ul>
								<?php
							}


							//Next, try to insert into the radical table; this only happens if the rad_meaning field is actually filled in
							if($rad_insert) {

								$rad_insert_result = insert_radical($kanji, $rad_meaning, $is_primitive);

								if(count($rad_insert_result)) {
									print_errors($rad_insert_result);
								}

							}

							//Next, try to insert into the vocabulary table
							if(!empty($master_vocab_array)) {
								$vocab_insert_result = insert_vocab($kanji, $master_vocab_array);
						
								//Check for returned errors
								if(count($vocab_insert_result)) {
									print_errors($vocab_insert_result);
								}
							}



							//Finally, try to insert into composed_of
							$rad_errors = pair_radical($kanji, $radicals_array);

							//If some error was returned
							if(count($rad_errors)) {
								print_errors($rad_errors);
							}

					


							//Reset all the variables so they don't repopulate in the form fields
							$kanji = NULL;
							$primitive = NULL;
							$radicals = NULL;
							$onyomi = NULL;
							$kunyomi = NULL;
							$r_onyomi = NULL;
							$r_kunyomi = NULL;
							$meaning = NULL;
							$strokes = NULL;
							$genki_ch = NULL;

							$rad_meaning = NULL;
							$is_primitive = NULL;

							$vocab_words = NULL;
							$vocab_kana = NULL;
							$vocab_romaji = NULL;
							$vocab_meaning = NULL;
							$vocab_ch = NULL;
						}
					}
					else
						print_errors($radcheck);
				}

			}
			elseif(count($errormsg)) {
				print_errors($errormsg);
			}
		}
	?>
	</div>
	<div id="form">
		<form action="insert_form.php" method="post" accept-charset="UTF-8">
			<span id="KanjiInsert">
			<h3>Insert Character:</h3>
			Kanji:               <br /><input size ="50" type="text" name="kanji" value="<?php echo htmlspecialchars($kanji); ?>" /><br />
			Primitive:           <br /><input size ="50" type="text" name="primitive" value="<?php echo htmlspecialchars($primitive); ?>" /><br />
			Associated Radicals: <br /><input size ="50" type="text" name="radicals" value="<?php echo htmlspecialchars($radicals); ?>" /><br />
			On-yomi (kana):      <br /><input size ="50" type="text" name="onyomi" value="<?php echo htmlspecialchars($onyomi); ?>" /><br />
			Kun-yomi (kana):     <br /><input size ="50" type="text" name="kunyomi" value="<?php echo htmlspecialchars($kunyomi); ?>" /><br />
			On-yomi (romaji):    <br /><input size ="50" type="text" name="r_onyomi" value="<?php echo htmlspecialchars($r_onyomi); ?>" /><br />
			Kun-yomi (romaji):   <br /><input size ="50" type="text" name="r_kunyomi" value="<?php echo htmlspecialchars($r_kunyomi); ?>" /><br />
			Meaning:             <br /><input size ="50" type="text" name="meaning" value="<?php echo htmlspecialchars($meaning); ?>" /><br />
			Strokes:             <br /><input size ="50" type="text" name="strokes" value="<?php echo htmlspecialchars($strokes); ?>" /><br />
			Genki Chapter:       <br /><input size ="50" type="text" name="genki_ch" value="<?php echo htmlspecialchars($genki_ch); ?>" /><br />
			</span>
			<span id="RadicalInsert">
			<h3>Insert into Radical Table:</h3>
			Meaning:             <br /><input size ="50" type="text" name="rad_meaning" value="<?php echo htmlspecialchars($rad_meaning); ?>" /><br />
			Is also a primitive? <br /><input size ="50" type="text" name="is_primitive" value="<?php echo htmlspecialchars($is_primitive); ?>" /><br />
	
           </span>
			<span id="VocabInsert">
			<h3>Insert Vocab:</h3>
			<p><em>as a semicolon-separated list</em></p>
			Word(s):          <br /><input size ="50" type="text" name="vocab_words" value="<?php echo htmlspecialchars($vocab_words); ?>" /><br />
			Kana:             <br /><input size ="50" type="text" name="vocab_kana" value="<?php echo htmlspecialchars($vocab_kana); ?>" /><br />
			Romaji:           <br /><input size ="50" type="text" name="vocab_romaji" value="<?php echo htmlspecialchars($vocab_romaji); ?>" /><br />
			Meaning:          <br /><input size ="50" type="text" name="vocab_meaning" value="<?php echo htmlspecialchars($vocab_meaning); ?>" /><br />
			Genki Chapter:    <br /><input size ="50" type="text" name="vocab_ch" value="<?php echo htmlspecialchars($vocab_ch); ?>" /><br />
							  <br /><input type="submit" name="submit_vocab" value="Submit Just Vocab" />
			</span>
			<span id="submit">
			<input type="submit" name="submit" value="Submit" />
			</span>

			<input type="hidden" name="modify" value="<?php echo $modify; ?>">
		</form>
	</div>

</body>
</html>