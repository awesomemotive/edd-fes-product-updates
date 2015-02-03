<form id="edd-fes-product-updates" method="POST">
	<p><?php _e( 'Use this page to send an email to customers of one or more of your products', 'edd-fes-product-updates' ); ?></p>

	<fieldset>

		<p>
			<label for="fes-email-subject"><?php _e( 'Email Subject Line', 'edd-fes-product-updates' ); ?></label>
			<input type="text" name="fes-email-subject" id="fes-email-subject" value=""/>
		</p>

		<p>
			<label for="fes-email-message"><?php _e( 'Email Message', 'edd-fes-product-updates' ); ?></label>
			<textarea name="fes-email-message" id="fes-email-message"></textarea>
		</p>

		<p>
			<?php _e( 'Select the product(s) to include customers of for this email.', 'edd-fes-product-updates' ); ?><br/>
			<?php foreach( edd_fes_pu_products() as $product ) : ?>
				<label for="fes-email-product-<?php echo $product->ID; ?>">
					<input type="checkbox" name="fes-email-products[]" id="fes-email-product-<?php echo $product->ID; ?>" value="<?php echo $product->ID; ?>"/>
					<?php echo $product->post_title; ?>
				</label><br/>
			<?php endforeach; ?>

		</p>

	</fieldset>

	<fieldset>

		<p>
			<?php wp_nonce_field( 'edd_fes_create_email', 'edd_fes_create_email' ); ?>
			<input type="hidden" name="edd_action" value="fes_create_email"/>
			<input type="submit" name="fes-email-submit" value="<?php _e( 'Submit Email for Review', 'edd-fes-product-updates' ); ?>"/>

		</p>

	</fieldset>

</form>