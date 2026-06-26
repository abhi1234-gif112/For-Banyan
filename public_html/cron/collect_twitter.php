<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('collect_twitter', 'started');
$count   = 0;
$clients = getAllActiveClients();

foreach ($clients as $client) {
    $keywords = getClientKeywords($client['id']);
    if (empty($keywords)) continue;

    $query = implode(' OR ', array_map(fn($k) => '"' . $k . '"', array_slice($keywords, 0, 5)));
    $query .= ' lang:en OR lang:hi -is:retweet';

    $rateLimitKey = 'twitter_15min';
    if (incrementRateLimit($rateLimitKey, 900) > 400) {
        echo "Twitter rate limit approached, skipping\n";
        continue;
    }

    $url = 'https://api.twitter.com/2/tweets/search/recent'
        . '?query=' . urlencode($query)
        . '&max_results=50'
        . '&tweet.fields=created_at,author_id,public_metrics,lang'
        . '&expansions=author_id'
        . '&user.fields=name,username,public_metrics,verified';

    $response = curlGet($url, ['Authorization: Bearer ' . TWITTER_BEARER_TOKEN]);

    if ($response['code'] !== 200) {
        logCron('collect_twitter', 'failed', 0, "HTTP {$response['code']} for client {$client['name']}");
        continue;
    }

    $data = json_decode($response['body'], true);
    if (empty($data['data'])) continue;

    $users = [];
    foreach ($data['includes']['users'] ?? [] as $u) {
        $users[$u['id']] = $u;
    }

    foreach ($data['data'] as $tweet) {
        $author  = $users[$tweet['author_id']] ?? [];
        $metrics = $tweet['public_metrics'] ?? [];

        $inserted = insertMention([
            'client_id'     => $client['id'],
            'platform'      => 'Twitter/X',
            'source_url'    => 'https://twitter.com/i/web/status/' . $tweet['id'],
            'author_handle' => '@' . ($author['username'] ?? 'unknown'),
            'content'       => $tweet['text'],
            'language'      => $tweet['lang'] ?? 'en',
            'reach_estimate' => (int) ($author['public_metrics']['followers_count'] ?? 0),
            'engagement'    => [
                'likes'    => $metrics['like_count'] ?? 0,
                'shares'   => $metrics['retweet_count'] ?? 0,
                'comments' => $metrics['reply_count'] ?? 0,
                'views'    => $metrics['impression_count'] ?? 0,
            ],
            'published_at'  => date('Y-m-d H:i:s', strtotime($tweet['created_at'] ?? 'now')),
        ]);
        if ($inserted) $count++;
    }
}

logCron('collect_twitter', 'completed', $count);
echo "Twitter/X: collected $count new mentions\n";
