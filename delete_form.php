<?php 
	require_once("header.php");
	require_once("form_validation.php");
	require_once("dbinsert.php");
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Delete Form</title>
	<link href="inputform.css" rel="stylesheet" type="text/css">
</head>
<body>
	<?php make_header(); ?>
	<h1>Deletion Form</h1>
	<?php 

	$kanji = NULL;
	$vocab_word = NULL;

	//If a kanji is submitted, this will be run
	if(isset($_POST['submit_kanji'])) {

		$errormsg = array();

		//First, make sure the kanji is acceptable
		switch(valid_char("kanji")) {
			case 0:	
				$errormsg["kanjierror"] = "Too many characters in <b>Kanji</b> field. You typed \"{$kanji}\".";
				break;
			case 1:
				$kanji = $_POST['kanji'];

				//Proceed with deletion from Chara table
				$kanji_error = delete_tuple("Chara", "kanji", $kanji);
				if(count($kanji_error))
					print_errors($kanji_error);
				else { //If no errors, reset the kanji field for new input
					echo "<h3>Successfully deleted kanji " . $kanji . ".</h3>";
					$kanji = NULL;
				}
				break;
			case 2:
				$errormsg["kanjierror"] = "<b>Kanji</b> field cannot be blank.";
				break;
			default:
				$errormsg["kanjierror"] = "Something unexpected went wrong while verifying the <b>Kanji</b> field. <br />";
		}


	}
	//If a vocab word is submitted, this will run
	elseif(isset($_POST['submit_vocab'])) {

		$errormsg = array();

		switch(validate_yomi_length("vocab_word")) {
				case 0:
					$errormsg["vocab_word_error"] = "<b>Vocab word</b> field cannot have more than 20 characters.";
					break;
				case 1:
					$vocab_word = $_POST['vocab_word'];

					//Delete from vocab table
					$vocab_error = delete_tuple("Vocab", "word", $vocab_word);
					if(count($vocab_error))
						print_errors($vocab_error);
					else {
						echo "<h3>Successfully deleted vocab word " . $vocab_word . ".</h3>";
						$vocab_word = NULL;
					}

					break;
				case 2:
					break;
				default:
					$errormsg["vocab_word_error"] = "Something unexpected happened when validating <b>Vocab Word</b>.";
			}	


	}

	//If errormsg is set, which will only happen after the form has been submitted
	if(isset($errormsg)) {
		if(count($errormsg)) {
			print_errors($errormsg);
		}


	}

?>

	<div id="form">
		<form action="delete_form.php" method="post" accept-charset="UTF-8">
			<span id="KanjiInsert">
			<h3>Delete Kanji: </h3>
			Kanji: <br /><input size ="50" type="text" name="kanji" value="<?php echo htmlspecialchars($kanji); ?>" /><br /> 
			<input type="submit" name="submit_kanji" value="Delete This Kanji" />
			</span>
			<span id="VocabInsert">
			<h3>Delete Word(s):</h3>
			Word(s):<br /><input size ="50" type="text" name="vocab_word" value="<?php echo htmlspecialchars($vocab_word); ?>" /><br />
			<input type="submit" name="submit_vocab" value="Delete This Vocab" />
			</span>
		</form>
	</div>
	
</body>
</html>