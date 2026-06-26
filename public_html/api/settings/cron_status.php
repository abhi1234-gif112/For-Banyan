<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$u = requireAuth();
if (!isRole($u, 'super_admin')) respond(403, 'Forbidden');

$db = getDB();

$logs = $db->query("
    SELECT script_name, status, records_processed, ran_at, error_message
    FROM cron_logs
    WHERE (script_name, ran_at) IN (
        SELECT script_name, MAX(ran_at) FROM cron_logs GROUP BY script_name
    )
    ORDER BY script_name
")->fetchAll();

respond(200, 'ok', ['logs' => $logs]);
