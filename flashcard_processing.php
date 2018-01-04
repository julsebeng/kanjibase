<?php
session_start();
require_once("form_validation.php");
require_once("makecard.php");

//Simple comparison function to be used when sorting dates later on
//Sorts done with this function will have the lowest time as the first element
function datecmp ($a, $b) {
	if($a == $b)
		return 0;

	return ($a < $b) ? -1 : 1;
}


//If the value isn't set, or if there are no results, redirect to the search form
if(!isset($_SESSION['results']) || empty($_SESSION['results'])) 
	header("Location: search_form.php");

//IF $_GET is empty, then we know that this is the first time the deck has been to this page, so it should be prepped
if(empty($_GET)) {

	$errormsg = array();

	//Open a DB connection to gather some additional info about the cards
	$dbhost = "localhost";
	$dbuser = "php_access";
	$dbpass = "cReT7a2EkApHere";
	$dbname = "kanjibase";
	$connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

	//Check for connection errors
	if(mysqli_connect_errno()) {
		mysqli_close($connection);
		$errormsg["Connection_failure"] = "Error: could not connect. " . mysqli_connect_errno() . ": " . mysqli_connect_error();
	}

	//Change charset to UTF-8: since this DB uses Chinese/Japanese characters, queries will NOT FUNCTION without this
	if (!mysqli_set_charset($connection, "utf8")) {
		mysqli_close($connection);
		$errormsg["charset_error"] = "Error: could not change charset to UTF-8, aborting...";
	}


	//Prepare an associative array to be passed into the flashcard generator
	$temp = array();
	foreach ($_SESSION['results'] as $value) {

		//Type is whether or not the card is a kanji card or a vocab card
		// 0 = kanji
		// 1 = vocab
		echo mb_strlen($value, "UTF-8") . "<br />";
		if(mb_strlen($value, "UTF-8") == 1) 
			$type = 0;
		
		else 
			$type = 1;

		$get_meaning = get_meaning($value, $type);

		$temp["{$value}"] = array("back" => $get_meaning, "type" => $type, "review_times" => 0, "next_review" => NULL, "difficulty" => NULL);

		//Create a flip card as well, so the meaning will be the the key now
		
		$temp["{$get_meaning}"] = array("back" => $value, "type" => $type, "review_times" => 0, "next_review" => NULL, "difficulty" => NULL);

	}
}

//Else, if there are values being passed in from $_GET, do different processing
elseif(!empty($_GET)) {
	echo "Updating card info <br />";
	//The card that was just reviewed
	$currentcard = urldecode($_GET['card']);

	//When the user wanted to review the card again
	// 0 = remove from deck
	// 1 = review in 5 min
	// 2 = review in 1 min
	$time = $_GET['time']; 

	//If the user wishes to remove the card, do that first
	if($time == 0) {
		unset($_SESSION['cards']["{$currentcard}"]);
	}
	//Else, change some of the info in the card
	//review_times: how many times the card has been reviewed
	//difficulty: either M or H - determines how soon the card will appear again
	//last_review: timestamp of the last time the card was reviewed, used to determine when the card will show up again
	else {
		$_SESSION['cards']["{$currentcard}"]["review_times"] += 1;

		$currtime = date_create();

		if($time == 1) {
			$_SESSION['cards']["{$currentcard}"]["difficulty"] = "M";
			date_add($currtime, date_interval_create_from_date_string("5 minutes"));
		}
		elseif($time == 2) {
			$_SESSION['cards']["{$currentcard}"]["difficulty"] = "H";
			date_add($currtime, date_interval_create_from_date_string("1 minute"));
		}

		$_SESSION['cards']["{$currentcard}"]["next_review"] = $currtime;

	}
}

?>

<body>

	<?php 
	if(!empty($errormsg)) 
		print_errors($errormsg);

	//If no errors, pass the array into the session variable and redirect
	elseif(empty($_GET)) {

		//If it's already set, delete
		if(isset($_SESSION['cards']))
			unset($_SESSION['cards']);	
		

		//Pull a random card from the array to be used as the first card
		$first = array_rand($temp);

		$_SESSION['cards'] = $temp;
		header("Location: flashcards.php?card=" . urlencode($first));

	}

	//If $_GET is not empty, we know that we came here from the flashcard page, and therefore need to figure out what card to give next
	elseif(!empty($_GET)) {

		$newcard = "";

		//Make sure that there are actually cards still left to review
		if(!empty($_SESSION['cards'])) {

			//First, create an array of potential next cards, based on their difficulty and the last time they were reviewed
			$overdue_cards = array();
			$not_overdue_cards = array();
			$not_reviewed = array();


			//Loop through the cards to find if there are any overdue cards
			$continue_search = TRUE;
			$currtime = date_create();

			foreach ($_SESSION['cards'] as $cardname => $info) {
				if($info['next_review'] != NULL) {

					if($currtime >= $info['next_review']) {
						$overdue_cards["{$cardname}"] = $info['next_review'];
					}
					//If the card has been set, but isn't overdue, put in here
					else
						$not_overdue_cards["{$cardname}"] = $info['next_review'];
				}

				//if the review time is NULL, put that in a separate array
				elseif($info['next_review'] == NULL)
					$not_reviewed[] = $cardname;

			}


			//Next, find a suitable card to return

			//If there are no cards overdue, but there are cards that haven't been reviewed yet, pick a random card that hasn't been reviewed yet
			if(empty($overdue_cards) && !empty($not_reviewed)) {

				$newcard = $not_reviewed[array_rand($not_reviewed)];

			}
			//If there is more than one overdue, find the one most overdue
			elseif(count($overdue_cards) > 1) {

				//Sort the potential cards = the most overdue card will be on top
				uasort($overdue_cards, "datecmp");

				//Get first element key
				reset($overdue_cards);
				$newcard = key($overdue_cards);

			}
			//If there is only one result in $overdue_cards, pick it!
			elseif(count($overdue_cards) == 1) {

				reset($overdue_cards);
				$newcard = key($overdue_cards);

			}
			//If all decks but $not_overdue_cards are empty, choose from there
			//In this case, the card to be reviewed the soonest will be chosen
			elseif(!empty($not_overdue_cards) && empty($overdue_cards) && empty($not_reviewed)) {

				//Sort the potential cards
				uasort($not_overdue_cards, "datecmp");

				//Get first element key
				reset($not_overdue_cards);
				$newcard = key($not_overdue_cards);

			}

		}
		//If there aren't any new cards to review, then tell flashcards.php to print out something different
		else 
			$newcard = "end";

		header("Location: flashcards.php?card=" . urlencode($newcard));


	}

	?>

</body>