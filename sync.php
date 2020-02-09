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

$lastModified = -1;

if ( $request )
{
	if ( array_key_exists( "lastModified", $request ) )
	{
		$lastModified = floatval( $request[ "lastModified" ] );
	}
}
		
if ( $dh = opendir( "items" ) )
{
	while ( ( $file = readdir( $dh ) ) !== false )
	{
		$path = "items/" . $file;
		if ( is_file( $path ) )
		{
			if ( filemtime( $path ) > $lastModified )
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
	}
	
	closedir( $dh );
}

if ( $request )
{
	if ( array_key_exists( "items", $request ) )
	{
		$currentModificationTime = microtime( true );
		
		foreach( $request[ "items" ] as $name => &$item )
		{
			if ( is_array( $item ) )
			{
				$current_server_entry = &$server_data[ $name ];
				
				if ( $current_server_entry )
				{
					if ( $current_server_entry[ "state" ] == $item[ "state" ] )
					{
						$error[ $name ] = "redundant change, state remains at " . $current_server_entry[ "state" ];
						continue;
					}
				
					if ( array_key_exists( "modified", $current_server_entry ) )
					{
						if ( $current_server_entry[ "modified" ] != $item[ "modified" ] )
						{
							$error[ $name ] = "could not be saved, modified " . $current_server_entry[ "modified" ] . " differs from " . $item[ "modified" ];
							continue;
						}
					}
				}
				
				$path = "items/" . $name;
				
				$current_server_entry[ "modified" ] = $currentModificationTime;
			
				$current_server_entry[ "state" ] = $item[ "state" ];
				
				if ( !array_key_exists( "usecount", $current_server_entry ) )
				{
					$current_server_entry[ "usecount" ] = 0;
				}
				$current_server_entry[ "usecount" ] = intval( $current_server_entry[ "usecount" ] ) + 1;
				
				file_put_contents( $path, json_encode( $current_server_entry ) );
			}
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
