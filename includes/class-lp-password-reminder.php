<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * LP_Password_Reminder Class
 *
 * Handles scheduling and sending email reminders for users whose passwords are about to expire.
 *
 * @package    LP_Password_Reminder
 * @subpackage LP_Password_Reminder/includes
 */
class LP_Password_Reminder {

    /**
     * Cron hook name for sending password expiration reminders
     *
     * @var string
     */
    private $cron_hook = 'lp_password_expiration_reminder';

    /**
     * Constructor
     *
     * Schedules the daily cron job and hooks the reminder function to the cron.
     */
    public function __construct() {
        // Schedule cron if not already scheduled
        if ( ! wp_next_scheduled( $this->cron_hook ) ) {
            wp_schedule_event( time(), 'daily', $this->cron_hook );
        }

        // Hook the reminder function to the cron job
        add_action( $this->cron_hook, [ $this, 'maybe_send_reminder_email' ] );
    }

    /**
     * Check if reminder emails should be sent
     *
     * This method checks plugin settings to determine if password reset
     * and reminder emails are enabled. If both are on, it triggers the
     * email sending process.
     */
    public function maybe_send_reminder_email() {
        $settings = get_option( 'loginpress_setting', [] );

        // Sanitize toggles
        $enable_reset            = isset( $settings['enable_password_reset'] ) ? sanitize_text_field( $settings['enable_password_reset'] ) : 'off';
        $enable_reminder_emails  = isset( $settings['enable_reminder_emails'] ) ? sanitize_text_field( $settings['enable_reminder_emails'] ) : 'off';

        // Exit if either feature is disabled
        if ( 'on' !== $enable_reset || 'on' !== $enable_reminder_emails ) {
            return;
        }

        // Both features are enabled, send reminders
        $this->send_reminder_email( $settings );
    }

    /**
     * Send password expiration reminder emails to users
     *
     * @param array $settings Plugin settings.
     */
    private function send_reminder_email( $settings ) {
        $reminder_days = isset( $settings['reminder_days_before_expiry'] ) ? absint( $settings['reminder_days_before_expiry'] ) : 3;

        // Roles eligible for password reset
        $roles_for_reset = isset( $settings['roles_for_password_reset'] ) && is_array( $settings['roles_for_password_reset'] )
            ? array_map( 'sanitize_text_field', $settings['roles_for_password_reset'] )
            : [];

        // Fetch users with the password changed meta
        $users = get_users( [
            'role__in'  => $roles_for_reset,
            'meta_key'  => '_lp_password_changed',
        ] );

        $emails = [];
        $now    = current_time( 'timestamp' );

        foreach ( $users as $user ) {
            $password_changed = get_user_meta( $user->ID, '_lp_password_changed', true );
            if ( ! $password_changed ) {
                continue;
            }

            $expiry_days = isset( $settings['reminder_days_before_expiry'] ) ? absint( $settings['reminder_days_before_expiry'] ) : 90;
            $expiry_time = strtotime( "+{$expiry_days} days", $password_changed );

            $days_left = ceil( ( $expiry_time - $now ) / DAY_IN_SECONDS );

            if ( $days_left < $reminder_days && $days_left > 0 ) {
                $emails[] = $user->user_email;
            }
        }

        if ( ! empty( $emails ) ) {
            $subject = __( 'Password Expiration Reminder', 'my-loginpress-extension' );
            $message = sprintf(
                __( 'Your password will expire within %d days. Please change it soon.', 'my-loginpress-extension' ),
                $reminder_days
            );

            $headers = [ 'Bcc: ' . implode( ',', $emails ) ];
            wp_mail( '', $subject, $message, $headers );
        }
    }
}