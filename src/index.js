// Import the WordPress element render function
import { render } from '@wordpress/element';
// Import the custom React component
import Extendsecuritysettings from './componets/extendsecuritysettings';

// ID for the container div where the React component will be rendered
const ROOT_ID = "loginpress-extend-security-settings";

/**
 * Inject the Extendsecuritysettings React component
 * after the "Force Password Reset" field in the settings page.
 */
function injectField() {
    // Select the target field where we want to insert our component
    const forceResetField = document.querySelector(
        '.loginpress-setting-field[data-label="Force Password Reset"]'
    );

    // If the target field is not found, exit early
    if (!forceResetField) return;

    // Prevent multiple injections if container already exists
    if (document.getElementById(ROOT_ID)) return;

    // Create a container div for the React component
    const container = document.createElement("div");
    container.id = ROOT_ID;

    // Insert the container after the Force Password Reset field
    forceResetField.insertAdjacentElement("afterend", container);

    // Render the React component into the container
    render(<Extendsecuritysettings />, container);
}

/**
 * Use a MutationObserver to detect DOM changes
 * and inject the component dynamically if the target field is added later.
 */
const observer = new MutationObserver(() => {
    injectField();
});

// Observe changes in the body and its descendants
observer.observe(document.body, {
    childList: true,
    subtree: true
});

// Initial injection in case the target field is already present
injectField();