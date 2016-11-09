<?php

$error = array();
$server_data = array();

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
			$server_data[ $file ] = json_decode( file_get_contents( $path ), true );
			if ( !array_key_exists( "modified", $server_data[ $file ] ) )
			{
				$server_data[ $file ][ "modified" ] = 0;
			}
		}
	}
	
	closedir( $dh );
}

$lastModified = -1;

if ( array_key_exists( "lastModified", $_REQUEST ) )
{
	$lastModified = floatval( $_REQUEST[ "lastModified" ] );
}

if ( array_key_exists( "items", $_REQUEST ) )
{
	$currentModificationTime = microtime( true );
	
	foreach( $_REQUEST[ "items" ] as $name => &$item )
	{
		if ( is_array( $item ) )
		{
			$current = &$server_data[ $name ];
			
			if ( $current )
			{
				
				if ( $current[ "state" ] == $item[ "state" ] )
				{
					$error[ $name ] = "redundant change, state remains at " . $current[ "state" ];
					continue;
				}
			
				if ( array_key_exists( "modified", $current ) )
				{
					if ( $current[ "modified" ] != $item[ "modified" ] )
					{
						$error[ $name ] = "could not be saved, modified " . $current[ "modified" ] . " differs from " . $item[ "modified" ];
						continue;
					}
				}
			}
			
			$path = "items/" . $name;
			
			$current[ "modified" ] = $currentModificationTime;
		
			$current[ "state" ] = $item[ "state" ];
			
			$current[ "usecount" ] = intval( $current[ "usecount" ] ) + 1;
			
			file_put_contents( $path, json_encode( $current ) );
		}
	}
}

foreach( $server_data as $name => &$item )
{
	if ( $item[ "modified" ] <= $lastModified )
	{
		if ( !$error[ $name ] )
		{
			unset( $server_data[ $name ] );
		}
	}
}
	
echo json_encode(
	[
		"items" => $server_data,
		"errors" => $error
	]
);

?>
