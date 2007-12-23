<?php
	require( "config.php" );

	$sAction = isset( $_POST[ "action" ] ) ? $_POST[ "action" ] : 
		( isset( $_GET[ "action" ] ) ? $_GET[ "action" ] : "" );
		
	$sMaster = isset( $_POST[ "master_database" ] ) ? $_POST[ "master_database" ] : 
		( isset( $_GET[ "master_database" ] ) ? $_GET[ "master_database" ] : "" );
		
	$sUpdate = isset( $_POST[ "update_database" ] ) ? $_POST[ "update_database" ] : 
		( isset( $_GET[ "update_database" ] ) ? $_GET[ "update_database" ] : "" );
		
	$sIgnore_Tables = isset( $_POST[ "ignore_tables" ] ) ? $_POST[ "ignore_tables" ] : 
		( isset( $_GET[ "ignore_tables" ] ) ? $_GET[ "ignore_tables" ] : "" );
		
	$sFile_Output = isset( $_POST[ "file_output" ] ) ? $_POST[ "file_output" ] : 
		( isset( $_GET[ "file_output" ] ) ? $_GET[ "file_output" ] : "" );
		
	switch( $sAction )
	{
		case "proc_compare":
			Form::LoadArray( $_POST );
			
			if( $sFile_Output == "t" )
			{
				header( "Content-type: text/plain;" );
				header( "Content-disposition: attachment; filename=schema.sql;" );
				$sSQL = $oDBCompare->CompareDBs( $sMaster, $sUpdate, $sIgnore_Tables, false );
				
				echo $sSQL;
			}
			else
			{
				$sSQL = $oDBCompare->CompareDBs( $sMaster, $sUpdate, $sIgnore_Tables );
				$sView = "main.php";
			}
		break;
		
		default:
			$sView = "main.php";
	}
	
	if( isset( $sView ) )
	{
		require( $sView );
	}
	
?>
