<?php
	$aDatabases = $oDB_General->FetchDatabases();

	require( "header.php" );
?>


	<ul class="small-bulleted-list">
		<li>
			<i>Master Database</i> is the name of the database whose schema we want to duplicate
		</li>
		<li>
			<i>Update Database</i> is the name of the database whose schema we want to update
		</li>
		<li>
			<i>Tables to Ignore</i> is a comma seperated list of tables to ignore (Use an 
			astericks (*) as a wildcard).
		</li>
	</ul>


	<form action="index.php?action=proc_compare" method="post">
		<fieldset>			
			<?php Form::Label( array( "for" => "master_database", "label" => "Master Database" ) ); ?>
			<?php Form::Select( array( "id" => "master_database", "name" => "master_database", 
				"options" => $aDatabases ) ); ?>
			<br style="clear: both;" />

			<?php Form::Label( array( "for" => "update_database", "label" => "Update Database" ) ); ?>
			<?php Form::Select( array( "id" => "update_database", "name" => "update_database", 
				"options" => $aDatabases ) ); ?>

			<br style="clear: both;" />

			<?php Form::Label( array( "for" => "ignore_tables", "label" => "Tables to Ignore" ) ); ?>
			<?php Form::TextArea( array( "id" => "ignore_tables", "name" => "ignore_tables", 
				"cols" => 30, "rows" => 3 ) ); ?>

			<br style="clear: both;" />

			<?php Form::Label( array( "for" => "file_output", "label" => "File Output" ) ); ?>
			 <input type="checkbox" id="file_output" name="file_output" value="t" />

			<br style="clear: both;" />
			<br />

			<input type="submit" value=" Submit" />
		</fieldset>
	</form>
	
	<?php
		if( isset( $sSQL ) )
		{
		?>
		
			<pre><?php echo $sSQL; ?></pre>
		
		<?php
		}
	?>

<?php
	require( "footer.php" );
?>
