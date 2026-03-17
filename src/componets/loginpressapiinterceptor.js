// Import WordPress API fetch utility
import apiFetch from '@wordpress/api-fetch';

/**
 * Interceptor for LoginPress settings API requests
 *
 * This middleware intercepts requests to 'loginpress/v1/settings' to:
 * 1. Inject custom values when saving settings (POST requests)
 * 2. Sync settings to global window variables when loading (GET requests)
 */
apiFetch.use((options, next) => {
    const path = options.path || '';
    const method = options.method || 'GET';

    // Only intercept requests to the LoginPress settings endpoint
    if (path.includes('loginpress/v1/settings')) {

        // ----- POST REQUEST: Inject custom values before saving -----
        if (method === 'POST' && options.data) {
            options.data.disallow_last_password = window.lp_custom_disallow_password || 'off';
            options.data.enable_reminder_emails = window.lp_custom_enable_reminder || 'off';
            options.data.reminder_days_before_expiry = window.lp_custom_reminder_days || 3;
        }

        // Continue with the request
        return next(options).then((response) => {

            // ----- GET REQUEST: Sync values after loading settings -----
            if (method === 'GET') {
                const disallow = response?.settings?.disallow_last_password || 'off';
                const reminder = response?.settings?.enable_reminder_emails || 'off';
                const reminderDays = response?.settings?.reminder_days_before_expiry || 3;

                // Store synced values in global window object
                window.lp_custom_disallow_password = disallow;
                window.lp_custom_enable_reminder = reminder;
                window.lp_custom_reminder_days = reminderDays;

                console.log('[Interceptor] Synced:', { disallow, reminder, reminderDays });

                // Dispatch a custom event to notify other scripts/components
                setTimeout(() => {
                    window.dispatchEvent(
                        new CustomEvent('lp_settings_synced', {
                            detail: { disallow, reminder, reminderDays },
                        })
                    );
                }, 50);
            }

            // Return the response to continue normal flow
            return response;
        });
    }

    // If not the target endpoint, continue without modification
    return next(options);
});