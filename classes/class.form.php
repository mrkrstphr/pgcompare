<?php
/////////////////////////////////////////////////////////////////////////////////
// class_form.req
//
// Description:
//

/////////////////////////////////////////////////////////////////////////////////
class Form
//
// Description:
// 
//
{
   var $aFields = array();
   var $aFiles = array();
   
   var $aErrors = array();
   var $iError_Count = 0;
   var $aNotes = array();
   

	////////////////////////////////////////////////////////////////////////////////////////////////
	function Form()
	//
	// Description:
	//
	{


	} // Form()


	////////////////////////////////////////////////////////////////////////////////
	function LoadFromSubmit()
	//
	// Description:
	//
	{
	
		foreach( $_GET as $sField => $sValue )
		{
			$this->aFields[ $sField ] = $sValue;
		}
		
		foreach( $_POST as $sField => $sValue )
		{
			$this->aFields[ $sField ] = $sValue;
		}

		foreach( $_FILES as $sField => $aValue )
		{
			$this->aFiles[ $sField ] = $aValue;
		}

		return( true );

	}  // LoadSubmit()


	//////////////////////////////////////////////////////////////////////////////
	function SetError( $sField, $sMsg )
	//
	// Description:
	//
	{
		$this->iError_Count++;
		$this->aErrors[ $sField ] = true;
		$this->aNotes[ $sField ] = $sMsg;

		return( true );
	}
	

	//
	// Form generation methods
	//


	////////////////////////////////////////////////////////////////////////////////////////////////
	function GenElement( $sName, $sType, $aAttributes = null, $bReturn = false )
	//
	// Description:
	//
	{
		$aValidTypes = array( 
			"text", "hidden", "check", "radio", "file", "submit", "button", "password"
		);
		
		if( !in_array( $sType, $aValidTypes ) )
		{
			return( false );
		}
		
		$sElement = "<input type=\"" . $sType . "\" name=\"" . $sName . "\" ";
		
		if( isset( $this->aFields[ $sName ] ) )
		{
			$sElement .= "value=\"" . $this->aFields[ $sName ] . "\" ";
			
			if( isset( $aAttributes[ "value" ] ) )
			{
				unset( $aAttributes[ "value" ] );
			}
		}
		
		foreach( $aAttributes as $sName => $sValue )
		{
			$sElement .= $sName . "=\"" . $sValue . "\" ";
		}
		
		$sElement .= " />";
		
		if( $bReturn )
		{
			return( $sElement );
		}
		else
		{
			echo $sElement;
		}
		
	} // GetElement()


	///////////////////////////////////////////////////////////////////////////////////////////////////
	function GenLabel( $sDisplay, $sName, $bReturn = false )
	//
	// Description: 
	//
	{

		if( isset( $this->aNotes[ $sName ] ) && !blank( $this->aNotes[ $sName ] ) )
		{
			$sLabel = "<span class=\"formerror\"><label for=\"" . $sName . "\">" . $sDisplay . "</label></span>";
		}
		else
		{
			$sLabel = "<label for=\"" . $sName . "\">" . $sDisplay . "</label>";
		}

		if( $bReturn )
		{
			return( $sLabel );
		}
		else
		{
			echo $sLabel;
		}

	} // GenLabel()


	///////////////////////////////////////////////////////////////////////////////////////////////////
	function GenSelect( $sName, $aValues, $sDefault = "", $aAttributes = array(), 
		$bReverse = false, $bSort = true )
	//
	// Description:
	//    Generates select and option tags
	//
	{

		if( $bSort )
		{
			ksort( $aValues );
		}

		$sSelected = isset( $this->aFields[ $sName ] ) ? $this->aFields[ $sName ] : $sDefault;


		$sElement = "<select name=\"" . $sName . "\" ";

		foreach( $aAttributes as $sName => $sValue )
		{
			$sElement .= $sName . "=\"" . $sValue . "\" ";
		}

		$sElement .= ">";


		foreach( $aValues as $sKey => $sValue )
		{
			if( $bReverse )
			{
				$sInput_Key = $sValue; $sInput_Value = $sKey;
			}
			else
			{
				$sInput_Key = $sKey; $sInput_Value = $sValue;
			}

			$sElement .= "<option value=\"" . $sInput_Key . "\" " . 
				( ( $sInput_Value == $sSelected ) ? "selected=\"selected\" " : "" ) . " />" . 
				$sInput_Value . "</option>";
		}

		$sElement .= "</select>";

		if( $bReturn )
		{
			return( $sElement );
		}
		else
		{
			echo $sElement;
		}

	} // GenSelect()


}; // class Form()

?>
