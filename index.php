<html>
	<head>
		<title><?php
$path_parts = pathinfo( __FILE__ );
echo basename( $path_parts["dirname"] );
?></title>
		<meta name="viewport" content="width=device-width,height=device-height,user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0" />
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
		<link rel="apple-touch-icon" href="icon-2.0.png">
		<style>
		html, body
		{
			background-color: #789;
			overflow: hidden;
			height: 100%;
			width: 100%;
			margin: 0;
			border: 5px #789 solid;
			position: absolute;
			box-sizing: border-box;
		}
		html, body > *
		{
			position: absolute;
		}
		#items
		{
			top: 28pt;
			bottom: 0;
			left: 0;
			right: 0;
		}
		.customfont
		{
			font-size: 18pt;
			font-family: sans-serif;
			font-weight: lighter;
		}
		input[type=text]
		{
			background-color: transparent;
			border-color: black;
			border-width: 0 0 1px 0;
			border-radius: 0;
			top: 0;
			left: 0;
			right: 0;
			height: 24pt;
		}
		.strike > span
		{
			text-decoration: line-through;
			color: #555;
			overflow: hidden;
		}
		.noselect
		{
			-webkit-touch-callout: none;
			-webkit-user-select: none;
			-khtml-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
			user-select: none;
		}
		.hidden
		{
			display: none;
		}
		#scrollbox
		{
			height: 100%;
			overflow: auto;
			-webkit-overflow-scrolling: touch;
		}
		.item
		{
			position: relative;
			width: 100%;
			overflow: hidden;
			white-space: nowrap;
			transition: width 1.5s ease-in-out, background-color 0.2s;
		}
		.item > span
		{
			display: inline-block;
		}
		.unsaved
		{
			font-style: italic;
		}
		.remove_all
		{
			width: 0;
			background-color: rgba( 0, 0, 0, 0.1 );
		}
		</style>
		<script type="text/javascript">
		"use strict";
		function for_key_value( obj, f )
		{
			for ( var k in obj )
			{
				if ( obj.hasOwnProperty( k ) )
				{
					f( k, obj[ k ] );
				}
			}
		}
		function getLastModified( items )
		{
			var m = 0;
			for_key_value( items,
				function( k, v )
				{
					if ( v && v.modified > m )
					{
						m = v.modified;
					}
				}
			);
			return m;
		}
		function LocalData()
		{
			this.get = function()
			{
				if ( !localStorage[ window.location.host + window.location.pathname ] )
				{
					localStorage[ window.location.host + window.location.pathname ] = "{}";
				}
				return JSON.parse( localStorage[ window.location.host + window.location.pathname ] );
			}
			
			this.set = function( data )
			{
				localStorage[ window.location.host + window.location.pathname ] = JSON.stringify( data );
			}
		}
		function bind( f, t )
		{
			var args = Array.prototype.slice.call( arguments, 2 );
			return function(){
				f.apply( t, args );
			}
		}
		function parseQuery( query )
		{
			var result = {};
			query
				.substr( query.indexOf( '?' ) + 1 )
				.split( '&' )
				.forEach(
					function( i )
					{
						var kv = i.split( '=' );
						result[ kv[ 0 ] ] = kv[ 1 ];
					}
				)
			return result;
		}
		function ALL( args, obj )
		{
			if ( !obj ) obj = document;
			return Array.prototype.slice.call( obj.querySelectorAll.call( obj, args ) );
		}
		function ONE( args, obj )
		{
			if ( !obj ) obj = document;
			return obj.querySelector.apply( obj, [ args ] );
		}
		function emptyrow()
		{
			var div = document.createElement( "div" );
			div.classList.add( "item" );
			var span = document.createElement( "span" );
			div.appendChild( span );
			div.span = span;
			return div;
		}
		function on( obj, ev, selector, f )
		{
			var old = obj[ ev ] || function() {};
			obj[ ev ] = function( e )
			{
				old( e );
				if ( selector )
				{
					var t = e.target;
					while ( t && t !== document )
					{
						if ( t.matches( selector ) )
						{
							return f.call( t, e, t );
						}
						t = t.parentNode;
					}
				}
				else
				{
					return f.call( e.target, e, e.target );
				}
			}
		}
		function Timer( duration, callbacks )
		{
			if ( !callbacks ) callbacks = {};
			var args = Array.prototype.slice.call( arguments, 2 );
			var to = 0;
			var always = callbacks.always;
			var end = function( f )
			{
				if ( to )
				{
					clearTimeout( to );
					to = 0;
					if ( always )
					{
						always.apply( this, args );
						always = 0;
					}
					if ( f ) f.apply( this, args );
				}
			}
			if ( duration )
			{
				to = setTimeout( end.bind( this, callbacks.timeout ), duration );
			}
			this.cancel = end.bind( this, callbacks.cancel );
			this.abort = end.bind( this, callbacks.abort );
			return this;
		}
		function for_all_in( col, parent, fun )
		{
			var args = Array.prototype.slice.call( arguments, 3 );
			Array.prototype.forEach.call( col,
				function( t )
				{
					var p = t;
					for ( var i = 0, len = parent.length; i != len; ++i )
					{
						p = p[ parent[ i ] ];
					}
					p[ fun ].apply( p, args );
				}
			)
		}
		function hashCode( str )
		{
			var hash = 0, i, chr;
			if (str.length === 0) return hash;
			for (i = 0; i < str.length; i++) {
			chr   = str.charCodeAt(i);
			hash  = ((hash << 5) - hash) + chr;
			hash |= 0; // Convert to 32bit integer
			}
			return hash;
		}
		window.onload = function()
		{
			var query = parseQuery( location.search );
			var currentData = new LocalData();
			var container = ONE( "#scrollbox" );
			var item_cache = {};
			
			if ( query.hasOwnProperty( "reset" ) )
			{
				currentData.set( {} );
			}
			
			function getRow( item )
			{
				while ( !item.classList.contains( "item" ) )
				{
					item = item.parentNode;
				}
				return item;
			}
			
			function findItem( str )
			{
				if ( item_cache[ str ] ) item_cache[ str ];
				ALL( "div.item:not(.hidden)>span", container ).forEach(
					function( i )
					{
						if ( i.innerText === str )
						{
							item_cache[ str ] = i.parentNode;
							return false;
						}
					}
				);
				return item_cache[ str ];
			}
			
			function get_or_create_item( str )
			{
				if ( item_cache[ str ] ) item_cache[ str ];
				var row = findItem( str );
				if ( !row )
				{
					row = emptyrow();
					row.span.innerText = str;
				}
				container.appendChild( row );
				item_cache[ str ] = row;
				return row;
			}
			
			function updatePageJson( data )
			{
				return updatePage( JSON.parse( data ) );
			}
			
			function sortItems( a, b )
			{
				var as = a.classList.contains( "strike" );
				var bs = b.classList.contains( "strike" );
				if ( as )
				{
					if ( !bs )
					{
						return 1;
					}
				}
				else if ( bs )
				{
					if ( !as )
					{
						return -1;
					}
				}
				if ( a.innerText < b.innerText ) return -1;
				if ( a.innerText > b.innerText ) return 1;
				return 0;
			}
			
			function sortList()
			{
				var tmp = ALL( "div.item:not(.hidden)", container );
				tmp.sort( sortItems ).forEach(
					function( v ) { container.appendChild( v ); }
				);
			}
			
			function isEmpty( obj )
			{
				return Object.keys( obj ).length === 0;
			}
			
			function keep_hidden_active_strike( v )
			{
				switch( v )
				{
					case "hidden":
					case "active":
					case "strike":
						return true;
					default:
						return false;
				}
			}
			
			function fillPage( data )
			{
				for ( var name in data )
				{
					var i = get_or_create_item( name );
					i.classList.remove( "hidden", "strike" );
					data[ name ][ "state" ]
						.split( ' ' )
						.filter( keep_hidden_active_strike )
						.forEach( function( v ) { i.classList.add( v ); } );
				}
				
				sortList();
			}
			
			function updatePage( data )
			{
				var complete = currentData.get();
				var diff = get_new_changes( complete, data[ "items" ] );

				for_key_value(
					data[ "items" ],
					function( k, v ) { complete[ k ] = v; }
				);

				currentData.set( complete );
									
				if ( !isEmpty( data.errors ) )
				{
					console.log( data.errors );
				}
				fillPage( diff );

				
				ALL(".unsaved").forEach( function( o ) { o.classList.remove( "unsaved" ); } );
			}
			
			function getItemsFromPage()
			{
				var cache = currentData.get();
				var items = {};
				for_key_value( item_cache,
					function( k, v )
					{
						var m = 0;
						if( cache.hasOwnProperty( k ) && cache[ k ] )
						{
							if ( cache[ k ].hasOwnProperty( "modified" ) )
							{
								m = cache[ k ].modified;
							}
						}
						items[ k ] = {
							"modified": m,
							"state": Array.prototype.filter.call( v.classList, keep_hidden_active_strike ).join( " " )
						}
					}
				)
				return items;
			}
			
			function itemDifferent( a, b )
			{
				return !a || !b || ( a[ "state" ] !== b[ "state" ] );
			}
		
			function get_new_changes( old_value, new_value )
			{
				var result = {};
				if ( !old_value )
				{
					return new_value;
				}
				for_key_value( new_value,
					function( k, v )
					{
						if ( v )
						{
							if ( old_value.hasOwnProperty( k ) )
							{
								if ( itemDifferent( old_value[ k ], v ) )
								{
									result[ k ] = v;
								}
							}
							else
							{
								result[ k ] = v;
							}
						}
					}
				)
				return result;
			}
			
			function post( url, data, complete )
			{
				var xhr = new XMLHttpRequest();
				xhr.onreadystatechange = function()
				{
					if ( xhr.readyState == XMLHttpRequest.DONE )
					{
						complete( xhr.responseText );
					}
				}
				var xhrdata = "json=" + encodeURIComponent( JSON.stringify( data ) );
				xhr.open( "POST", url );
				xhr.setRequestHeader( "Content-type", "application/x-www-form-urlencoded" );
				xhr.send( xhrdata );
			}

			function sync()
			{
				if ( navigator.onLine )
				{
					var lastServerData = currentData.get();
					
					var diff = get_new_changes(
						lastServerData,
						getItemsFromPage()
					);
					
					if ( !isEmpty( diff ) )
					{
						diff = { "items" : diff };
					}
					
					var lastModified = getLastModified( lastServerData );
					if ( lastModified )
					{
						diff[ "lastModified" ] = lastModified;
					}
					
					post(
						"sync.php",
						diff,
						updatePageJson
					);
				}
			}
			
			function addRow( str )
			{
				if ( str.length )
				{
					var row = get_or_create_item( str );
					row.classList.remove( "hidden" );
					row.classList.remove( "strike" );
				}
			}
			
			function removeItem( str )
			{
				findItem( str ).classList.add( "hidden" );
			}
			
			var input = ONE("input[name=item]");
			
			var sync_timeout = null;
			
			function syncIfNeeded()
			{
				// wait a bit in case the press that caused this update also
				// triggered a real sync
				clearTimeout( sync_timeout );
				sync_timeout = setTimeout( sync, 1000 );
			}
			
			var start_touch = new Timer();
			
			function toggle_row( t )
			{
				var row = getRow( t ).classList;
				row.toggle( "strike" );
				row.add( "unsaved" );
				sortList();
				syncIfNeeded();
			}
			
			function long_press( t )
			{
				if ( t.matches( ".strike" ) )
				{
					var collection = ALL(":not(.hidden).strike");
					
					for_all_in(
						collection,
						[ "classList" ],
						[ "add" ],
						"remove_all"
					);
					
					var obj = t;
					start_touch = new Timer( 1500,
						{
							"timeout": function( o )
							{
								for_all_in(
									collection,
									[ "classList" ],
									"add",
									"hidden"
								);
								syncIfNeeded();
							},
							"always": function( o )
							{
								for_all_in(
									collection,
									[ "classList" ],
									"remove",
									"remove_all"
								);
							}
						},
						obj
					)
				}
			}
			
			on( document,
				"ontouchstart",
				".item",
				function( e, obj )
				{
					input.blur();
					start_touch = new Timer( 100,
						{
							"timeout": long_press,
							"cancel": toggle_row
						},
						obj
					)
				}
			)
			
			document.onmousedown = document.ontouchstart;
			
			on( document,
				"ontouchmove",
				"#scrollbox",
				function(){ start_touch.abort(); }
			)
			
			on( document,
				"onmouseup",
				null,
				function(){ start_touch.cancel() }
			);
			
			document.ontouchend = document.onmouseup;
			
			document.onkeyup = function( e )
			{
				if ( e.keyCode == 13 )
				{
					addRow( input.value.trim() );
					input.value = "";
					sortList();
					syncIfNeeded();
				}
			}
			
			function findMatching( str )
			{
				var result = [];
				for ( var i in getItemsFromPage() )
				{
					if ( i.length > str.length && i.lastIndexOf( str, str.length ) === 0 )
					{
						result.push( i );
					}
				}
				return result;
			}
			
			function byLength( a, b )
			{
				return a.length > b.length;
			}
			
			function sameSize( len )
			{
				return function( a ) { return a.length === len; };
			}
			
			function suggest( str )
			{
				var i = input;
				var start = i.value.length;
				i.value = str;
				i.setSelectionRange( start, str.length );
				i.focus();
			}
			
			var nosuggest = false;
			
			input.onkeydown = function( e )
			{
				switch ( e.keyCode )
				{
					case 8: // backspace
					case 46: // delete
						nosuggest = true;
						break;
					default:
						nosuggest = false;
				}
			}
			
			input.oninput = function( e )
			{
				this.value = this.value.toLowerCase();
				if ( nosuggest ) return;
				var str = this.value;
				if ( !str.length ) return;
				var matches = findMatching( str ).sort( byLength );
				if ( !matches.length ) return;
				matches = matches.filter( sameSize( matches[ 0 ].length ) );
				if ( matches.length === 1 )
				{
					suggest( matches[ 0 ] );
				}
			}
			
			input.ontouchstart = function( e )
			{
				if ( document.activeElement == this )
				{
					this.blur();
					e.preventDefault();
				}
			}
			
			input.onmousedown = input.ontouchstart;
			
			document.onvisibilitychange = syncIfNeeded;
			
			fillPage( currentData.get() );
			
			sync();
		}
		</script>
	</head>
	<body class="customfont noselect">
		<input class="customfont" name="item" type="text" />
		<div id="items">
			<div id="scrollbox">
			</div>
		</div>
	</body>
</html>