<?php

$error = array();
$server_data = array();
$request = array();

if ( $_REQUEST )
{
	if ( array_key_exists( "json", $_REQUEST ) )
	{
		$json = $_REQUEST[ "json" ];
		$request = json_decode( $json, true );
	}
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
			$server_data[ $file ] = json_decode( file_get_contents( $path ), true );
			if ( $server_data[ $file ] )
			{
				if ( !array_key_exists( "modified", $server_data[ $file ] ) )
				{
					$server_data[ $file ][ "modified" ] = 0;
				}
			}
		}
	}
	
	closedir( $dh );
}

$lastModified = -1;

if ( $request )
{
	if ( array_key_exists( "lastModified", $request ) )
	{
		$lastModified = floatval( $request[ "lastModified" ] );
	}

	if ( array_key_exists( "items", $request ) )
	{
		$currentModificationTime = microtime( true );
		
		foreach( $request[ "items" ] as $name => &$item )
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
				
				if ( !array_key_exists( "usecount", $current ) )
				{
					$current[ "usecount" ] = 0;
				}
				$current[ "usecount" ] = intval( $current[ "usecount" ] ) + 1;
				
				file_put_contents( $path, json_encode( $current ) );
			}
		}
	}
}

foreach( $server_data as $name => &$item )
{
	if ( $item[ "modified" ] <= $lastModified )
	{
		if ( !array_key_exists( $name, $error ) )
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
