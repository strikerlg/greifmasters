<?php

parse_str($_POST['data']);
mysql_connect('localhost', 'root');
mysql_select_db('greifmasters');

for ($offset_count = 0; $offset_count < count($ajax_list); $offset_count++) {
	if(is_int($offset_count)) {
		mysql_query("UPDATE upc_matches SET order = '$offset_count' WHERE match_id = '$ajax_list[$offset_count]'");
	}
	else {
	exit;
	}
}

?>

