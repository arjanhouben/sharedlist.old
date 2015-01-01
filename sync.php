<?php

if ( !file_exists( "items" ) )
{
	mkdir( "items" );
}

if ( array_key_exists( "items", $_REQUEST ) )
{
	foreach( $_REQUEST[ "items" ] as $name => $item )
	{
		if ( is_array( $item ) && key_exists( "modified", $item ) )
		{
			$path = "items/" . $name;
			$date = $item[ "modified" ];
			unset( $item[ "modified" ] );
			$newcontent = json_encode( $item );
			if ( file_exists( $path ) )
			{
				$modified = filemtime( $path );
			}
			if ( $date == 0 )
			{
				file_put_contents( $path, $newcontent );
			}
			else if ( $modified == $date )
			{
				$content = file_get_contents( $path );
				if ( $content != $newcontent )
				{
					file_put_contents( $path, $newcontent );
				}
			}
			else
			{
				error_log( "could not update item " . $name );
			}
		}
	}
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

echo json_encode( [ "items" => $result ] );

?>
