<?php

declare(strict_types=1);

use GlpiPlugin\Nessusglpi\NessusClient;
use GlpiPlugin\Nessusglpi\Scan;
use GlpiPlugin\Nessusglpi\TenableWasClient;

include('../../../inc/includes.php');

Session::checkRight(Scan::$rightname, CREATE);

function nessusglpi_browser_nested_value(array $data, array $path)
{
    $current = $data;

    foreach ($path as $part) {
        if (!is_array($current) || !array_key_exists($part, $current)) {
            return null;
        }

        $current = $current[$part];
    }

    return $current;
}

function nessusglpi_browser_first_string(array $data, array $paths): string
{
    foreach ($paths as $path) {
        $value = nessusglpi_browser_nested_value($data, $path);
        if (is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }
    }

    return '';
}

function nessusglpi_browser_pretty_date(string $value): string
{
    if ($value === '') {
        return '-';
    }

    if (is_numeric($value)) {
        $timestamp = (int) $value;
        if ($timestamp > 9999999999) {
            $timestamp = (int) floor($timestamp / 1000);
        }

        return $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : $value;
    }

    $timestamp = strtotime($value);
    return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : $value;
}

function nessusglpi_browser_item_id(array $row, array $paths): string
{
    return nessusglpi_browser_first_string($row, $paths);
}

function nessusglpi_browser_scan_id(array $row, string $source): string
{
    if ($source === Scan::SOURCE_NESSUS) {
        return nessusglpi_browser_item_id($row, [
            ['id'],
            ['scan_id'],
            ['uuid'],
            ['scan_uuid'],
        ]);
    }

    return nessusglpi_browser_item_id($row, [
        ['scan_id'],
        ['scan_uuid'],
        ['uuid'],
        ['id'],
    ]);
}

function nessusglpi_browser_config_id(array $row): string
{
    return nessusglpi_browser_item_id($row, [
        ['config_id'],
        ['config_uuid'],
        ['id'],
        ['uuid'],
    ]);
}

function nessusglpi_browser_name(array $row): string
{
    return nessusglpi_browser_first_string($row, [
        ['name'],
        ['scan_name'],
        ['title'],
        ['config_name'],
        ['application_name'],
        ['web_application_name'],
        ['target'],
        ['url'],
        ['application', 'name'],
        ['web_application', 'name'],
    ]);
}

function nessusglpi_browser_target(array $row): string
{
    return nessusglpi_browser_first_string($row, [
        ['url'],
        ['target'],
        ['application_url'],
        ['web_application_url'],
        ['asset', 'url'],
        ['application', 'url'],
        ['web_application', 'url'],
    ]);
}

function nessusglpi_browser_status(array $row): string
{
    return nessusglpi_browser_first_string($row, [
        ['status'],
        ['state'],
        ['scan_status'],
        ['enabled'],
    ]);
}

function nessusglpi_browser_date(array $row): string
{
    $value = nessusglpi_browser_first_string($row, [
        ['completed_at'],
        ['finished_at'],
        ['last_scan_time'],
        ['last_modification_date'],
        ['last_modification_date'],
        ['last_seen'],
        ['updated_at'],
        ['created_at'],
        ['start_time'],
        ['started_at'],
    ]);

    return nessusglpi_browser_pretty_date($value);
}

function nessusglpi_browser_form_url(string $source, string $scanId): string
{
    return 'scan.form.php?' . http_build_query([
        'scan_type' => $source,
        'scan_id'   => $scanId,
    ]);
}

function nessusglpi_browser_source_button(string $source, string $label, string $activeSource): string
{
    $class = $source === $activeSource ? 'btn btn-primary' : 'btn btn-outline-primary';
    $href = 'scan.browser.php?' . http_build_query(['source' => $source]);

    return '<a class="' . $class . '" href="' . Html::cleanInputText($href) . '">' . Html::cleanInputText($label) . '</a>';
}

function nessusglpi_browser_render_scans(array $scans, string $source): void
{
    if ($scans === []) {
        echo '<div class="alert alert-warning" role="alert">' . Html::cleanInputText(__('No scans were returned by the API.', 'nessusglpi')) . '</div>';
        return;
    }

    echo "<table class='tab_cadre_fixehov'>";
    echo '<tr>';
    echo '<th>' . Html::cleanInputText(__('Name')) . '</th>';
    echo '<th>' . Html::cleanInputText(__('Target', 'nessusglpi')) . '</th>';
    echo '<th>' . Html::cleanInputText(__('Scan ID', 'nessusglpi')) . '</th>';
    echo '<th>' . Html::cleanInputText(__('Status')) . '</th>';
    echo '<th>' . Html::cleanInputText(__('Last run', 'nessusglpi')) . '</th>';
    echo '<th>' . Html::cleanInputText(__('Actions')) . '</th>';
    echo '</tr>';

    foreach ($scans as $scanRow) {
        if (!is_array($scanRow)) {
            continue;
        }

        $scanId = nessusglpi_browser_scan_id($scanRow, $source);
        $name = nessusglpi_browser_name($scanRow);
        $target = nessusglpi_browser_target($scanRow);
        $status = nessusglpi_browser_status($scanRow);
        $date = nessusglpi_browser_date($scanRow);

        echo '<tr>';
        echo '<td>' . Html::cleanInputText($name !== '' ? $name : '-') . '</td>';
        echo '<td>' . Html::cleanInputText($target !== '' ? $target : '-') . '</td>';
        echo '<td><code>' . Html::cleanInputText($scanId !== '' ? $scanId : '-') . '</code></td>';
        echo '<td>' . Html::cleanInputText($status !== '' ? $status : '-') . '</td>';
        echo '<td>' . Html::cleanInputText($date) . '</td>';
        echo '<td>';
        if ($scanId !== '') {
            $href = nessusglpi_browser_form_url($source, $scanId);
            echo '<a class="btn btn-sm btn-primary" href="' . Html::cleanInputText($href) . '">' . Html::cleanInputText(__('Use this scan', 'nessusglpi')) . '</a>';
        } else {
            echo Html::cleanInputText(__('Missing scan ID', 'nessusglpi'));
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

function nessusglpi_browser_render_was_configs(array $configs): void
{
    if ($configs === []) {
        echo '<div class="alert alert-warning" role="alert">' . Html::cleanInputText(__('No WAS configurations were returned by the API.', 'nessusglpi')) . '</div>';
        return;
    }

    echo "<table class='tab_cadre_fixehov'>";
    echo '<tr>';
    echo '<th>' . Html::cleanInputText(__('Name')) . '</th>';
    echo '<th>' . Html::cleanInputText(__('Target', 'nessusglpi')) . '</th>';
    echo '<th>' . Html::cleanInputText(__('Config ID', 'nessusglpi')) . '</th>';
    echo '<th>' . Html::cleanInputText(__('Status')) . '</th>';
    echo '<th>' . Html::cleanInputText(__('Actions')) . '</th>';
    echo '</tr>';

    foreach ($configs as $configRow) {
        if (!is_array($configRow)) {
            continue;
        }

        $configId = nessusglpi_browser_config_id($configRow);
        $name = nessusglpi_browser_name($configRow);
        $target = nessusglpi_browser_target($configRow);
        $status = nessusglpi_browser_status($configRow);
        $href = 'scan.browser.php?' . http_build_query([
            'source'    => Scan::SOURCE_WAS,
            'config_id' => $configId,
        ]);

        echo '<tr>';
        echo '<td>' . Html::cleanInputText($name !== '' ? $name : '-') . '</td>';
        echo '<td>' . Html::cleanInputText($target !== '' ? $target : '-') . '</td>';
        echo '<td><code>' . Html::cleanInputText($configId !== '' ? $configId : '-') . '</code></td>';
        echo '<td>' . Html::cleanInputText($status !== '' ? $status : '-') . '</td>';
        echo '<td>';
        if ($configId !== '') {
            echo '<a class="btn btn-sm btn-primary" href="' . Html::cleanInputText($href) . '">' . Html::cleanInputText(__('View executions', 'nessusglpi')) . '</a>';
        } else {
            echo Html::cleanInputText(__('Missing config ID', 'nessusglpi'));
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

$source = Scan::normalizeSource($_GET['source'] ?? Scan::SOURCE_NESSUS);
$configId = trim((string) ($_GET['config_id'] ?? ''));
$error = null;
$items = [];

try {
    if ($source === Scan::SOURCE_WAS) {
        $client = new TenableWasClient();
        $items = $configId !== ''
            ? $client->getAllConfigScans($configId)
            : $client->getAllScanConfigs();
    } else {
        $items = (new NessusClient())->getAllScans();
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

Html::header(__('Browse Tenable scans', 'nessusglpi'), $_SERVER['PHP_SELF'], 'plugins', 'GlpiPlugin\\Nessusglpi\\Scan');

echo "<div class='card card-body'>";
echo '<h2>' . Html::cleanInputText(__('Browse Tenable scans', 'nessusglpi')) . '</h2>';
echo '<p class="d-flex gap-2">';
echo nessusglpi_browser_source_button(Scan::SOURCE_NESSUS, __('Nessus / Tenable VM', 'nessusglpi'), $source);
echo nessusglpi_browser_source_button(Scan::SOURCE_WAS, __('Tenable WAS', 'nessusglpi'), $source);
echo ' <a class="btn btn-outline-secondary" href="scan.php">' . Html::cleanInputText(__('Back')) . '</a>';
echo '</p>';

if ($error !== null) {
    echo '<div class="alert alert-danger" role="alert">' . Html::cleanInputText($error) . '</div>';
} elseif ($source === Scan::SOURCE_WAS && $configId === '') {
    echo '<h3>' . Html::cleanInputText(__('WAS configurations', 'nessusglpi')) . '</h3>';
    nessusglpi_browser_render_was_configs($items);
} else {
    if ($source === Scan::SOURCE_WAS) {
        $backHref = 'scan.browser.php?' . http_build_query(['source' => Scan::SOURCE_WAS]);
        echo '<p><a class="btn btn-outline-secondary" href="' . Html::cleanInputText($backHref) . '">' . Html::cleanInputText(__('Back to WAS configurations', 'nessusglpi')) . '</a></p>';
        echo '<h3>' . Html::cleanInputText(__('WAS scan executions', 'nessusglpi')) . '</h3>';
    } else {
        echo '<h3>' . Html::cleanInputText(__('Nessus / Tenable VM scans', 'nessusglpi')) . '</h3>';
    }

    nessusglpi_browser_render_scans($items, $source);
}

echo '</div>';
Html::footer();
