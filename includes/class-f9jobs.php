<?php
/**
 * F9jobs setup
 *
 * @package  F9jobs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main F9jobs Class.
 *
 * @class F9jobs
 */
final class F9jobs {

	/**
	 * F9jobs version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * The single instance of the class.
	 *
	 * @var F9jobs
	 */
	protected static $_instance = null;

	/**
	 * Main F9jobs Instance.
	 *
	 * Ensures only one instance of F9jobs is loaded or can be loaded.
	 *
	 * @static
	 * @see f9jobs()
	 * @return F9jobs - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * F9jobs Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();

		if ( defined( 'WPCF7_VERSION' ) ) {
			add_action( 'wpcf7_submit', array( $this, 'wpcf7_f9jobs_submit' ), 10, 2 );
			add_filter( 'wpcf7_skip_mail', array( $this, 'wpcf7_skip_mail' ), 10, 2 );
		}

		do_action( 'f9jobs_loaded' );
	}

	/**
	 * Define F9JOBS Constants.
	 */
	private function define_constants() {
		$this->define( 'F9JOBS_ABSPATH', dirname( F9JOBS_PLUGIN_FILE ) . '/' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {

		/**
		 * Core classes.
		 */
		include_once F9JOBS_ABSPATH . 'includes/f9jobs-core-functions.php';
		include_once F9JOBS_ABSPATH . 'includes/class-f9jobs-post-types.php';
		include_once F9JOBS_ABSPATH . 'includes/class-f9jobs-install.php';
	}

	public function wpcf7_f9jobs_submit( $contact_form, $result ) {

		if ( 'Trabalhe' === $contact_form->title() && 'mail_sent' === $result['status'] ) {
			$values = array();

			$fields = array(
				'your-name',
				'your-email',
				'ddd',
				'phone',
				'address',
				'number',
				'neighborhood',
				'city',
				'wishjob',
				'wishsalary',
				'birth',
				'school',
				'courses',
				'lastjob',
				'lastini',
				'lastend',
				'lastsalary',
				'aforejob',
				'aforeini',
				'aforeend',
				'aforesalary',
				'shift',
				'choice',
				'relay',
				'know',
			);

			foreach ( $fields as $field ) {
				$values[ $field ] = $this->wpcf7_f9jobs_get_value( $field, $contact_form );
			}

			$post_content = $this->application_content( $values );

			$postarr = array(
				'post_type'    => 'application',
				'post_title'   => sprintf( '%s', $values['your-name'] ),
				'post_content' => $post_content,
				'post_status'  => 'publish',
			);

			$post_id = wp_insert_post( $postarr );

			add_post_meta( $post_id, '_json_object', $values, true );

			$post = array(
				'ID'         => $post_id,
				'post_title' => sprintf( '#%s %s', $post_id, $values['your-name'] ),
			);

			wp_update_post( $post );

			if ( apply_filters( 'f9jobs_notify', true ) ) {
				$this->f9jobs_notify_new_candidate( $post_id, $values );
			}
		}

		do_action( 'wpcf7_after_f9jobs', $result );
	}

	public function wpcf7_f9jobs_get_value( $field, $contact_form ) {
		if ( empty( $field ) || empty( $contact_form ) ) {
			return false;
		}

		$value = '';

		$fields = array(
			'your-name',
			'your-email',
			'ddd',
			'phone',
			'address',
			'number',
			'neighborhood',
			'city',
			'wishjob',
			'wishsalary',
			'birth',
			'school',
			'courses',
			'lastjob',
			'lastini',
			'lastend',
			'lastsalary',
			'aforejob',
			'aforeini',
			'aforeend',
			'aforesalary',
			'shift',
			'choice',
			'relay',
			'know',
		);

		if ( in_array( $field, $fields ) ) {
			$templates = $contact_form->additional_setting( 'f9jobs_' . $field );

			if ( empty( $templates[0] ) ) {
				$template = sprintf( '[%s]', $field );
			} else {
				$template = trim( wpcf7_strip_quote( $templates[0] ) );
			}

			$value = wpcf7_mail_replace_tags( $template );
		}

		$value = apply_filters( 'wpcf7_f9jobs_get_value', $value, $field, $contact_form );

		return $value;
	}

	public function wpcf7_skip_mail( $skip_mail, $contact_form ) {
		if ( 'Trabalhe' === $contact_form->title() ) {
			$skip_mail = apply_filters( 'f9jobs_skip_mail', false );
		}
		return $skip_mail;
	}

	public function f9jobs_notify_new_candidate( $application_id, $values ) {

		$emails = array( get_option( 'admin_email' ) );
		$emails = apply_filters( 'f9jobs_notify_recipients', $emails );

		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$notify_message  = __( 'Uma nova candidatura foi registrada.', 'rineplast' ) . "\r\n\r\n";

		$notify_message  = $this->application_content_mail( $values ) . "\r\n\r\n";

		$notify_message .= __( 'Visite o painel:', 'rineplast' ) . "\r\n";
		$notify_message .= admin_url( 'edit.php?post_type=application' ) . "\r\n";

		$notify_message = apply_filters( 'f9jobs_notify_text', $notify_message );

		// translators: Candidate full name
		$subject = sprintf( __( 'Candidatura "%s"', 'rineplast' ), $values['your-name'] );
		$subject = apply_filters( 'f9jobs_notify_subject', $subject );

		$message_headers = '';
		$message_headers = apply_filters( 'f9jobs_notify_headers', $message_headers );

		foreach ( $emails as $email ) {
			wp_mail( $email, wp_specialchars_decode( $subject ), $notify_message, $message_headers );
		}

		return true;
	}

	public function application_content( $values ) {
		$age = date_i18n( 'Y', date_i18n( 'U' ) - strtotime( $values['birth'] ) ) - 1970;
		$age = $age . _n( ' ano', ' anos', $age, 'rineplast' );
		$values['birth'] = mysql2date( get_option( 'date_format' ), $values['birth'] );
		$text = sprintf( '%s (%s [%s]) <%s>', $values['your-name'], $age, $values['birth'], $values['your-email'] ) . "\n";
		$wishjob = isset( $values['wishjob'] ) && $values['wishjob'];
		if ( $wishjob ) {
			// translators: Role
			$text .= sprintf( __( 'Cargo pretendido: %s', 'rineplast' ), $values['wishjob'] );
		}
		$wishsalary = ( isset( $values['wishsalary'] ) && $values['wishsalary'] );
		if ( $wishsalary ) {
			$values['wishsalary'] = str_replace( '.', '', $values['wishsalary'] );
			$values['wishsalary'] = str_replace( ',', '.', $values['wishsalary'] );
			$values['wishsalary'] = round( $values['wishsalary'] );
			$values['wishsalary'] = number_format( $values['wishsalary'], 0, ',', '.' );
			if ( $values['wishsalary'] > 0 ) {
				// translators: Currency value
				$text .= sprintf( __( ' (R$%s)', 'rineplast' ), $values['wishsalary'] );
			}
		}
		if ( $wishjob || $wishsalary ) {
			$text .= "\n";
		}
		if ( isset( $values['school'] ) && $values['school'] ) {
			$values['school'] = str_replace( 'Ensino Superior', 'Superior', $values['school'] );
			// translators: School level
			$text .= sprintf( __( 'Escolaridade: %s', 'rineplast' ), $values['school'] ) . "\n\n";
		}

		if ( isset( $values['courses'] ) && $values['courses'] ) {
			// translators: Courses list
			$text .= sprintf( __( "Cursos:\n%s", 'rineplast' ), $values['courses'] ) . "\n\n";
		}

		$lastjob = ( isset( $values['lastjob'] ) && $values['lastjob'] );
		$lastsalary = ( isset( $values['lastsalary'] ) && $values['lastsalary'] );
		$lastini = ( isset( $values['lastini'] ) && $values['lastini'] );
		$lastend = ( isset( $values['lastend'] ) && $values['lastend'] );
		$last = $lastjob || $lastsalary || $lastini || $lastend;
		$aforejob = ( isset( $values['aforejob'] ) && $values['aforejob'] );
		$aforesalary = ( isset( $values['aforesalary'] ) && $values['aforesalary'] );
		$aforeini = ( isset( $values['aforeini'] ) && $values['aforeini'] );
		$aforeend = ( isset( $values['aforeend'] ) && $values['aforeend'] );
		$afore = $aforejob || $aforesalary || $aforeini || $aforeend;
		if ( $last || $afore ) {
			$text .= __( 'Experiência:', 'rineplast' ) . "\n";
		}
		if ( $lastjob ) {
			$text .= sprintf( '%s', $values['lastjob'] );
		}
		if ( $lastsalary ) {
			$values['lastsalary'] = str_replace( '.', '', $values['lastsalary'] );
			$values['lastsalary'] = str_replace( ',', '.', $values['lastsalary'] );
			$values['lastsalary'] = round( $values['lastsalary'] );
			$values['lastsalary'] = number_format( $values['lastsalary'], 0, ',', '.' );
			if ( $values['lastsalary'] > 0 ) {
				$text .= sprintf( ' (R$%s)', $values['lastsalary'] );
			}
		}
		if ( $lastini ) {
			$values['lastini'] = mysql2date( get_option( 'date_format' ), $values['lastini'] );
			$text .= sprintf( ' %s', $values['lastini'] );
		}
		if ( $lastend ) {
			$values['lastend'] = mysql2date( get_option( 'date_format' ), $values['lastend'] );
			$text .= sprintf( '-%s', $values['lastend'] );
		}
		if ( $last ) {
			$text .= "\n";
		}
		if ( $aforejob ) {
			$text .= sprintf( '%s', $values['aforejob'] );
		}
		if ( $aforesalary ) {
			$values['aforesalary'] = str_replace( '.', '', $values['aforesalary'] );
			$values['aforesalary'] = str_replace( ',', '.', $values['aforesalary'] );
			$values['aforesalary'] = round( $values['aforesalary'] );
			$values['aforesalary'] = number_format( $values['aforesalary'], 0, ',', '.' );
			if ( $values['aforesalary'] > 0 ) {
				$text .= sprintf( ' (R$%s)', $values['aforesalary'] );
			}
		}
		if ( $aforeini ) {
			$values['aforeini'] = mysql2date( get_option( 'date_format' ), $values['aforeini'] );
			$text .= sprintf( ' %s', $values['aforeini'] );
		}
		if ( $aforeend ) {
			$values['aforeend'] = mysql2date( get_option( 'date_format' ), $values['aforeend'] );
			$text .= sprintf( '-%s', $values['aforeend'] );
		}
		if ( $afore ) {
			$text .= "\n";
		}
		if ( $last || $afore ) {
			$text .= "\n";
		}

		$shift = ( isset( $values['shift'] ) && $values['shift'] );
		if ( $shift ) {
			// translators: Yes or Not
			$text .= sprintf( __( 'Tem preferência de horário? %s', 'rineplast' ), $values['shift'] );
		}
		$choice = ( isset( $values['choice'] ) && $values['choice'] );
		if ( $choice ) {
			// translators: Option value
			$text .= sprintf( __( ' (%s)', 'rineplast' ), $values['choice'] );
		}
		$shift_choice = $shift || $choice;
		if ( $shift_choice ) {
			$text .= "\n";
		}
		$relay = ( isset( $values['relay'] ) && $values['relay'] );
		if ( $relay ) {
			// translators: Yes or Not
			$text .= sprintf( __( 'Sujeita-se a revezamento? %s', 'rineplast' ), $values['relay'] ) . "\n";
		}
		$know = ( isset( $values['know'] ) && $values['know'] );
		if ( $know ) {
			// translators: Selected value
			$text .= sprintf( __( 'Como conheceu nossa empresa? %s', 'rineplast' ), $values['know'] ) . "\n";
		}
		if ( $shift_choice || $relay || $know ) {
			$text .= "\n";
		}

		$text .= sprintf( '(%s) %s', $values['ddd'], $values['phone'] ) . "\n";
		$text .= sprintf( '%s', $values['address'] );
		if ( isset( $values['number'] ) && $values['number'] ) {
			$text .= sprintf( ', %s', $values['number'] );
		}
		if ( isset( $values['neighborhood'] ) && $values['neighborhood'] ) {
			$text .= sprintf( ' - %s', $values['neighborhood'] );
		}
		$text .= "\n";
		if ( isset( $values['city'] ) && $values['city'] ) {
			$text .= sprintf( '%s', $values['city'] );
		}

		return $text;
	}

	public function application_content_mail( $values ) {
		$age = date_i18n( 'Y', date_i18n( 'U' ) - strtotime( $values['birth'] ) ) - 1970;
		$age = $age . _n( ' ano', ' anos', $age, 'rineplast' );
		$values['birth'] = mysql2date( 'd/m/y', $values['birth'] );
		/* translators: Name */
		$text  = sprintf( __( '*Nome: *%s', 'rineplast' ), $values['your-name'] ) . "\n";
		$text .= sprintf( __( '*Data de nascimento: *%s', 'rineplast' ), $values['birth'] ) . "\n";
		$text .= sprintf( __( '*Idade: *%s', 'rineplast' ), $age ) . "\n";
		$text .= sprintf( __( '*E-mail: *%s', 'rineplast' ), $values['your-email'] ) . "\n";
		$wishjob = isset( $values['wishjob'] ) && $values['wishjob'];
		if ( $wishjob ) {
			// translators: Role
			$text .= sprintf( __( '*Cargo pretendido: *%s', 'rineplast' ), $values['wishjob'] ) . "\n";
		}
		$wishsalary = ( isset( $values['wishsalary'] ) && $values['wishsalary'] );
		if ( $wishsalary ) {
			$values['wishsalary'] = str_replace( '.', '', $values['wishsalary'] );
			$values['wishsalary'] = str_replace( ',', '.', $values['wishsalary'] );
			$values['wishsalary'] = round( $values['wishsalary'] );
			$values['wishsalary'] = number_format( $values['wishsalary'], 0, ',', '.' );
			if ( $values['wishsalary'] > 0 ) {
				// translators: Currency value
				$text .= sprintf( __( '*Salário predentido: *R$ %s', 'rineplast' ), $values['wishsalary'] ) . "\n";
			}
		}
		if ( isset( $values['school'] ) && $values['school'] ) {
			$values['school'] = str_replace( 'Ensino Superior', 'Superior', $values['school'] );
			// translators: School level
			$text .= sprintf( __( '*Escolaridade: *%s', 'rineplast' ), $values['school'] ) . "\n";
		}

		if ( isset( $values['courses'] ) && $values['courses'] ) {
			// translators: Courses list
			$text .= sprintf( __( "Cursos:\n%s", 'rineplast' ), $values['courses'] ) . "\n\n";
		}

		$lastjob = ( isset( $values['lastjob'] ) && $values['lastjob'] );
		$lastsalary = ( isset( $values['lastsalary'] ) && $values['lastsalary'] );
		$lastini = ( isset( $values['lastini'] ) && $values['lastini'] );
		$lastend = ( isset( $values['lastend'] ) && $values['lastend'] );
		$last = $lastjob || $lastsalary || $lastini || $lastend;
		$aforejob = ( isset( $values['aforejob'] ) && $values['aforejob'] );
		$aforesalary = ( isset( $values['aforesalary'] ) && $values['aforesalary'] );
		$aforeini = ( isset( $values['aforeini'] ) && $values['aforeini'] );
		$aforeend = ( isset( $values['aforeend'] ) && $values['aforeend'] );
		$afore = $aforejob || $aforesalary || $aforeini || $aforeend;
		if ( $last || $afore ) {
			$text .= __( 'Experiência:', 'rineplast' ) . "\n";
		}
		if ( $lastjob ) {
			$text .= sprintf( '%s', $values['lastjob'] );
		}
		if ( $lastsalary ) {
			$values['lastsalary'] = str_replace( '.', '', $values['lastsalary'] );
			$values['lastsalary'] = str_replace( ',', '.', $values['lastsalary'] );
			$values['lastsalary'] = round( $values['lastsalary'] );
			$values['lastsalary'] = number_format( $values['lastsalary'], 0, ',', '.' );
			if ( $values['lastsalary'] > 0 ) {
				$text .= sprintf( ' (R$%s)', $values['lastsalary'] );
			}
		}
		if ( $lastini ) {
			$values['lastini'] = mysql2date( get_option( 'date_format' ), $values['lastini'] );
			$text .= sprintf( ' %s', $values['lastini'] );
		}
		if ( $lastend ) {
			$values['lastend'] = mysql2date( get_option( 'date_format' ), $values['lastend'] );
			$text .= sprintf( '-%s', $values['lastend'] );
		}
		if ( $last ) {
			$text .= "\n";
		}
		if ( $aforejob ) {
			$text .= sprintf( '%s', $values['aforejob'] );
		}
		if ( $aforesalary ) {
			$values['aforesalary'] = str_replace( '.', '', $values['aforesalary'] );
			$values['aforesalary'] = str_replace( ',', '.', $values['aforesalary'] );
			$values['aforesalary'] = round( $values['aforesalary'] );
			$values['aforesalary'] = number_format( $values['aforesalary'], 0, ',', '.' );
			if ( $values['aforesalary'] > 0 ) {
				$text .= sprintf( ' (R$%s)', $values['aforesalary'] );
			}
		}
		if ( $aforeini ) {
			$values['aforeini'] = mysql2date( get_option( 'date_format' ), $values['aforeini'] );
			$text .= sprintf( ' %s', $values['aforeini'] );
		}
		if ( $aforeend ) {
			$values['aforeend'] = mysql2date( get_option( 'date_format' ), $values['aforeend'] );
			$text .= sprintf( '-%s', $values['aforeend'] );
		}
		if ( $afore ) {
			$text .= "\n";
		}
		if ( $last || $afore ) {
			$text .= "\n";
		}

		$shift = ( isset( $values['shift'] ) && $values['shift'] );
		if ( $shift ) {
			// translators: Yes or Not
			$text .= sprintf( __( 'Tem preferência de horário? %s', 'rineplast' ), $values['shift'] );
		}
		$choice = ( isset( $values['choice'] ) && $values['choice'] );
		if ( $choice ) {
			// translators: Option value
			$text .= sprintf( __( ' (%s)', 'rineplast' ), $values['choice'] );
		}
		$shift_choice = $shift || $choice;
		if ( $shift_choice ) {
			$text .= "\n";
		}
		$relay = ( isset( $values['relay'] ) && $values['relay'] );
		if ( $relay ) {
			// translators: Yes or Not
			$text .= sprintf( __( 'Sujeita-se a revezamento? %s', 'rineplast' ), $values['relay'] ) . "\n";
		}
		$know = ( isset( $values['know'] ) && $values['know'] );
		if ( $know ) {
			// translators: Selected value
			$text .= sprintf( __( 'Como conheceu nossa empresa? %s', 'rineplast' ), $values['know'] ) . "\n";
		}
		if ( $shift_choice || $relay || $know ) {
			$text .= "\n";
		}

		$text .= sprintf( '(%s) %s', $values['ddd'], $values['phone'] ) . "\n";
		$text .= sprintf( '%s', $values['address'] );
		if ( isset( $values['number'] ) && $values['number'] ) {
			$text .= sprintf( ', %s', $values['number'] );
		}
		if ( isset( $values['neighborhood'] ) && $values['neighborhood'] ) {
			$text .= sprintf( ' - %s', $values['neighborhood'] );
		}
		$text .= "\n";
		if ( isset( $values['city'] ) && $values['city'] ) {
			$text .= sprintf( '%s', $values['city'] );
		}

		return $text;
	}
}
