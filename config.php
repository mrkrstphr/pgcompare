<?php
	
	error_reporting( E_ALL ^ E_NOTICE );

	define( "BASE_PATH", realpath( "." ) );
	
	$sInclude_Path = "." . 
		":" . BASE_PATH . "/classes" . 
		":" . BASE_PATH . "/classes/openavanti" . 
		":" . BASE_PATH . "/views";
	
	set_include_path( $sInclude_Path );

	// SYSTEM DEFINES
	
	define( "DATABASE_HOST", "localhost" );
	define( "DATABASE_USER", "postgres" );
	define( "DATABASE_PASS", "" );
	
	
	// OBJECT INSTANTIATION
	
	//$oDB_General = new Database( DATABASE_HOST, DATABASE_USER, DATABASE_PASS, "template1" );
	
	$oDBCompare = new Database_Compare();


	////////////////////////////////////////////////////////////////////////////////////////////
	function __autoload( $sClass )
	{		
		$sFileName = "class." . strtolower( $sClass ) . ".php";
		$aPaths = explode( PATH_SEPARATOR, get_include_path() );
		
		foreach( $aPaths as $sPath )
		{
			$sFile = "{$sPath}/{$sFileName}";
				
			if( file_exists( $sFile ) )
			{
				require( $sFile );
				return;
			}
		}
		
	} // __autoload()

?>
