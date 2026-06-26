<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('daily_report', 'started');
$db      = getDB();
$clients = getAllActiveClients();
$count   = 0;

foreach ($clients as $client) {
    $cid      = $client['id'];
    $dateFrom = date('Y-m-d', strtotime('yesterday'));
    $dateTo   = date('Y-m-d', strtotime('yesterday'));

    // Get yesterday's data
    $stmt = $db->prepare(
        "SELECT platform, sentiment, COUNT(*) as cnt, COALESCE(SUM(reach_estimate),0) as reach
         FROM mentions WHERE client_id = ? AND collected_at BETWEEN ? AND ?
         GROUP BY platform, sentiment"
    );
    $stmt->execute([$cid, $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    $mentionData = $stmt->fetchAll();

    $total = 0;
    foreach ($mentionData as $m) $total += (int) $m['cnt'];

    if ($total === 0) continue;

    // Generate via Claude
    $sysPrompt  = "You are a senior political ORM strategist and communications expert specialising in Indian politics. Write a daily intelligence report for a political client. Be concise, factual, and actionable. Format as clean Markdown.";
    $clientCtx  = "Client: {$client['name']} | Role: {$client['role']} | Party: {$client['party']} | State: {$client['state']}";
    $dataStr    = json_encode($mentionData);
    $userPrompt = "Generate a daily hygiene report for: $clientCtx\n\nDate: $dateFrom\nMention data: $dataStr\n\nInclude: Executive summary (3 sentences), Sentiment snapshot, Platform breakdown, Top 3 threats, Top 3 positive stories, Recommended actions for today.";

    try {
        $output   = callClaude($sysPrompt, $userPrompt, 0.2, 2500);
        $reportId = generateUUID();

        $db->prepare(
            "INSERT INTO actions (id, client_id, action_type, status, input_context, output_content)
             VALUES (?, ?, 'daily_report', 'completed', ?, ?)"
        )->execute([$reportId, $cid, json_encode(['date' => $dateFrom]), $output]);

        $db->prepare(
            "INSERT INTO reports (id, client_id, report_type, date_from, date_to, status)
             VALUES (UUID(), ?, 'daily_hygiene', ?, ?, 'completed')"
        )->execute([$cid, $dateFrom, $dateTo]);

        // Email if configured
        if (REPORT_EMAIL && SENDGRID_API_KEY) {
            $emailPayload = json_encode([
                'personalizations' => [['to' => [['email' => REPORT_EMAIL]]]],
                'from'             => ['email' => 'reports@saptanga.com', 'name' => 'NAZAR Reports'],
                'subject'          => "NAZAR Daily Report: {$client['name']} — $dateFrom",
                'content'          => [['type' => 'text/plain', 'value' => $output]],
            ]);
            $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $emailPayload,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . SENDGRID_API_KEY,
                ],
            ]);
            curl_exec($ch);
            curl_close($ch);
        }

        $count++;
    } catch (Exception $e) {
        logCron('daily_report', 'failed', 0, "Client {$client['name']}: " . $e->getMessage());
    }
}

logCron('daily_report', 'completed', $count);
echo "Daily reports: generated $count reports\n";
