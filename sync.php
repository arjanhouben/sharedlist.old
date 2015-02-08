<?php

$unique = 0;
$error = array();
$result = array();

if ( array_key_exists( "session", $_REQUEST ) )
{
	$session = $_REQUEST[ "session" ];
}
else
{
	$session = md5( $_SERVER[ "HTTP_USER_AGENT" ] . $_SERVER[ "REMOTE_ADDR" ] . uniqid() );
}

if ( !file_exists( "items" ) )
{
	mkdir( "items" );
}

if ( $dh = opendir( "items" ) )
{
	while ( ( $file = readdir( $dh ) ) !== false )
	{
		$path = "items/" . $file;
		if ( is_file( $path ) )
		{
			error_log( "read " . $path );
			$result[ $file ] = json_decode( file_get_contents( $path ), true );
			$result[ $file ][ "modified" ] = filemtime( $path );
		}
	}
	
	closedir( $dh );
}

if ( array_key_exists( "items", $_REQUEST ) )
{
	foreach( $_REQUEST[ "items" ] as $name => &$item )
	{
		if ( is_array( $item ) && key_exists( "modified", $item ) && key_exists( "state", $item ) )
		{
			$path = "items/" . $name;
			$current = &$result[ $name ];
			
			if ( file_exists( $path ) )
			{
				if ( $current[ "state" ] == $item[ "state" ] )
				{
					$error[ $name ] = "redundant change, state remains at " . $current[ "state" ];
					continue;
				}
				
				if ( key_exists( "session", $current ) && $current[ "session" ] != $session )
				{
					if ( $current[ "modified" ] != $item[ "modified" ] )
					{
						$error[ $name ] = "could not be saved, state remains at " . $result[ "modified" ];
						continue;
					}
				}
			}

			unset( $item[ "modified" ] );
			$item[ "session" ] = $session;
			
			file_put_contents( $path, json_encode( $item ) );
			
			$current = $item;
			$current[ "modified" ] = filemtime( $path );
		}
	}
}

foreach( $result as $name => &$item )
{
	unset( $item[ "session" ] );
}

echo json_encode(
	[
		"items" => $result,
		"errors" => $error,
		"session" => $session
	]
);

?>
