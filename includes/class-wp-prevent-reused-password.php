<?php
if ( ! class_exists( 'WP_Prevent_Reused_Passwords' ) ) {

    /**
     * WP_Prevent_Reused_Passwords Class
     *
     * Handles logic to prevent users from reusing their last passwords.
     *
     * @package WP_LoginPress
     * @subpackage WP_LoginPress/includes
     */
    class WP_Prevent_Reused_Passwords {

        /**
         * Option name for plugin settings
         *
         * @var string
         */
        private $option_name = 'loginpress_setting';

        /**
         * Number of previous passwords to store
         *
         * @var int
         */
        private $max_history = 6;

        /**
         * Constructor
         *
         * Hooks into WordPress password reset and profile update actions/filters.
         */
        public function __construct() {
            add_filter( 'validate_password_reset', [ $this, 'check_password_reuse' ], 10, 2 );
            add_action( 'user_profile_update_errors', [ $this, 'check_password_reuse_profile' ], 10, 3 );
            add_action( 'password_reset', [ $this, 'save_new_password' ], 10, 2 );
            add_action( 'profile_update', [ $this, 'save_new_password_profile' ], 10, 2 );
        }

        /**
         * Get plugin settings
         *
         * @return array
         */
        private function get_settings() {
            return get_option( $this->option_name, [] );
        }

        /**
         * Check if password reuse prevention is enabled
         *
         * @return bool
         */
        private function is_enabled() {
            $settings = $this->get_settings();

            return isset( $settings['disallow_last_password'], $settings['enable_password_reset'] ) &&
                ( $settings['disallow_last_password'] === 'on' ) &&
                ( $settings['enable_password_reset'] === 'on' );
        }

        /**
         * Get the user's password history
         *
         * @param int $user_id
         * @return array Array of hashed passwords
         */
        private function get_user_history( $user_id ) {
            $history = get_user_meta( $user_id, '_last_passwords', true );

            if ( ! is_array( $history ) ) {
                $history = [];
            }

            return $history;
        }

        /**
         * Add a new password to the user's history
         *
         * @param int    $user_id
         * @param string $password
         */
        private function add_password_to_history( $user_id, $password ) {
            $history = $this->get_user_history( $user_id );
            $hash    = wp_hash_password( $password );

            array_unshift( $history, $hash ); // Add new password at the beginning

            // Keep only the last $max_history passwords
            if ( count( $history ) > $this->max_history ) {
                $history = array_slice( $history, 0, $this->max_history );
            }

            update_user_meta( $user_id, '_last_passwords', $history );
        }

        /**
         * Check if password exists in the user's history
         *
         * @param int    $user_id
         * @param string $password
         * @return bool
         */
        private function password_in_history( $user_id, $password ) {
            $history = $this->get_user_history( $user_id );

            foreach ( $history as $hash ) {
                if ( wp_check_password( $password, $hash ) ) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Check password reuse on reset
         *
         * @param WP_Error $errors
         * @param WP_User  $user
         * @return WP_Error
         */
        public function check_password_reuse( $errors, $user ) {
            if ( ! $this->is_enabled() ) {
                return $errors;
            }

            if ( isset( $_POST['pass1'] ) && $this->password_in_history( $user->ID, $_POST['pass1'] ) ) {
                $errors->add( 'password_reuse', __( 'You cannot reuse a recently used password.', 'loginpress' ) );
            }

            return $errors;
        }

        /**
         * Check password reuse on profile update
         *
         * @param WP_Error $errors
         * @param bool     $update
         * @param WP_User  $user
         */
        public function check_password_reuse_profile( $errors, $update, $user ) {
            if ( ! $this->is_enabled() ) {
                return;
            }

            if ( ! empty( $_POST['pass1'] ) && $this->password_in_history( $user->ID, $_POST['pass1'] ) ) {
                $errors->add( 'password_reuse', __( 'You cannot reuse a recently used password.', 'loginpress' ) );
            }
        }

        /**
         * Save new password after reset
         *
         * @param WP_User $user
         * @param string  $new_pass
         */
        public function save_new_password( $user, $new_pass ) {
            // Update password changed timestamp
            update_user_meta( $user->ID, '_lp_password_changed', current_time( 'timestamp' ) );

            if ( $this->is_enabled() ) {
                $this->add_password_to_history( $user->ID, $new_pass );
            }
        }

        /**
         * Save new password after profile update
         *
         * @param int     $user_id
         * @param WP_User $old_user_data
         */
        public function save_new_password_profile( $user_id, $old_user_data ) {
            // Update password changed timestamp
            update_user_meta( $user_id, '_lp_password_changed', current_time( 'timestamp' ) );

            if ( $this->is_enabled() && ! empty( $_POST['pass1'] ) ) {
                $this->add_password_to_history( $user_id, $_POST['pass1'] );
            }
        }

    }
}