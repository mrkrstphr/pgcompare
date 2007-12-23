<?php

   ///////////////////////////////////////////////////////////////////////////////////////////////
   // class Database_Compare()
   //
   // Description:
   //
	class Database_Compare
   {
      var $oDB_Master;
      var $oDB_Update;
        
      ///////////////////////////////////////////////////////////////////////////////////////////
      // Database_Compare()
      //
      // Description: Constructor
		//
		function Database_Compare()
		{

		} // Database()


		/////////////////////////////////////////////////////////////////////////////////////////////
		// CompareDBs()
		//
		// Description: Given two databases to compare, list all the missing tables, fields, 
		//		indexes and sequences for sUpdate_Database with sMaster_Database.
		//		sIgnore_Tables is a list of tables to ignore in the comparison.
		//
		function CompareDBs( $sMaster_Database, $sUpdate_Database, $sIgnore_Tables = '', $bPretty = true )
		{
			// build a regular expression for matching the tables:
			// ( at this point, assume the user didn't enter data to blow the program up )
			//
			$sIgnore_Tables  = "/(" . $sIgnore_Tables . ")/i";

			$sIgnore_Tables = str_replace( "*", "(.*)", $sIgnore_Tables );
			$sIgnore_Tables = str_replace( ",", "|", $sIgnore_Tables );


			//
			// Database Connection
			//

			$oMaster_DB = new Database( "localhost", "postgres", "", $sMaster_Database );
			$oMaster_DB->Connect();

			$oUpdate_DB = new Database( "localhost", "postgres", "", $sUpdate_Database );
			$oUpdate_DB->Connect();


			// Get all the tables in each database, and load them into an array
			// run through the "master" array. for each table that does not exist in the
			// "update" array, build a CREATE statement.

			// if the table does exist in both arrays, compare column-by-column, creating
			// ALTER statements for any differences.

			$aMaster_Tables = $oMaster_DB->FetchTables();
			$aUpdate_Tables = $oUpdate_DB->FetchTables();

			$sSQL = "";
			
			// Loop all the tables:
			foreach( $aMaster_Tables as $iIndex => $sTable )
			{
				/*if( preg_match( $sIgnore_Tables, $sTable ) != 0 )
				{
					continue;
				}*/

				if( ( $iUpdate_Index = array_search( $sTable, $aUpdate_Tables ) ) === false )
				{
					// If this table doesn't exist in the update schema, we need to create it:
					$sSQL .= "-- CREATE TABLE " . strtoupper( $sTable ) . "\n\n";

					$sSQL .= "CREATE TABLE " . $sTable . " ( \n";

					// Loop all of the columns in this table:
					$aColumns = $oMaster_DB->FetchTableColumns( $iIndex );
					for( $iColumn = 0; $iColumn < count( $aColumns ); $iColumn++ )
					{
						$sSQL .= "\t" . $aColumns[ $iColumn ][ "name" ] . " " .
							$this->FormatDataType( $aColumns[ $iColumn ][ "type" ], 
								$aColumns[ $iColumn ][ "size" ] );

						if( $iColumn < count( $aColumns ) - 1 )
						{
							$sSQL .= ", ";
						}

						$sSQL .= "\n";
					}

					$sSQL .= ");\n\n";

					// Get index definitions for the table:
					$aIndexes = $oMaster_DB->FetchTableIndexDefinitions( $iIndex );
					foreach( $aIndexes as $sIndex )
					{
						 $sSQL .= $sIndex . "; \n\n";
					}
				}
				else
				{
					$bOutput = false;

					$aMaster_Columns = $oMaster_DB->FetchTableColumns( $iIndex );
					$aUpdate_Columns = $oUpdate_DB->FetchTableColumns( $iUpdate_Index );

					for( $iColumn = 0; $iColumn < count( $aMaster_Columns ); $iColumn++ )
					{
						if( ( $iColumn_Index = $this->FindColumn( $aMaster_Columns[ $iColumn ][ "name" ], $aUpdate_Columns ) ) === false )
						{
							if( !$bOutput )
							{
								$bOutput = true;
								$sSQL .= "-- UPDATE TABLE " . strtoupper( $sTable ) . "\n\n";
							}

							$sSQL .= "ALTER TABLE " . $sTable . " ADD COLUMN " . 
								$aMaster_Columns[ $iColumn ][ "name" ] . " " .  
								$this->FormatDataType( $aMaster_Columns[ $iColumn ][ "type" ], 
									 $aMaster_Columns[ $iColumn ][ "size" ] ) . "; \n\n";
						}
					}

					$aMaster_Indexes = $oMaster_DB->FetchTableIndexes( $iIndex );
					$aUpdate_Indexes = $oUpdate_DB->FetchTableIndexes( $iUpdate_Index );

					foreach( $aMaster_Indexes as $sField => $aIndex_Details )
					{
						//
						// TODO : Check if the index has changed? Column, primary, index?
						//

						if( !isset( $aUpdate_Indexes[ $sField ] ) )
						{
							if( !$bOutput )
							{
								$bOutput = true;
								//echo "<span style=\"color: #339900;\">-- <b>UPDATE TABLE " . strtoupper( $sTable ) . "</b></span> \n\n";
								
								$sSQL .= "-- UPDATE TABLE " . strtoupper( $sTable ) . "\n\n";
							}

							$sSQL .= "CREATE " . ( ( $aIndex_Details[ "unique" ] == "t" ) ? 
								"UNIQUE " : "" ) . "INDEX " . $sField . " " . 
								"ON " . $sTable . 
								"( " . $aIndex_Details[ "field" ] . " ); \n\n";
						}
					}
				}
			}

			$aMaster_Sequences = $oMaster_DB->FetchSequences();
			$aUpdate_Sequences = $oUpdate_DB->FetchSequences();

			foreach( $aMaster_Sequences as $sSequence => $iCurrent_Value )
			{
				if( !isset( $aUpdate_Sequences[ $sSequence ] ) )
				{
					$sSQL .= "CREATE SEQUENCE " . $sSequence . " START " . $iCurrent_Value . ";\n\n";
				}
			}
			
			return( ( $bPretty ) ? $this->ColorizeSQL( $sSQL ) : $sSQL );
			
		} // CompareDBs()

		
		//////////////////////////////////////////////////////////////////////////////////////////////
		//
		// ColorizeSQL()
		//
		// Description: Adds color to elements of a SQL statement
		//
		function ColorizeSQL( $sSQL )
		{
			$aSQL_Statements = array( 
				"/(CREATE UNIQUE INDEX )/i", 
				"/(CREATE INDEX )/i", 
				"/(CREATE SEQUENCE )/i",
				"/(CREATE TABLE )/i",
				"/(ALTER TABLE )/i",
				"/(ADD COLUMN )/i",
				"/( ON )/i", 
				"/( USING )/i" );
				
			$sReplace = "<span style=\"color: #333399;\"><b>$1</b></span>";

			$sSQL = preg_replace( $aSQL_Statements, $sReplace, $sSQL );

			return( $sSQL );
			
		} // ColorizeSQL()

		///////////////////////////////////////////////////////////////////////////////////////////////
		// 
		// Find Column
		//
		// Description: Searches a passed column array for a given column name
		//
		function FindColumn( $sColumn, &$aColumns )
		{
			foreach( $aColumns as $iIndex => $aColumn )
			{
				if( $aColumn[ "name" ] == $sColumn )
				{
					return( $iIndex );
				}
			}

			return( false );

		} // FindColumn()


		///////////////////////////////////////////////////////////////////////////////////////////////
		// FormatDataType()
		//
		// Description: Takes in a datatype and field size and converts them for use
		//     in a query
		//
		function FormatDataType( $sType, $iSize )
		{
			 // if size == -1, then the default size of that datatype is used

			 $sType = str_replace( "_", "", $sType );
			 $sType = strtoupper( $sType );

			 if( $sType == 'VARCHAR' && $iSize != -1 )
			 {
				  // for varchar (and other some other fields, probably), you must subtract
				  // the size of an int, as the actual size of the field is stored in
				  // the field as well -- or something.

				  $iSize = $iSize - 4;
			 }

			 if( $sType == "NUMERIC" )
			 {
				  // found this in the user comments of the PostgreSQL manual:
				  $iPrecision = floor( $iSize / 65536 );
				  $iDecimal = $iSize - $iPrecision * 65536 - 4;
				  $iSize = " " . $iPrecision . ", " . $iDecimal . " ";
			 }

			 if( $iSize != -1 )
			 {
				  $sType .= "( " . $iSize . " )";
			 }

			 return( $sType );

		} // FormatDataType()
	
	
	}; // class Database_Compare()

?>