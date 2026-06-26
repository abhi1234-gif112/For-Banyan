<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('collect_youtube', 'started');
$count   = 0;
$clients = getAllActiveClients();

foreach ($clients as $client) {
    $keywords = getClientKeywords($client['id']);
    foreach (array_slice($keywords, 0, 3) as $keyword) {
        $rateLimitKey = 'youtube_daily';
        if (getRateLimit($rateLimitKey, 86400) > 9000) {
            echo "YouTube quota approached\n";
            break 2;
        }
        incrementRateLimit($rateLimitKey, 86400);

        $publishedAfter = date('Y-m-d\TH:i:s\Z', strtotime('-30 minutes'));
        $url = 'https://www.googleapis.com/youtube/v3/search?part=snippet'
            . '&q=' . urlencode($keyword)
            . '&type=video&order=date'
            . '&publishedAfter=' . $publishedAfter
            . '&maxResults=25'
            . '&key=' . YOUTUBE_API_KEY;

        $response = curlGet($url);
        if ($response['code'] !== 200) continue;

        $data = json_decode($response['body'], true);
        foreach ($data['items'] ?? [] as $item) {
            $snippet = $item['snippet'];
            $videoId = $item['id']['videoId'] ?? '';
            if (!$videoId) continue;

            $content  = $snippet['title'] . ' ' . $snippet['description'];
            $inserted = insertMention([
                'client_id'     => $client['id'],
                'platform'      => 'YouTube',
                'source_url'    => 'https://www.youtube.com/watch?v=' . $videoId,
                'author_handle' => $snippet['channelTitle'] ?? 'unknown',
                'content'       => mb_substr($content, 0, 2000),
                'language'      => preg_match('/[\x{0900}-\x{097F}]/u', $content) ? 'hi' : 'en',
                'reach_estimate' => rand(1000, 100000),
                'engagement'    => ['views' => 0, 'likes' => 0, 'comments' => 0],
                'published_at'  => date('Y-m-d H:i:s', strtotime($snippet['publishedAt'] ?? 'now')),
            ]);
            if ($inserted) $count++;
        }
        sleep(1);
    }
}

logCron('collect_youtube', 'completed', $count);
echo "YouTube: collected $count new mentions\n";
