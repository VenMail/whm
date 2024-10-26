# WHM VenMail Plugin

A comprehensive email solution integration for WHM/cPanel that allows hosting providers to offer VenMail email services as an addon during domain registration.

## Table of Contents
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [CLI Tool](#cli-tool)
- [Troubleshooting](#troubleshooting)
- [API Reference](#api-reference)

## Features

- Automatic email service provisioning
- DNS record management
- Bulk domain processing
- User-friendly admin interface
- Command-line interface for automation
- Detailed reporting and monitoring
- Integration with domain registration
- Automatic DNS configuration

## Requirements

- WHM/cPanel server
- PHP 7.4 or higher
- Composer
- Valid VenMail Partner API credentials
- Root access to WHM server
- Required PHP extensions:
  - curl
  - json
  - openssl
  - dom
  - mbstring

## Installation

### 1. Download the Plugin

```bash
cd /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins
git clone https://github.com/venmail/whm-plugin.git venmail
cd venmail
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Set Permissions

```bash
chown -R root:root /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail
chmod 755 /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail
chmod 644 /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/config/*
```

### 4. Register Plugin with WHM

```bash
/usr/local/cpanel/bin/register_cpanelplugin /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/install.json
```

### 5. Create Required Directories

```bash
mkdir -p /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/{logs,cache,data}
chmod 755 /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/{logs,cache,data}
```

## Configuration

### 1. API Credentials Setup

Create/edit `/usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/config/venmail.conf`:

```ini
api_base_url = "https://api.venmail.io"
api_token = "YOUR_PARTNER_API_TOKEN"
partner_id = "YOUR_PARTNER_ID"
debug_mode = false
cache_ttl = 3600
```

### 2. Addon Settings

Edit `/usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/config/addon_settings.conf`:

```ini
enabled = "true"
default_plan_id = "1"
pricing_mode = "monthly"
auto_dns_setup = "true"
```

### 3. Verify Installation

```bash
php /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/venmail-cli.php verify-install
```

## Usage

### Admin Interface

1. Log in to WHM
2. Navigate to Plugins → VenMail Email Manager
3. Configure settings and manage domains

### Domain Registration Integration

The plugin automatically integrates with:
- Domain registration process
- Package/plan selection
- DNS zone management

### Bulk Operations

1. Prepare CSV file with domains:
```csv
domain,organization,fullname,email,plan_id
example.com,Example Inc,John Doe,admin@example.com,1
```

2. Use CLI tool for bulk import:
```bash
php venmail-cli.php bulk-import domains.csv
```

## CLI Tool

### Basic Commands

```bash
# Enable addon
php venmail-cli.php enable

# Setup domain
php venmail-cli.php setup example.com --plan 1

# Verify domain setup
php venmail-cli.php verify example.com

# Sync all domains
php venmail-cli.php sync

# Show status
php venmail-cli.php status [domain]

# Bulk import
php venmail-cli.php bulk-import domains.csv
```

### Scheduled Tasks

Add to crontab:

```cron
# Sync domains daily
0 0 * * * /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/venmail-cli.php sync

# Verify DNS records
0 */6 * * * /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/venmail-cli.php verify-all
```

## Troubleshooting

### Log Locations

- Main log: `/usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/logs/venmail.log`
- CLI log: `/usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/logs/cli.log`
- Error log: `/usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/logs/error.log`

### Common Issues

1. **API Connection Failed**
   - Verify API credentials
   - Check network connectivity
   - Ensure API endpoint is accessible

2. **DNS Setup Failed**
   - Verify WHM API access
   - Check domain zone exists
   - Ensure no conflicting records

3. **Addon Not Showing**
   - Verify plugin registration
   - Check file permissions
   - Clear WHM cache

### Debug Mode

Enable debug logging:

```bash
sed -i 's/debug_mode = false/debug_mode = true/' /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail/config/venmail.conf
```

## API Reference

### Endpoints

The plugin uses the following VenMail API endpoints:

- `/partners/domains` - Domain management
- `/partners/plans` - Plan retrieval
- `/partners/domains/{id}/subscribe` - Subscription management
- `/partners/domains/{id}/cancel` - Service cancellation

### WHM Integration Points

- Domain registration hooks
- DNS zone management
- Package management
- User creation events

## Security

- API credentials are stored securely
- All requests use HTTPS
- Input validation on all user inputs
- Rate limiting implemented
- Access log monitoring

## Updates

To update the plugin:

```bash
cd /usr/local/cpanel/whostmgr/docroot/cgi/addon_plugins/venmail
git pull
composer update
php venmail-cli.php upgrade
```

## Support

For support:
- Email: hello@venmail.io
- Documentation: [API Docs](https://m.venmail.io/api/integration/docs#tag/Partner/operation/getDomainList)
- GitHub Issues: https://github.com/venmail/whm-plugin/issues

## License

This plugin is licensed under the MIT License. See LICENSE file for details.