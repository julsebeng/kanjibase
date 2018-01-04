<?php
	session_start();
	require_once("header.php");
	//If form is submitted, do some preliminary info gathering before redirecting 
	//Also make sure that something was submitted in the first place
	if (isset($_POST['submit']) && !empty($_POST['search_query'])) {
		$search_query = $_POST['search_query'];

		//String to append to URL when info is shipped off to be processed
		$get_string = "?";

		//First, trim any whitespace
		trim($search_query);

		//Next, check for any tags included in the search string, first is chapter:
		if (($pos = stripos($search_query, "chapter:")) !== FALSE) { 
    		$chapter_num = substr($search_query, $pos+8); //get everything after the tag, which is 8 chars long

    		//Trim anything off the end of the number
    		$get_string .= "ch=";
    		if (($pos = strpos($chapter_num, " ")) === FALSE) {
				$get_string .= substr($chapter_num, 0);
    		}
    		else
    			$get_string .= substr($chapter_num, 0, $pos);

			$chapter_num = substr($chapter_num, $pos);

			//Check of asc or desc is given afterwards
			if (($pos = stripos($chapter_num, "asc")) !== FALSE) 
				$get_string .= "&mod=1";
			
			elseif (($pos = stripos($chapter_num, "desc")) !== FALSE) 
				$get_string .= "&mod=2";

		}
		//Next, check if strokes: was used
		elseif (($pos = stripos($search_query, "strokes:")) !== FALSE) { 
    		$stroke_num = substr($search_query, $pos+8); //get everything after the tag, which is 8 chars long

    		//Trim anything off the end of the number
    		$get_string .= "str=";
    		if (($pos = strpos($stroke_num, " ")) === FALSE) {
				$get_string .= substr($stroke_num, 0);
    		}
    		else
    			$get_string .= substr($stroke_num, 0, $pos);

			$stroke_num = substr($stroke_num, $pos);

		}
		//Finally, check for the radical: tag
		elseif (($pos = stripos($search_query, "radical:")) !== FALSE) { 
    		$rad_search = substr($search_query, $pos+8); //get everything after the tag, which is 8 chars long

    		//Trim anything off the end of the number
    		$get_string .= "rad=";
    		if (($pos = strpos($rad_search, " ")) === FALSE) {
				$get_string .= substr($rad_search, 0);
    		}
    		else
    			$get_string .= substr($rad_search, 0, $pos);

			$rad_search = substr($rad_search, $pos);

			//Check of asc or desc is given afterwards
			if (($pos = stripos($rad_search, "asc")) !== FALSE) 
				$get_string .= "&mod=1";
			
			elseif (($pos = stripos($rad_search, "desc")) !== FALSE) 
				$get_string .= "&mod=2";

		}
		//Otherwise, just return the query
		else {
			$get_string .= "search=" . $search_query;
		}

		//With this info, redirect to the query processing page
		header("Location: search_results.php" . htmlspecialchars($get_string));


	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Search Kanjibase</title>
	<link href="searchform.css" rel="stylesheet" type="text/css">
</head>
<body>

<?php

	if(isset($_SESSION['login']) && $_SESSION['login'] == TRUE)	
		make_header();
	else
		echo "<p><a href=\"login.php\">Login</a></p>";
?>

	<h1>Search Form</h1>
	<form action="search_form.php" method="post" accept-charset="UTF-8">
		<br /><input size ="50" type="text" name="search_query" value="" /><br />
		<input type="submit" name="submit" value="Search" />
	</form>
</body>