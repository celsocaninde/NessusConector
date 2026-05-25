<?php

declare(strict_types=1);

namespace GlpiPlugin\Nessusglpi;

use CommonDBTM;

class TicketMemory extends CommonDBTM
{
    public static $table = 'glpi_plugin_nessusglpi_ticket_memory';

    public static $rightname = 'plugin_nessusglpi_ticket';

    public static function getTable($classname = null)
    {
        return 'glpi_plugin_nessusglpi_ticket_memory';
    }

    public static function getTypeName($nb = 0): string
    {
        return __('Nessus ticket memory', 'nessusglpi');
    }

    public static function buildHostFingerprint(?array $hostFields): string
    {
        if (!is_array($hostFields)) {
            return '';
        }

        $fqdn = strtolower(trim((string) ($hostFields['fqdn'] ?? '')));
        if ($fqdn !== '') {
            return 'fqdn:' . $fqdn;
        }

        $hostname = strtolower(trim((string) ($hostFields['hostname'] ?? '')));
        if ($hostname !== '') {
            return 'hostname:' . $hostname;
        }

        $ip = strtolower(trim((string) ($hostFields['ip'] ?? '')));
        if ($ip !== '') {
            return 'ip:' . $ip;
        }

        return '';
    }

    public static function rememberVulnerabilityTicket(array $vulnerabilityFields, ?array $hostFields, int $ticketId): void
    {
        global $DB;

        if ($ticketId <= 0) {
            return;
        }

        $vulnKey = trim((string) ($vulnerabilityFields['vuln_key'] ?? ''));
        if ($vulnKey === '') {
            return;
        }

        $itemtype = trim((string) ($vulnerabilityFields['itemtype'] ?? ''));
        $itemsId = (int) ($vulnerabilityFields['items_id'] ?? 0);
        $hostFingerprint = static::buildHostFingerprint($hostFields);

        $existing = $DB->request([
            'FROM'  => static::getTable(),
            'WHERE' => [
                'vuln_key'         => $vulnKey,
                'itemtype'         => $itemtype,
                'items_id'         => $itemsId,
                'host_fingerprint' => $hostFingerprint,
                'tickets_id'       => $ticketId,
            ],
            'LIMIT' => 1,
        ])->current();

        if ($existing) {
            return;
        }

        $memory = new static();
        $memory->add([
            'vuln_key'         => $vulnKey,
            'itemtype'         => $itemtype,
            'items_id'         => $itemsId,
            'host_fingerprint' => $hostFingerprint,
            'tickets_id'       => $ticketId,
            'date_creation'    => date('Y-m-d H:i:s'),
        ]);
    }

    public static function findVulnerabilityTicketId(array $vulnerabilityFields, ?array $hostFields): ?int
    {
        $vulnKey = trim((string) ($vulnerabilityFields['vuln_key'] ?? ''));
        if ($vulnKey === '') {
            return null;
        }

        $itemtype = trim((string) ($vulnerabilityFields['itemtype'] ?? ''));
        $itemsId = (int) ($vulnerabilityFields['items_id'] ?? 0);
        if ($itemtype !== '' && $itemsId > 0) {
            $ticketId = static::findUsableTicketIdByWhere([
                'vuln_key' => $vulnKey,
                'itemtype' => $itemtype,
                'items_id' => $itemsId,
            ]);
            if ($ticketId !== null) {
                return $ticketId;
            }
        }

        $hostFingerprint = static::buildHostFingerprint($hostFields);
        if ($hostFingerprint !== '') {
            $ticketId = static::findUsableTicketIdByWhere([
                'vuln_key'         => $vulnKey,
                'host_fingerprint' => $hostFingerprint,
            ]);
            if ($ticketId !== null) {
                return $ticketId;
            }
        }

        return null;
    }

    public static function getTicketDataForVulnerability(array $vulnerabilityFields, ?array $hostFields): ?array
    {
        $ticketId = static::findVulnerabilityTicketId($vulnerabilityFields, $hostFields);
        if ($ticketId === null) {
            return null;
        }

        $ticket = new \Ticket();
        if (!$ticket->getFromDB($ticketId)) {
            return null;
        }

        if ((int) ($ticket->fields['is_deleted'] ?? 0) !== 0) {
            return null;
        }

        return [
            'id'   => (int) $ticket->fields['id'],
            'name' => (string) ($ticket->fields['name'] ?? ''),
            'link' => $ticket->getLinkURL(),
        ];
    }

    private static function findUsableTicketIdByWhere(array $where): ?int
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => static::getTable(),
            'WHERE' => $where,
            'ORDER' => ['id DESC'],
        ]);

        foreach ($iterator as $row) {
            $ticketId = (int) ($row['tickets_id'] ?? 0);
            if ($ticketId <= 0) {
                continue;
            }

            $ticket = new \Ticket();
            if (!$ticket->getFromDB($ticketId)) {
                continue;
            }

            if ((int) ($ticket->fields['is_deleted'] ?? 0) !== 0) {
                continue;
            }

            return $ticketId;
        }

        return null;
    }
}
