# 🛡️ Nessus Conector for GLPI

[English](README.md) / [Português do Brasil](readme_pt-br.md)

![Nessus Conector](images/nessus-logo.png)

**Nessus Conector** is a GLPI plugin that imports vulnerability data from Tenable products, links findings to GLPI assets, keeps synchronization history, and helps security teams create tickets from vulnerabilities.

This fork adds support for two sources:

| Source | What it imports | API family |
| --- | --- | --- |
| 🖥️ Nessus / Tenable VM | scans, hosts and vulnerability plugins | `/scans` |
| 🌐 Tenable WAS | web application scan executions and findings | `/was/v2` |

## ✨ Highlights

- 🔐 Configure Tenable API URL, access key and secret key inside GLPI.
- 🔎 Browse available Nessus / Tenable VM scans directly from GLPI.
- 🌐 Browse Tenable WAS configurations and scan executions directly from GLPI.
- ⚙️ Queue scan synchronization from the GLPI interface.
- 🧩 Match imported targets with GLPI assets.
- 📊 View current vulnerabilities per scan or consolidated across scans.
- 🕰️ Keep synchronization history and first/last seen dates.
- 🎫 Create individual or grouped GLPI tickets from findings.
- 👤 Manage plugin rights per GLPI profile.
- 🧪 Compatible with PHP 8.5 deprecation behavior for cURL close handling.

## 🖼️ Screenshots

### Plugin Screens

![Nessus Conector screen 1](images/print1.png)

![Nessus Conector screen 2](images/print2.png)

### API Configuration

![Nessus Conector configuration](images/print3.png)

## ✅ Requirements

- GLPI 11.0.x
- PHP cURL extension enabled
- Tenable API credentials
- A user profile with the plugin rights enabled

Tested with:

```text
PHP 8.5
```

The plugin declares compatibility with:

```text
GLPI >= 11.0.0 and < 11.1.0
```

## 🚀 Installation

Place the plugin directory inside GLPI:

```text
plugins/nessusglpi
```

Then install and activate it from GLPI, or use the CLI:

```bash
php bin/console glpi:plugin:install --force --username=glpi --no-interaction nessusglpi
php bin/console glpi:plugin:activate --no-interaction nessusglpi
php bin/console cache:clear
```

After installing, open your GLPI profile and enable the **Nessus Conector** rights.

## 🔑 Configuration

Go to:

```text
Plugins -> Nessus Conector -> Configuration
```

Fill in:

| Field | Example |
| --- | --- |
| API URL for Tenable Cloud | `https://cloud.tenable.com` |
| API URL for local Nessus | `https://nessus.example.local:8834` |
| Access key | Your Tenable access key |
| Secret key | Your Tenable secret key |
| Timeout | `30` |
| Asset types for matching | Computer, Network equipment, Printer, Phone, Unmanaged |

Use the right test button:

- **Test Nessus/VM connection** for Nessus / Tenable VM.
- **Test WAS connection** for Tenable Web App Scanning.

## 🖥️ Nessus / Tenable VM Workflow

1. Go to:

   ```text
   Plugins -> Nessus Conector -> Scans
   ```

2. Click **Browse Nessus / Tenable VM scans**.

3. Pick a scan and click **Use this scan**.

4. The scan form will be filled with:

   ```text
   Source: Nessus / Tenable VM
   Scan ID: numeric scan ID
   ```

5. Save the scan.

6. Keep the scan list open while the queued synchronization is processed.

For VM scans, prefer the numeric `id` returned by `/scans`.

## 🌐 Tenable WAS Workflow

1. Go to:

   ```text
   Plugins -> Nessus Conector -> Scans
   ```

2. Click **Browse Tenable WAS scans**.

3. Select a WAS configuration.

4. Select a scan execution.

5. Click **Use this scan**.

6. The scan form will be filled with:

   ```text
   Source: Tenable WAS
   Scan ID: WAS scan execution UUID
   ```

7. Save the scan.

8. Keep the scan list open while the queued synchronization is processed.

For WAS, use the scan execution UUID, not the configuration ID.

## 🔄 Synchronization

The plugin stores queued synchronization jobs in the database.

Current behavior:

1. Creating or syncing a scan queues a job.
2. The scan list page detects pending jobs.
3. The page calls an AJAX endpoint to process the next job.
4. Imported data is stored in plugin tables.

Important: this is not a permanent background worker. Keep the scan list open while a job is queued, or implement a CLI/cron worker around the same service later.

## 🧩 Asset Matching

The plugin tries to match imported targets with GLPI assets using configured item types.

Current matching strategy:

- hostname
- FQDN
- GLPI asset `name`

For Tenable WAS, the web application URL is converted into a target hostname/domain before matching.

## 🎫 Tickets

From vulnerability screens, you can create:

- individual tickets for one vulnerability;
- grouped tickets for the same vulnerability affecting multiple assets;
- pending-host tickets when a target was imported but not matched to a GLPI asset.

Ticket content includes vulnerability details, severity, target, scan reference and remediation details when available.

## 👤 Profile Rights

The plugin creates these GLPI rights:

| Right | Purpose |
| --- | --- |
| `plugin_nessusglpi_scan` | View/create/update scans and process synchronization |
| `plugin_nessusglpi_config` | View/update API configuration |
| `plugin_nessusglpi_vulnerability` | View vulnerabilities and imported hosts |
| `plugin_nessusglpi_ticket` | Ticket link rights declared by the plugin |

If permissions were changed while a user was logged in, log out and log in again to refresh the GLPI session rights.

## ⚠️ Data Retention Notice

Do not uninstall the plugin if you want to keep imported data.

The uninstall script may remove plugin tables, including:

- configured scans;
- imported hosts;
- imported vulnerabilities;
- synchronization history;
- plugin ticket links;
- API configuration.

Use plugin deactivation if you only want to temporarily disable it.

## 🧰 Useful CLI Commands

```bash
php bin/console glpi:plugin:list
php bin/console glpi:plugin:install --force --username=glpi --no-interaction nessusglpi
php bin/console glpi:plugin:activate --no-interaction nessusglpi
php bin/console cache:clear
```

Validate a PHP file:

```bash
php -l plugins/nessusglpi/src/TenableWasClient.php
```

## 📚 Extra Documentation

A detailed step-by-step technical guide is available in:

```text
COMO_FUNCIONA.md
```

## 🤝 Contributing

Contributions are welcome through forks and pull requests.

Suggested contribution flow:

1. Fork the repository.
2. Create a feature branch.
3. Commit focused changes.
4. Open a pull request with clear testing notes.

## 📄 License

See the repository license file for details.
