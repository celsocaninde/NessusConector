<?php

declare(strict_types=1);

use GlpiPlugin\Nessusglpi\Scan;
use GlpiPlugin\Nessusglpi\SyncJobService;

include('../../../inc/includes.php');

Session::checkRight(Scan::$rightname, UPDATE);
Html::header_nocache();
header('Content-Type: application/json; charset=utf-8');

$entityIds = Scan::getVisibleEntityIds();
$service   = new SyncJobService();

$result = $service->processNextPendingJob($entityIds);

$payload = [
    'ok'        => true,
    'processed' => $result !== null,
    'remaining' => (int) ($result['remaining'] ?? $service->countPendingJobs($entityIds)),
    'open'      => (int) $service->countOpenJobs($entityIds),
    'job'       => null,
];

if ($result !== null) {
    $scanInternalId = (int) ($result['scan_id'] ?? 0);
    $scan = new Scan();
    $scanRow = $scanInternalId > 0 && $scan->getFromDB($scanInternalId) ? $scan->fields : [];

    $lastSyncStatus = (string) ($scanRow['last_sync_status'] ?? ($result['status'] ?? ''));
    $lastSyncAt     = (string) ($scanRow['last_sync_at'] ?? '');

    $payload['job'] = [
        'job_id'             => (int) ($result['job_id'] ?? 0),
        'scan_internal_id'   => $scanInternalId,
        'scan_name'          => (string) ($scanRow['name'] ?? ('#' . $scanInternalId)),
        'status'             => (string) ($result['status'] ?? ''),
        'message'            => (string) ($result['message'] ?? ''),
        'run_id'             => isset($result['run_id']) ? (int) $result['run_id'] : null,
        'last_sync_status'   => $lastSyncStatus,
        'last_sync_at'       => $lastSyncAt,
        'last_sync_bucket'   => Scan::statusBucket($lastSyncStatus),
        'last_sync_label'    => Scan::statusLabel($lastSyncStatus),
        'last_sync_relative' => Scan::relativeDate($lastSyncAt),
    ];
}

echo json_encode($payload, JSON_THROW_ON_ERROR);
