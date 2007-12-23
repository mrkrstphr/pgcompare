<?php
	
	error_reporting( E_ALL ^ E_NOTICE );

	define( "BASE_PATH", realpath( "." ) );
	
	$sInclude_Path = "." . 
		":" . BASE_PATH . "/classes" . 
		":" . BASE_PATH . "/views";
	
	set_include_path( $sInclude_Path );

	//
	// SYSTEM DEFINES
	//
	
	define( "DATABASE_HOST", "localhost" );
	define( "DATABASE_USER", "postgres" );
	define( "DATABASE_PASS", "" );
	
	
	//
	// REQUIRED FILES
	//

	require( "class.database.php" );
	require( "class.database_compare.php" );
	
	require( "class.form.php" );
	
	
	//
	// OBJECT INSTANTIATION
	//
	
	$oDB_General = new Database( DATABASE_HOST, DATABASE_USER, DATABASE_PASS, "template1" );
	
	$oDBCompare = new Database_Compare();

?>
