<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('collect_instagram', 'started');
$count   = 0;
$clients = getAllActiveClients();

foreach ($clients as $client) {
    $keywords = getClientKeywords($client['id']);
    if (empty($keywords)) continue;

    // Instagram Hashtag search via Graph API
    foreach (array_slice($keywords, 0, 2) as $keyword) {
        // Convert keyword to hashtag (remove spaces, special chars)
        $hashtag = strtolower(preg_replace('/[^a-zA-Z0-9\x{0900}-\x{097F}]/u', '', $keyword));
        if (mb_strlen($hashtag) < 2) continue;

        // Step 1: Get hashtag ID
        $url = 'https://graph.facebook.com/v19.0/ig_hashtag_search'
            . '?user_id=' . META_IG_USER_ID
            . '&q=' . urlencode($hashtag)
            . '&access_token=' . META_ACCESS_TOKEN;
        $r = curlGet($url);
        if ($r['code'] !== 200) continue;
        $hashData = json_decode($r['body'], true);
        $hashId   = $hashData['data'][0]['id'] ?? null;
        if (!$hashId) continue;

        // Step 2: Get recent media
        $url = 'https://graph.facebook.com/v19.0/' . $hashId . '/recent_media'
            . '?user_id=' . META_IG_USER_ID
            . '&fields=id,caption,media_type,permalink,timestamp,like_count,comments_count'
            . '&access_token=' . META_ACCESS_TOKEN;
        $r = curlGet($url);
        if ($r['code'] !== 200) continue;
        $data = json_decode($r['body'], true);

        foreach ($data['data'] ?? [] as $post) {
            $caption = $post['caption'] ?? '';
            if (empty($caption)) continue;

            $inserted = insertMention([
                'client_id'     => $client['id'],
                'platform'      => 'Instagram',
                'source_url'    => $post['permalink'] ?? null,
                'author_handle' => 'instagram_user',
                'content'       => mb_substr($caption, 0, 2000),
                'language'      => preg_match('/[\x{0900}-\x{097F}]/u', $caption) ? 'hi' : 'en',
                'reach_estimate' => (int) ($post['like_count'] ?? 0) * 20,
                'engagement'    => [
                    'likes'    => $post['like_count'] ?? 0,
                    'comments' => $post['comments_count'] ?? 0,
                ],
                'published_at'  => isset($post['timestamp'])
                    ? date('Y-m-d H:i:s', strtotime($post['timestamp'])) : null,
            ]);
            if ($inserted) $count++;
        }
        sleep(1);
    }
}

logCron('collect_instagram', 'completed', $count);
echo "Instagram: collected $count new mentions\n";
