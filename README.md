# Contact Form Plugin

A WordPress plugin that provides a contact form with admin panel and REST API functionality.

## Features

- Custom contact form with name, email, subject, and message fields
- Admin panel to manage form submissions
- REST API endpoints for form submission
- Secure form handling and validation
- Database storage for submissions

## Installation

1. Download the plugin files
2. Upload the `contact-form-plugin` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to the plugin settings page to configure the form

## Usage

### Shortcode
Use the following shortcode to display the contact form on any page or post:
```
[contact_form]
```

### REST API
The plugin provides REST API endpoints for form submission:

- Endpoint: `/wp-json/contact-form/v1/submit`
- Method: POST
- Parameters:
  - name (string, required)
  - email (string, required)
  - subject (string, required)
  - message (string, required)

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## License

This plugin is licensed under the GPL v2 or later.

## Author

Your Name

## Support

For support, please create an issue in the GitHub repository. 