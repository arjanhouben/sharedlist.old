<?php

$unique = 0;

if ( !file_exists( "items" ) )
{
	mkdir( "items" );
}

$client = md5($_SERVER[ "HTTP_USER_AGENT" ] . $_SERVER[ "REMOTE_ADDR" ] );

if ( array_key_exists( "items", $_REQUEST ) )
{
	foreach( $_REQUEST[ "items" ] as $name => $item )
	{
		if ( is_array( $item ) && key_exists( "modified", $item ) )
		{
			$path = "items/" . $name;
			$date = $item[ "modified" ];
			$item[ "owner" ] = $client;
			unset( $item[ "modified" ] );
			$newcontent = json_encode( $item );
			$content = "";
			$owner = "";
			if ( file_exists( $path ) )
			{
				$modified = filemtime( $path );
				$content = file_get_contents( $path );
				$jscontent = json_decode( $content, true );
				if ( key_exists( "owner", $jscontent ) )
				{
					$owner = $jscontent[ "owner" ];
				}
			}
			
			$update = ( $date == 0 ) || ( $modified == $date ) || ( $owner == $client );
			
			if ( $update )
			{
				if ( $content != $newcontent )
				{
					file_put_contents( $path, $newcontent );
				}
			}
		}
	}
}

if ( array_key_exists( "id", $_REQUEST ) )
{
	$unique = $_REQUEST[ "id" ];
}

$result = Array();

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

echo json_encode( [ "items" => $result, "id" => $unique ] );

?>
