<?php
function make_header() {
	echo   "<div id=\"nav\">
			<ul>
				<li><a href=\"insert_form.php\">Insert</a></li>
				<li><a href=\"update_form.php\">Update</a></li>
				<li><a href=\"delete_form.php\">Delete</a></li>
			</ul>
			</div>

			<style type=\"text/css\">
				#nav {
					margin: 0;
					padding: 0;
					width: 100%;
					height: 20px;
				}

				#nav li{
					margin-right: 20px;
					float: left;
					list-style: none;
				}

			</style>";


}

?>