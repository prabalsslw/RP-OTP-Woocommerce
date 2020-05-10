<?php 
	add_action( 'register_form', 'rp_registration_form' );
	
	function rp_registration_form() {
		?>
		<p>
			<label for="rp_phone_number">
				<?php esc_html_e( 'Phone Number', 'rp_phone_number' ) ?> <br/>
				<input type="text" class="regular_text" name="rp_phone_number" autocomplete="off" />
			</label>
		</p>

		<?php
	}

	add_filter( 'registration_errors', 'rp_registration_errors', 10, 3 );

	function rp_registration_errors( $errors, $sanitized_user_login, $user_email ) {

		if ( empty( $_POST['rp_phone_number'] ) ) {
			$errors->add( 'rp_phone', __( '<strong>ERROR</strong>: Phone number is missing.', 'rp' ) );
		}
		else if(!is_numeric($_POST['rp_phone_number']))
		{
			$errors->add( 'rp_phone', __( '<strong>ERROR</strong>: Phone number isn’t correct.', 'rp' ) );
		}

		return $errors;
	}

	add_action( 'user_register', 'rp_save_data' );

	function rp_save_data( $user_id ) {
		if ( ! empty( $_POST['rp_phone_number'] ) ) {
			update_user_meta( $user_id, 'rp_phone_number', trim( $_POST['rp_phone_number'] ) ) ;		
		}
	}
?>