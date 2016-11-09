<?php

$error = array();
$result = array();

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
			$result[ $file ] = json_decode( file_get_contents( $path ), true );
			if ( !array_key_exists( "modified", $result[ $file ] ) )
			{
				$result[ $file ][ "modified" ] = 0;
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
		if ( is_array( $item ) && key_exists( "modified", $item ) && key_exists( "state", $item ) )
		{
			$save_contents = true;
			
			if( array_key_exists( $name, $result ) )
			{
				$current = &$result[ $name ];
				
				if ( $current[ "state" ] == $item[ "state" ] )
				{
					$error[ $name ] = "redundant change, state remains at " . $current[ "state" ];
					$save_contents = false;
				}
			
				if ( $current[ "modified" ] != $item[ "modified" ] )
				{
					$error[ $name ] = "could not be saved, modified " . $current[ "modified" ] . " differs from " . $item[ "modified" ];
					$save_contents = false;
				}
			}

			if( $save_contents )
			{
				$path = "items/" . $name;
				
				$current[ "modified" ] = $currentModificationTime;
			
				$current[ "state" ] = $item[ "state" ];
				
				$current[ "usecount" ] = intval( $current[ "usecount" ] ) + 1;
				
				file_put_contents( $path, json_encode( $current ) );
			}
		}
	}
}

foreach( $result as $name => &$item )
{
	if ( $item[ "modified" ] <= $lastModified )
	{
		if ( !$error[ $name ] )
		{
			unset( $result[ $name ] );
		}
	}
}
	
echo json_encode(
	[
		"items" => $result,
		"errors" => $error
	]
);

?>
