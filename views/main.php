<?php
	$aDatabases = $oDB_General->FetchDatabases();

	require( "header.php" );
?>

	<span style="font-size: 8pt;">
		<ul>
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
	</span>

	<br /><br />

	<form action="index.php" method="POST">
		<fieldset style="border: none;">
			<?php $oForm->GenElement( "action", "hidden", array( "value" => "proc_compare" ) ); ?>
			<?php
				$oForm->GenLabel( "Master Database: ", "master_database" );
				$oForm->GenSelect( "master_database", $aDatabases );
			?>

			<br style="clear: both;" />

			<?php
				$oForm->GenLabel( "Update Database: ", "update_database" );
				$oForm->GenSelect( "update_database", $aDatabases );
			?>

			 <br style="clear: both;" />

			 <label for="ignore_tables">Tables to Ignore:</label>
			 <textarea id="ignore_tables" cols="30" rows="3" name="ignore_tables"><?php 
				echo isset( $_POST[ "ignore_tables" ] ) ? $_POST[ "ignore_tables" ] : ""; ?></textarea>

			 <br style="clear: both;" />

			 <label for="file_output">File Output</label>
			 <input type="checkbox" id="file_output" name="file_output" value="t" />

			 <br style="clear: both;" /><br />

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
