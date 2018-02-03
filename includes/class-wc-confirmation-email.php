<?php

if ( ! defined( 'ABSPATH' ) ) exit;
// Exit if accessed directly






/**
custom Confirmation WooCommerce Email class
 *
 * @since 0.1
 * @extends \WC_Email
 */
class WC_Confirmation_Email extends WC_Email {
	// 	https://u	sersinsights.com/woocommerce-send-custom-email/
	
	
	
	/**
	* Set email defaults
			*
			* @since 0.1
			*/
			public function __construct() {
		$this->id = 'wc_order_confirmation';
		$this->title = 'Confirmation - Workshop Details';
		$this->description = 'Confirmation - Workshop Details';
		$this->heading = 'Confirmation - Workshop Details';
		$this->subject = 'Confirmation - Workshop Details';
		$this->customer_email = true;
		$this->template_html  = 'emails/customer-confirmation-order.php';
		$this->template_plain = 'emails/plain/customer-completed-order.php';

		add_action( 'woocommerce_order_status_completed_notification', array( $this, 'trigger' ), 10, 1 );

		// Call parent constructor to load any other defaults not explicity defined here
		parent::__construct();
	}
	/**
	 * Determine if the email should actually be sent and setup email merge variables
	 *
	 * @since 0.1
	 * @param int $order_id
	 */
	public function trigger( $order_id ) {
		// bail if no order ID is present
		if ( ! $order_id )
			return;
			
			// setup order object
		$this->object = new WC_Order( $order_id );
		// bail if shipping method is not expedited
		
		if ( ! wc_donna_farhi_category_is_in_the_order( $this->object ) ) {
			error_log('not sending confirmation, no appropriate products in order');
			return;
		}

	  $this->recipient = $this->object->get_billing_email();

			// replace variables in the subject/headings
		$this->find[] = '{
			order_date
		}
		';
		$this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
		$this->find[] = '{
			order_number
		}
		';
		$this->replace[] = $this->object->get_order_number();

		if ( ! $this->is_enabled() || ! $this->get_recipient() )
      return;

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}
	/**
	 * get_content_html function.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading()
		) );
		return ob_get_clean();
	}
	/**
	 * get_content_plain function.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading()
		) );
		return ob_get_clean();
	}
	/**
	 * Initialize Settings Form Fields
	 *
	 * @since 2.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable this email notification',
				'default' => 'yes'
			),
			'subject'    => array(
				'title'       => 'Subject',
				'type'        => 'text',
				'description' => sprintf( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', $this->subject ),
				'placeholder' => '',
				'default'     => ''
			),
			'heading'    => array(
				'title'       => 'Email Heading',
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.' ), $this->heading ),
				'placeholder' => '',
				'default'     => ''
			),
			'email_type' => array(
				'title'       => 'Email type',
				'type'        => 'select',
				'description' => 'Choose which format of email to send.',
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => array(
					'plain'	    => __( 'Plain text', 'woocommerce' ),
					'html' 	    => __( 'HTML', 'woocommerce' ),
					'multipart' => __( 'Multipart', 'woocommerce' ),
				)
			)
		);
	}
} // end \WC_Confirmation_Email class