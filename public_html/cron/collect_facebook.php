<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('collect_facebook', 'started');
$count   = 0;
$clients = getAllActiveClients();

foreach ($clients as $client) {
    $keywords = getClientKeywords($client['id']);
    if (empty($keywords)) continue;

    // Use Meta Graph API keyword search on public pages
    foreach (array_slice($keywords, 0, 2) as $keyword) {
        $url = 'https://graph.facebook.com/v19.0/search'
            . '?type=post&q=' . urlencode($keyword)
            . '&fields=id,message,from,created_time,shares,reactions.summary(true)'
            . '&access_token=' . META_ACCESS_TOKEN;

        $response = curlGet($url);
        if ($response['code'] !== 200) continue;

        $data = json_decode($response['body'], true);
        foreach ($data['data'] ?? [] as $post) {
            if (empty($post['message'])) continue;

            $inserted = insertMention([
                'client_id'     => $client['id'],
                'platform'      => 'Facebook',
                'source_url'    => 'https://www.facebook.com/' . $post['id'],
                'author_handle' => $post['from']['name'] ?? 'unknown',
                'content'       => mb_substr($post['message'], 0, 2000),
                'language'      => preg_match('/[\x{0900}-\x{097F}]/u', $post['message']) ? 'hi' : 'en',
                'reach_estimate' => (int) ($post['reactions']['summary']['total_count'] ?? 0) * 50,
                'engagement'    => [
                    'likes'  => $post['reactions']['summary']['total_count'] ?? 0,
                    'shares' => $post['shares']['count'] ?? 0,
                ],
                'published_at'  => isset($post['created_time'])
                    ? date('Y-m-d H:i:s', strtotime($post['created_time'])) : null,
            ]);
            if ($inserted) $count++;
        }
        sleep(1);
    }
}

logCron('collect_facebook', 'completed', $count);
echo "Facebook: collected $count new mentions\n";
