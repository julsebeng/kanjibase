<?php
//Validate $fieldname field against these two conditions:
	//Must be populated
	//Must be exactly one character
	//Returns 0 if populated, but too many chars in field	
	//Returns 1 if valid
	//Returns 2 if not filled in at all
function valid_char($fieldname) {
	if(!empty($_POST[$fieldname])) {
		//Can't use strlen() due to the fact that unicode chars are used
		if (mb_strlen($_POST[$fieldname], "UTF-8") === 1)
			return 1;
		else
			return 0;
	}
	else
		return 2;
}


//Used to validate the kana of all four pronunciation fields; max length of all four
//is 20 in the DB
	//Returns 0 if field has too many characters
	//returns 1 if field is valid
	//Returns 2 if not filled in at all
function validate_yomi_length($fieldname, $numchars = 20) {
	if(!empty($_POST[$fieldname])) {
		if (mb_strlen($_POST[$fieldname], "UTF-8") <= $numchars)
			return 1;
		else
			return 0;
	}
	else
		return 2;
}


//Print error messages as a pretty list
function print_errors($errormsg) {	
	?>
	<h3>Please fix the following errors: </h3>
	<ul>
	<?php
	foreach($errormsg as $badfield => $msg) {
		echo "<li>" . ucfirst($badfield) . ": " . $msg . "</li>";
	}
	?>
	</ul>
	<?php
}
?>

