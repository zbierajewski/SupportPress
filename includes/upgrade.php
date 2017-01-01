<?php


// uprade the db schema etc if required
function sp_upgrade() {

	require_once('includes/backpress/class.bp-sql-schema-parser.php');

	global $db, $table_prefix;

	$schema = include('schema.php');

	if ( is_array($schema) ) {
		$parser = new BP_SQL_Schema_Parser();

		// fill in the table names
		foreach ( $schema as $t => $q )
			$schema[$t] = sprintf($q, $table_prefix . $t);

		$result = $parser->delta( $db, $schema );
		if ( !empty($result['errors']) ) {
			echo '<pre>';
			echo "Upgrade error:\n";
			var_dump($result);
			echo '</pre>';
		}
	}

}