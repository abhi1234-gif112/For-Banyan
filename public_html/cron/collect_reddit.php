<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('collect_reddit', 'started');
$count   = 0;
$clients = getAllActiveClients();

foreach ($clients as $client) {
    $keywords = getClientKeywords($client['id']);

    foreach (array_slice($keywords, 0, 2) as $keyword) {
        $url = 'https://www.reddit.com/search.json'
            . '?q=' . urlencode($keyword)
            . '&sort=new&t=day&limit=25&type=link,comment';

        $response = curlGet($url, ['User-Agent: NAZAR-Bot/1.0 by SaptangaLabs']);
        if ($response['code'] !== 200) { sleep(2); continue; }

        $data = json_decode($response['body'], true);
        foreach ($data['data']['children'] ?? [] as $child) {
            $post    = $child['data'];
            $content = ($post['title'] ?? '') . ' ' . ($post['selftext'] ?? $post['body'] ?? '');
            if (mb_strlen(trim($content)) < 10) continue;

            $inserted = insertMention([
                'client_id'     => $client['id'],
                'platform'      => 'Other',
                'source_url'    => 'https://reddit.com' . ($post['permalink'] ?? ''),
                'author_handle' => 'u/' . ($post['author'] ?? 'unknown'),
                'content'       => mb_substr($content, 0, 2000),
                'language'      => 'en',
                'reach_estimate' => (int) ($post['ups'] ?? 0) * 100,
                'engagement'    => [
                    'likes'    => $post['ups'] ?? 0,
                    'comments' => $post['num_comments'] ?? 0,
                ],
                'published_at'  => isset($post['created_utc'])
                    ? date('Y-m-d H:i:s', $post['created_utc']) : null,
            ]);
            if ($inserted) $count++;
        }
        sleep(2);
    }
}

logCron('collect_reddit', 'completed', $count);
echo "Reddit: collected $count new mentions\n";
