<?php

$error = array();
$result = array();
$db = new SQLite3( "items/sqlite.db", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE );
$request = array();

$db->exec( "CREATE TABLE IF NOT EXISTS items ( name varchar(255), modified int, usecount int, state int )" );

if ( $_REQUEST )
{
	if ( array_key_exists( "json", $_REQUEST ) )
	{
		$json = $_REQUEST[ "json" ];
		$request = json_decode( $json, true );
	}
}

$lastModified = -1;

if ( $request )
{
	if ( array_key_exists( "lastModified", $request ) )
	{
		$lastModified = intval( $request[ "lastModified" ] );
	}
}

$sql_result = $db->query( "SELECT * FROM items WHERE modified > " . $lastModified );

while( $row = $sql_result->fetchArray( SQLITE3_ASSOC ) )
{
	$name = $row[ "name" ];
	unset( $row[ "name" ] );
	$result[ "items" ][ $name ] = $row;
}

// check if user sent items

if ( $request )
{
	if ( array_key_exists( "items", $request ) )
	{
		$currentModificationTime = intval( microtime( true ) * 1000 );

		foreach( $request[ "items" ] as $name => &$item )
		{
			unset( $result[ $name ] );
		
			// get server version of same item
			$WHERE_CLAUSE = " WHERE name=\"" . $name . "\"";
			$sql_result = $db->query( "SELECT modified,state,usecount FROM items" . $WHERE_CLAUSE );
			$current_server_entry = $sql_result->fetchArray( SQLITE3_ASSOC );

			if ( $current_server_entry )
			{
				if ( $current_server_entry[ "state" ] == $item[ "state" ] )
				{
					$error[ $name ] = "redundant change, state remains at " . $current_server_entry[ "state" ];
				}
			
				if ( array_key_exists( "modified", $current_server_entry ) )
				{
					if ( intval( $current_server_entry[ "modified" ] ) > 0 )
					{
						// bccomp returns 0 if values match
						if ( bccomp( $current_server_entry[ "modified" ], $item[ "modified" ], 4 ) )
						{
							$error[ $name ] = "could not be saved, modified " .
								$current_server_entry[ "modified" ] .
								" differs from " . $item[ "modified" ];
							$error[ "modified" ] = $current_server_entry[ "modified" ];
							$result[ "items" ][ $name ] = $current_server_entry;
							continue;
						}
					}
				}
				
				$item[ "modified" ] = $currentModificationTime;
				$item[ "usecount" ] = intval( $current_server_entry[ "usecount" ] ) + 1;
				
				if ( $db->exec(
						"UPDATE items SET modified = \"" . $item[ "modified" ] .
						"\", state = \"" . $item[ "state" ] .
						"\", usecount = \"" . $item[ "usecount" ] . "\"" . $WHERE_CLAUSE
						)
					)
				{
					$result[ "items" ][ $name ] = $item;
				}
				else
				{
					$result[ "items" ][ $name ] = $current_server_entry;
					$error[ $name ] = "could not be saved, DB did not accept change: "
						. $db->lastErrorCode()
						. " -> "
						. $db->lastErrorMsg();
				}
			}
			else
			{
				if ( $db->exec(
						"INSERT INTO items ( name, modified, usecount, state ) " .
						"VALUES ( \"" . $name . "\"," .
						" \"" . $item[ "modified" ] . "\"," .
						" \"" . $item[ "usecount" ] . "\"," .
						" \"" . $item[ "state" ] . "\" )"
						)
					)
				{
					$result[ "items" ][ $name ] = $item;
				}
			}
		}
	}
}

$sql_result = $db->query( "SELECT COUNT(name) FROM items WHERE state LIKE '%hidden%'" );
$number_hidden = $sql_result->fetchArray( SQLITE3_NUM )[ 0 ];

$sql_result = $db->query( "SELECT COUNT(name) FROM items WHERE state LIKE '%strike%' AND NOT state LIKE '%hidden%'" );
$number_strike = $sql_result->fetchArray( SQLITE3_NUM )[ 0 ];

$sql_result = $db->query( "SELECT COUNT(name) FROM items WHERE state = ''" );
$number_active = $sql_result->fetchArray( SQLITE3_NUM )[ 0 ];

$result[ "errors" ] = $error;
$result[ "check" ] = $number_active . "." . $number_strike . "." . $number_hidden;
echo json_encode( $result );

?>

