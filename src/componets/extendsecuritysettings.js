import { useState, useEffect } from '@wordpress/element';
// Import the API interceptor to sync settings with the server
import './loginpressapiinterceptor';

/**
 * ExtendSecuritySettings Component
 *
 * Renders additional security settings for LoginPress:
 * 1. Disallow last password reuse
 * 2. Enable reminder emails
 * 3. Reminder days before expiry
 *
 * This component is dynamically injected into the LoginPress settings page.
 */
export default function ExtendSecuritySettings() {
    // ----------------------
    // Component state
    // ----------------------
    const [forceReset, setForceReset] = useState(false); // Tracks "Force Password Reset" checkbox
    const [disallowLast, setDisallowLast] = useState(false); // Tracks "Disallow Last Password"
    const [enableReminder, setEnableReminder] = useState(false); // Tracks "Enable Reminder Emails"
    const [reminderDays, setReminderDays] = useState(3); // Tracks reminder days input

    // ----------------------
    // Observe Force Password Reset checkbox
    // ----------------------
    useEffect(() => {
        const forceCheckbox = document.querySelector('#loginpress-setting-enable_password_reset');
        if (!forceCheckbox) return;

        const handler = () => setForceReset(forceCheckbox.checked);
        // Initialize state
        setForceReset(forceCheckbox.checked);

        // Listen for changes to the Force Reset checkbox
        forceCheckbox.addEventListener('change', handler);

        // Cleanup listener on unmount
        return () => forceCheckbox.removeEventListener('change', handler);
    }, []);

    // ----------------------
    // Listen for API-synced settings
    // ----------------------
    useEffect(() => {
        const handler = (e) => {
            // Update component state based on API-synced values
            setDisallowLast(e.detail.disallow === 'on');
            setEnableReminder(e.detail.reminder === 'on');
            setReminderDays(e.detail.reminderDays);
        };

        // Listen for custom event dispatched by apiFetch interceptor
        window.addEventListener('lp_settings_synced', handler);

        // Cleanup listener on unmount
        return () => window.removeEventListener('lp_settings_synced', handler);
    }, []);

    // If Force Reset is OFF, hide the extended settings
    if (!forceReset) return null;

    return (
        <div className="my-loginpress-extension-root">
            {/* ----------------------
                Disallow Last Password Setting
            ---------------------- */}
            <div className="loginpress-setting-field loginpress-setting-field-checkbox">
                <label className="loginpress-setting-label" htmlFor="disallow_last_password">
                    Disallow Last Password
                </label>
                <div className="loginpress-setting-input" style={{ flex: "1 1 0%" }}>
                    <span className="loginpress-checkbox">
                        <input
                            id="disallow_last_password"
                            type="checkbox"
                            checked={disallowLast}
                            onChange={(e) => {
                                setDisallowLast(e.target.checked);
                                // Update global variable to sync with API
                                window.lp_custom_disallow_password = e.target.checked ? 'on' : 'off';
                            }}
                        />
                    </span>
                    <label className="loginpress-label-des">
                        Users will not be allowed to reuse their last password.
                    </label>
                </div>
            </div>

            {/* ----------------------
                Enable Reminder Emails Setting
            ---------------------- */}
            <div className="loginpress-setting-field loginpress-setting-field-checkbox">
                <label className="loginpress-setting-label" htmlFor="enable_reminder_emails">
                    Enable Reminder Emails
                </label>
                <div className="loginpress-setting-input" style={{ flex: "1 1 0%" }}>
                    <span className="loginpress-checkbox">
                        <input
                            id="enable_reminder_emails"
                            type="checkbox"
                            checked={enableReminder}
                            onChange={(e) => {
                                setEnableReminder(e.target.checked);
                                // Update global variable to sync with API
                                window.lp_custom_enable_reminder = e.target.checked ? 'on' : 'off';
                            }}
                        />
                    </span>
                </div>
            </div>

            {/* ----------------------
                Reminder Days Before Expiry
                Only visible if "Enable Reminder Emails" is ON
            ---------------------- */}
            {enableReminder && (
                <div className="loginpress-setting-field loginpress-setting-field-number">
                    <label className="loginpress-setting-label" htmlFor="reminder_days_before_expiry">
                        Reminder Days Before Expiry
                    </label>
                    <div className="loginpress-setting-input" style={{ flex: "1 1 0%" }}>
                        <input
                            id="reminder_days_before_expiry"
                            type="number"
                            className="loginpress-input"
                            min="1"
                            max="365"
                            value={reminderDays}
                            onChange={(e) => {
                                const val = parseInt(e.target.value, 10) || 1;
                                setReminderDays(val);
                                // Update global variable to sync with API
                                window.lp_custom_reminder_days = val;
                            }}
                        />
                        <p className="loginpress-description">
                            Number of days before users receive a password reset reminder.
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
}