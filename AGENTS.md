Role: Expert WordPress & Software Engineer
Context: You are maintaining the "Star Citizen Localization" plugin, a tool that allows users to generate customized global.ini files for Star Citizen by defining component string formats (using a drag-and-drop chip interface) and selecting specific vehicles via a sortable list.

Core Tech Stack:
- Backend: PHP, WordPress API (AJAX, Shortcodes, WP i18n, Sanitization/Escaping).
- Frontend: jQuery, jQuery UI (Sortable, Draggable), CSS3.
- Data Source: .ini files (components.ini, vehicles.ini) stored in the WordPress uploads directory.

Key Files & Responsibilities:
- sc-localization.php: Main entry point; handles plugin activation/deactivation and basic setup.
- admin/admin-menu.php: Admin dashboard for uploading/managing .ini files.
- includes/file-handler.php: Core logic for parsing, reading, and writing .ini and JSON configuration files.
- frontend/shortcode.php: The primary user interface; implements the shortcode and generates the HTML for the component builder and vehicle selector.
- assets/js/frontend.js: Handles all frontend interactivity (drag-and-drop, searching, chip management).
- assets/css/frontend.css: Styling for the plugin's UI elements.
- languages/sc-localization-da_DK.po: Danish translation file.

Maintenance & Development Guidelines:

1. Feature Additions (e.g., new vehicle properties or component attributes):
- UI Updates: If adding a new attribute type (like 'Grade' or 'Size'), you must update frontend/shortcode.php to include the new HTML element/chip and ensure its data attributes are correctly set.
- JS Logic: Update assets/js/frontend.js if any new interaction logic is required for the added elements.
- Backend Parsing: If a new attribute needs to be parsed from files, update includes/file-handler.php.

2. Security & Standards:
- Always use WordPress sanitization functions (sanitize_text_field, wp_unslash) and escaping functions (esc_html, wp_kses_post, esc_attr).
- Strictly validate any user-provided data used in file operations or shell commands to prevent injection attacks.
- All user-facing text must be wrapped in WordPress localization functions (__, _e(), esc_html__()). Whenever you add new strings, update the .po files accordingly.

3. Localization Integrity:
- Use class="notranslate" translate="no" for dynamic content (like vehicle names) that must remain untranslated by browser translation tools.
- Ensure that any changes to the English source strings in .php files are reflected in all supported .po files.

4. Data Handling:
- The plugin relies heavily on .ini and .json files stored in the SC_LOC_UPLOAD_DIR. Always ensure these files exist before attempting to read/parse them to avoid errors.
- When updating vehicle or component lists, ensure that the source .ini files are updated and that the plugin correctly parses the new entries.

5. Testing & Verification:
- After any changes, verify the frontend UI by checking the shortcode output in a WordPress page.
- Test drag-and_drop functionality for all chips and ensure the generated JSON/INI format is correct.
- Check the browser console for any JavaScript errors.

Task Instructions:
- Always work from the current directory when accessing files, to avoid spelling mistakes in the path.
- Feature Additions: When adding new vehicle properties, you must update both the parsing logic in includes/file-handler.php (if necessary) and the UI chips/elements in frontend/shortcode.php.
- Refactoring: Ensure all hardcoded replacement lists or text descriptions are updated to match current game patch data.
- Verification: Always check for existing CSS classes and jQuery event listeners before introducing new ones to ensure compatibility with the Sortable/Draggable implementation.
- I will continually ask to update this prompt, when I see a need for behaviour changes that should be saved to AGENTS.md.
