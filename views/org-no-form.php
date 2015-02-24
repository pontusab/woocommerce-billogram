<?php

/**
 * Org/ssn from on checkout
 *
 * @return string
*/

$output = '<fieldset id="billogram">';
	$output .= '<p class="form-row form-row-wide">';
		$output .= '<label for="' . esc_attr( $this->id ) . '">' . __( 'Personnr/Orgnr', WC_Billogram::$textdomain ) . ' <span class="required">*</span></label>';
		$output .= '<input id="' . esc_attr( $this->id ) . '" class="input-text" type="text" maxlength="20" autocomplete="off" placeholder="'. __( 'Ex. ÅÅMMDD-XXXX', WC_Billogram::$textdomain ) .'" name="org_no" />';
	$output .= '</p>';

	$output .= '<div class="clear"></div>';

$output .= '</fieldset>';

if( $description = $this->get_description() ) 
{
	$output .= wpautop( wptexturize( $description ) );
}

echo $output;
