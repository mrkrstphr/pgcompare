<?php
 ///////////////////////////////////////////////////////////////////////////////////////////////
 //
 // postgres-updater.php
 //
 // Description: Loops two Postgres databases and generates SQL statements for missing
 //              tables and columns in the the second database that appear in the first.
 //
 //
 // TODO: Current the code does not check field sizes. This is difficult as Postgres has no
 //       way of modifying field lengths, except by actually modifying the catalog. Also,
 //       this code does not generate DROP queries for tables/columns that are in the second 
 //       database but not the first. Obviously we may not want to drop these, so when 
 //       generated, they should be commented out by default, and the user can decided whether
 //       or not the entities should be dropped.
 //
 //
 //
 // 08/27/2006 KLW Initial Version
 //


?>