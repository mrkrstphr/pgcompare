<?php

   ///////////////////////////////////////////////////////////////////////////////////////////////
   // class Database_Compare()
   //
   // Description:
   //
	class Database_Compare
   {
   	private $oDB_Template1 = null;
   	
      var $oDB_Master;
      var $oDB_Update;
      
      public $aDatabases = array();
        
      ///////////////////////////////////////////////////////////////////////////////////////////
      // Database_Compare()
      //
      // Description: Constructor
		//
		public function __construct()
		{
			$oDB_Template1 = Database::getConnection( array(
				"driver" => "postgres",
				"host" => DATABASE_HOST,
				"name" => "template1",
				"user" => DATABASE_USER,
				"password" => DATABASE_PASS
			) );
			
			$this->aDatabases = $oDB_Template1->GetDatabases();

		} // __construct()


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

			// Database Connections:

			$oMaster_DB = Database::GetConnection( array( 
				"driver" => "postgres",
				"host" => DATABASE_HOST,
				"name" => $sMaster_Database, 
				"user" => DATABASE_USER,
				"password" => DATABASE_PASS 
			) );
			
			$oUpdate_DB = Database::GetConnection( array( 
				"driver" => "postgres",
				"host" => DATABASE_HOST,
				"name" => $sUpdate_Database, 
				"user" => DATABASE_USER,
				"password" => DATABASE_PASS 
			) );


			// Get all the tables in each database, and load them into an array
			// run through the "master" array. for each table that does not exist in the
			// "update" array, build a CREATE statement.

			// if the table does exist in both arrays, compare column-by-column, creating
			// ALTER statements for any differences.

			$aMaster_Tables = $oMaster_DB->GetTables();
			$aUpdate_Tables = $oUpdate_DB->GetTables();

			$sSQL = "";
			
			// Loop all the tables:
			foreach( $aMaster_Tables as $iIndex => $sTable )
			{				
				/*if( preg_match( $sIgnore_Tables, $sTable ) != 0 )
				{
					continue;
				}*/
				
				$aMaster_Keys = $oMaster_DB->GetTablePrimaryKey( $sTable );
				$aUpdate_Keys = $oUpdate_DB->GetTablePrimaryKey( $sTable );
				
				$aMaster_Refs = $oMaster_DB->GetTableForeignKeys( $sTable );
				$aUpdate_Refs = $oUpdate_DB->GetTableForeignKeys( $sTable );

				if( !in_array( $sTable, $aUpdate_Tables ) )
				{
					// If this table doesn't exist in the update schema, we need to create it:
					$sSQL .= "-- TABLE " . strtoupper( $sTable ) . "\n\n";

					$sSQL .= "CREATE TABLE " . $sTable . " ( \n";

					// Loop all of the columns in this table:
					$aColumns = $oMaster_DB->GetTableColumns( $sTable );
					
					$sColumns = "";
					
					foreach( $aColumns as $sColumn => $aColumn )
					{
						$sColumns .= empty( $sColumns ) ? "" : ", \n";
						
						if( $this->IsColumnTypeSerial( $sTable, $aColumn ) )
						{
							$aColumn[ "type" ] = "serial";
						}
						
						$sColumns .= "\t" . $sColumn . " " .
							$this->FormatDataType( $aColumn[ "type" ] );
						
						if( count( $aMaster_Keys ) == 1 && in_array( $sColumn, $aMaster_Keys ) )
						{
							$sColumns .= " PRIMARY KEY";
						}
						
						if( $aColumn[ "type" ] != "serial" && $aColumn[ "default" ] != "" )
						{
							$sColumns .= " DEFAULT " . $aColumn[ "default" ];
						}
						
						if( ( $sReference = $this->GetColumnReference( $aColumn, $aMaster_Refs ) ) )
						{
							$sColumns .= " REFERENCES {$sReference}";
						}
						
						
						if( $aColumn[ "not-null" ] && !count( $aMaster_Keys ) == 1 && in_array( $sColumn, $aMaster_Keys ) ) 
						{
							$sColumns .= " NOT NULL";
						}
					}
					
					if( count( $aMaster_Keys ) > 1 )
					{
						$sColumns .= ", \n";
						$sColumns .= "\tPRIMARY KEY( " . implode( ", ", $aMaster_Keys ) . " )";
					}

					$sSQL .= $sColumns . "\n);\n\n";

					// Get index definitions for the table:
					
					$aIndexes = $this->FetchTableIndexDefinitions( $oMaster_DB, $iIndex );
					
					foreach( $aIndexes as $sIndex )
					{
						 $sSQL .= $sIndex . "; \n\n";
					}
				}
				else
				{					
					/*
					$bOutput = false;

					$aMaster_Columns = $oMaster_DB->GetTableColumns( $sTable );
					$aUpdate_Columns = $oUpdate_DB->GetTableColumns( $sTable );

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
					*/

					/*$aMaster_Indexes = $oMaster_DB->FetchTableIndexes( $iIndex );
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
					}*/
				}
			}

			/*
			$aMaster_Sequences = $oMaster_DB->FetchSequences();
			$aUpdate_Sequences = $oUpdate_DB->FetchSequences();

			foreach( $aMaster_Sequences as $sSequence => $iCurrent_Value )
			{
				if( !isset( $aUpdate_Sequences[ $sSequence ] ) )
				{
					$sSQL .= "CREATE SEQUENCE " . $sSequence . " START " . $iCurrent_Value . ";\n\n";
				}
			}
			*/
			
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
				"/( NOT NULL)/i",
				"/( DEFAULT)/i",
				"/(PRIMARY KEY)/i",
				"/( REFERENCES )/i",
				"/( USING )/i" );
				
			$sReplace = "<span style=\"color: #333399;\"><b>\$1</b></span>";

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
		function FormatDataType( $sType )
		{
			return( "<b>{$sType}</b>" );

		} // FormatDataType()
		
		
		private function IsColumnTypeSerial( $sTable, $aColumn )
		{			
			if( strtolower( $aColumn[ "type" ] ) != "integer" )
			{
				return( false );
			}
			
			$sSequenceName = $sTable . "_" . $aColumn[ "field" ] . "_seq";
			
			if( preg_match( "/{$sSequenceName}/i", $aColumn[ "default" ] ) )
			{
				return( true );
			}
			
			return( false );
			
		} // IsColumnTypeSerial()
		
		
		private function GetColumnReference( $aColumn, $aMaster_Refs )
		{
			$sColumnName = $aColumn[ "field" ];
			
			foreach( $aMaster_Refs as $aReference )
			{
				if( count( $aReference[ "local" ] ) != 1 || count( $aReference[ "foreign" ] ) != 1 )
				{
					break;
				}
				
				if( !$aReference[ "dependency" ] ) 
				{
					break;
				}
				
				$sLocal = reset( $aReference[ "local" ] );
				$sForeign = reset( $aReference[ "foreign" ] );

				if( $sLocal == $sColumnName )
				{
					return( $aReference[ "table" ] . "( " . $sForeign . " )" );
				}
			}
		
			return( false );
		
		} // GetColumnReference()
	
	
		private function FetchTableIndexDefinitions( $oDatabase, $iTable )
		{
			$aIndexes = array();
			
			$sSQL = "SELECT 
				*,
				pg_get_indexdef( indexrelid ) AS index_def 
			FROM
				pg_index 
			WHERE 
				indrelid = " . $iTable;
			
			if( !( $oIndexes = $oDatabase->Query( $sSQL ) ) )
			{
				throw new QueryFailedException( $this->GetLastError() );
			}
			
			foreach( $oIndexes as $oIndex )
			{
				if( $oIndex->indisprimary != "t" )
				{
					$aIndexes[] = $oIndex->index_def;
				}
			}
			
			return( $aIndexes );
		
		} // FetchTableIndexDefinitions()
	
	
	}; // Database_Compare()

?>
