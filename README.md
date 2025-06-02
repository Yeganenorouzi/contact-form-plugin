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

- Endpoint: `/wp-json/contact-form/v1/messages`

**Note:** To access the REST API endpoints, you need to install and activate the "Basic Authentication" plugin for WordPress. This plugin enables HTTP Basic Authentication for the WordPress REST API, allowing external applications to authenticate and access the API endpoints.

You can install the Basic Auth plugin from:
-  download from: https://github.com/WP-API/Basic-Auth.git

After installing Basic Auth, you can authenticate API requests using your WordPress username and password.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Basic Authentication plugin (for REST API access)


## Author

Yegane Norouzi 

## Support

For support, please create an issue in the GitHub repository. 