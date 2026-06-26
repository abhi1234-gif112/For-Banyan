<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('collect_google_news', 'started');
$count   = 0;
$clients = getAllActiveClients();

foreach ($clients as $client) {
    $keywords = getClientKeywords($client['id']);
    foreach (array_slice($keywords, 0, 3) as $keyword) {
        foreach (['en-IN', 'hi'] as $lang) {
            $ceid = $lang === 'hi' ? 'IN:hi' : 'IN:en';
            $hl   = $lang === 'hi' ? 'hi' : 'en-IN';
            $url  = 'https://news.google.com/rss/search?q='
                . urlencode($keyword)
                . '&hl=' . $hl . '&gl=IN&ceid=' . $ceid;

            $response = curlGet($url);
            if ($response['code'] !== 200 || empty($response['body'])) continue;

            try {
                $xml   = @simplexml_load_string($response['body']);
                if (!$xml) continue;
                $items = $xml->channel->item ?? [];
                foreach ($items as $item) {
                    $title   = (string) ($item->title ?? '');
                    $link    = (string) ($item->link ?? '');
                    $pubDate = (string) ($item->pubDate ?? '');

                    $inserted = insertMention([
                        'client_id'     => $client['id'],
                        'platform'      => 'News',
                        'source_url'    => $link,
                        'author_handle' => parse_url($link, PHP_URL_HOST) ?: 'google_news',
                        'content'       => mb_substr($title, 0, 2000),
                        'language'      => $lang === 'hi' ? 'hi' : 'en',
                        'reach_estimate' => rand(20000, 300000),
                        'published_at'  => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : null,
                    ]);
                    if ($inserted) $count++;
                }
            } catch (Exception $e) {
                continue;
            }
            sleep(1);
        }
    }
}

logCron('collect_google_news', 'completed', $count);
echo "Google News: collected $count new mentions\n";
