<?php

    ///////////////////////////////////////////////////////////////////////////////////////////////
    // class Database()
    //
    // Description:
    //
    class Database
    {
        var $rDatabase;
        
        var $sHost;
        var $sUser;
        var $sPassword;
        var $sDatabase;


        ///////////////////////////////////////////////////////////////////////////////////////////
        // Database()
        //
        // Description: Constructor
        //
        function Database( $sHost, $sUser, $sPassword, $sDatabase )
        {
            $this->sHost = $sHost;
            $this->sUser = $sUser;
            $this->sPassword = $sPassword;
            $this->sDatabase = $sDatabase;
            
            $this->Connect();

        } // Database()


        ///////////////////////////////////////////////////////////////////////////////////////////
        // Connect()
        //
        // Description: Connects to a Postgres database
        //
        function Connect()
        {
            $sConnection_String = "host=" . $this->sHost . " dbname=" . 
                $this->sDatabase . " user=" . $this->sUser . " password=" . $this->sPassword;

            $this->rDatabase = pg_connect( $sConnection_String )
                or trigger_error( "<i>Failed to connect to database</i>", E_USER_ERROR );
        
        } // Connect()


		/////////////////////////////////////////////////////////////////////////////////////////////
		// FetchDatabases()
		//
		// Description: 
		//
		function FetchDatabases()
		{
			$oQ = new Query( $this );
			
			$aDatabases = array();

			$sSQL = "SELECT datname FROM pg_database ORDER BY datname";

			$oQ->Ex( $sSQL );
			
			while( $aDatabase = $oQ->NextRecord() ) 
			{
				 $aDatabases[ $aDatabase[ "datname" ] ] = $aDatabase[ "datname" ];
			}

			return( $aDatabases );
		
		} // FetchDatabases()


		///////////////////////////////////////////////////////////////////////////////////////////
      // FetchTables()
      // 
      // Description: Fetches all tables in a database that do not start with pg_ or sql_
      //
      function FetchTables()
      {
      	$oQ = new Query( $this );
        		
      	$aTables = array();

         $sSQL = "SELECT pt.tablename, pp.typrelid FROM pg_tables AS pt INNER JOIN " . 
             "pg_type AS pp ON pp.typname = pt.tablename WHERE " . 
             "pt.tablename NOT LIKE 'pg_%' AND " . 
             "pt.tablename NOT LIKE 'sql_%'" ;

         $oQ->Ex( $sSQL );
         while( $aTable = $oQ->NextRecord() ) 
         {
             $aTables[ $aTable[ "typrelid" ] ] = $aTable[ "tablename" ];
         }

         return( $aTables );

		} // FetchTables()


	  	///////////////////////////////////////////////////////////////////////////////////////////
	  	// FetchTableColumns
	  	//
	  	// Description: Fetches all columns from a given table by the table's OID
	  	//
	  	function FetchTableColumns( $iTable )
	  	{
	  		$oQ = new Query( $this );
			$aColumns = array();

			$sSQL = "SELECT * FROM pg_attribute AS pa INNER JOIN " . 
				 "pg_type AS pt ON pt.typelem = pa.atttypid WHERE " . 
				 "attrelid = " . $iTable . " AND " . 
				 "attnum > 0 ORDER BY attnum";

			$oQ->Ex( $sSQL );
			while( $aColumn = $oQ->NextRecord() ) 
			{
				//
				// NOTE: Certain versions of postgres have a problem with dropped columns
				//       and add a bunch of .......(blah)...... fields to the table.
				//			Quick fix, no column can start with ".", so ignore those.
				//

				if( substr( $aColumn[ "attname" ], 0, 1 ) != "." )
				{
					//
					// NOTE: We ignore "attlen" as its a default and does not need to be 
					// 	   specified in an CREATE or ALTER query
					//

					$aColumns[] = array(
						"name" => $aColumn[ "attname" ], // attribute name
						"type" => $aColumn[ "typname" ], // attribute type
						"size" => $aColumn[ "atttypmod" ] // custom value (if any)
					);
				}
			}

			return( $aColumns );

		} // FetchTableColumns()

        
        ///////////////////////////////////////////////////////////////////////////////////////////
        // FetchTableIndexDefinitions()
        //
        // Description: Fetches CREATE INDEX statements for the specified table. Too bad 
        //              everything isn't this easy...
        //
        function FetchTableIndexDefinitions( $iTable )
        {
        		$oQ = new Query( $this );
            $aIndexes = array();
            
            $sSQL = "SELECT pg_get_indexdef( indexrelid ) AS index_def FROM " . 
                "pg_index WHERE indrelid = " . $iTable;

            $oQ->Ex( $sSQL );

            while( $aIndex = $oQ->NextRecord() )
            {
                $aIndexes[] = $aIndex[ "index_def" ];
            }

            return( $aIndexes );

        } // FetchTableIndexDefinitions()


        ///////////////////////////////////////////////////////////////////////////////////////////
        // FetchTableIndexes()
        //
        // Description: Fetches a list of indexes for this table
        //
        function FetchTableIndexes( $iTable )
        {

            $aIndexes = array();
			/*
            $sSQL = "SELECT pc.relname, pa.attname, pi.indisunique, pi.indisprimary " . 
                "FROM pg_index AS pi  " . 
                "INNER JOIN pg_class AS pc " . 
                "ON pc.relfilenode = pi.indexrelid " . 
                "INNER JOIN pg_attribute AS pa " . 
                "ON pa.attrelid = pi.indrelid " . 
                "AND pi.indkey[0] = pa.attnum " . 
                "WHERE indrelid = " . $iTable; 

            $rIndexes = $this->Ex( $sSQL );
            while( $aIndex = pg_fetch_array( $rIndexes ) ) 
            {
                $aIndexes[ $aIndex[ "relname" ] ] = array( 
                    "field" => $aIndex[ "attname" ],
                    "unique" => $aIndex[ "indisunique" ],
                    "primary" => $aIndex[ "indisprimary" ]
                );
            }
		*/
            return( $aIndexes );

        } // FetchTableIndexes()


        ///////////////////////////////////////////////////////////////////////////////////////////
        // FetchSequences()
        //
        // Description: Fetches a list of sequences along with their current value
        //
        function FetchSequences()
        {
        		$oQ = new Query( $this );
        		$oQ2 = new Query( $this );
        		
            $aSequences = array();

            $sSQL = "SELECT relname FROM pg_class WHERE " . 
                "relkind = 'S'";

            $oQ->Ex( $sSQL );

            while( $aSequence = $oQ->NextRecord() )
            {
                // get the sequence value -- there is probably a catalog way to
                // do this, but I am too lazy to figure it out.
                $sSQL = "SELECT last_value AS current FROM " . $aSequence[ "relname" ];

                $oQ2->Ex( $sSQL );

                $aSeq_Value = $oQ2->NextRecord( $rSeq_Value );

                $aSequences[ $aSequence[ "relname" ] ] = $aSeq_Value[ "current" ];
            }

            return( $aSequences );
        
        } // FetchSequences()

	}; // Database()
	





	
	////////////////////////////////////////////////////////////////////////////////////////////////
	// class Query()
	//
	class Query
	{
		var $oDBConn;
		var $rResult;
		
		/////////////////////////////////////////////////////////////////////////////////////////////
		// Query()
		//
		// Description: Constructor
		//
		function Query( $oDBConn )
		{
			$this->oDBConn = &$oDBConn;
		
		} // Query()


      ///////////////////////////////////////////////////////////////////////////////////////////
      // Ex()
      //
      // Description: Executes a query
      //
		function Ex( $sSQL )
      {
			$this->rResult = pg_query( $this->oDBConn->rDatabase, $sSQL )
				or trigger_error( "<i>Failed on Query: " . $sSQL . "</i>", E_USER_ERROR );

			return( true );

		} // Ex()

		
		/////////////////////////////////////////////////////////////////////////////////////////////
		// NumRows()
		//
		// Description: Returns the number of rows in the result this->rResult
		//
		function NumRows()
		{
			if( $this->rResult )
			{
				return( pg_num_rows( $this->rResult ) );
			}
			else
			{
				return( 0 );
			}
		
		} // NumRows()


		/////////////////////////////////////////////////////////////////////////////////////////////
		// NextRecord()
		//
		// Description: Returns the next record or null if none
		//
		function NextRecord()
		{
			if( $this->rResult )
			{
				return( pg_fetch_array( $this->rResult ) );
			}
			else
			{
				return( null );
			}
		
		} // NextRecord()
	
	}; // class Query()

?>