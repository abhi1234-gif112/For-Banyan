<?php
require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/_cron_base.php';

logCron('collect_rss_news', 'started');
$count = 0;

$staticFeeds = [
    'https://www.bhaskar.com/rss-feed/1061/',
    'https://feeds.feedburner.com/ndtvnews-top-stories',
    'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
    'https://theprint.in/feed/',
    'https://indianexpress.com/feed/',
    'https://www.hindustantimes.com/feeds/rss/india-news/rssfeed.xml',
    'https://www.jagran.com/rss/news-national.xml',
    'https://www.amarujala.com/rss/breaking-news.xml',
];

$googleFeedTemplate = 'https://news.google.com/rss/search?q={KEYWORD}&hl=en-IN&gl=IN&ceid=IN:en';
$googleFeedHi       = 'https://news.google.com/rss/search?q={KEYWORD}&hl=hi&gl=IN&ceid=IN:hi';

$clients = getAllActiveClients();

foreach ($clients as $client) {
    $keywords = getClientKeywords($client['id']);
    if (empty($keywords)) continue;

    // Static feeds — scan all and keyword-match
    foreach ($staticFeeds as $feedUrl) {
        $response = curlGet($feedUrl);
        if ($response['code'] !== 200 || empty($response['body'])) continue;

        try {
            $xml = @simplexml_load_string($response['body']);
            if (!$xml) continue;
            $items = $xml->channel->item ?? $xml->entry ?? [];
            foreach ($items as $item) {
                $title       = (string) ($item->title ?? '');
                $description = strip_tags((string) ($item->description ?? $item->summary ?? ''));
                $link        = (string) ($item->link ?? $item->id ?? '');
                $pubDate     = (string) ($item->pubDate ?? $item->published ?? '');
                $content     = $title . ' ' . $description;

                $matched = false;
                foreach ($keywords as $kw) {
                    if (mb_stripos($content, $kw) !== false) { $matched = true; break; }
                }
                if (!$matched) continue;

                $lang     = preg_match('/[\x{0900}-\x{097F}]/u', $content) ? 'hi' : 'en';
                $inserted = insertMention([
                    'client_id'     => $client['id'],
                    'platform'      => 'News',
                    'source_url'    => $link,
                    'author_handle' => parse_url($link, PHP_URL_HOST) ?: 'unknown',
                    'content'       => mb_substr($content, 0, 2000),
                    'language'      => $lang,
                    'reach_estimate' => rand(50000, 500000),
                    'published_at'  => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : null,
                ]);
                if ($inserted) $count++;
            }
        } catch (Exception $e) {
            continue;
        }
    }

    // Google News keyword feeds
    foreach (array_slice($keywords, 0, 3) as $kw) {
        foreach ([$googleFeedTemplate, $googleFeedHi] as $tpl) {
            $url      = str_replace('{KEYWORD}', urlencode($kw), $tpl);
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
                    $lang    = preg_match('/[\x{0900}-\x{097F}]/u', $title) ? 'hi' : 'en';

                    $inserted = insertMention([
                        'client_id'     => $client['id'],
                        'platform'      => 'News',
                        'source_url'    => $link,
                        'author_handle' => parse_url($link, PHP_URL_HOST) ?: 'news',
                        'content'       => mb_substr($title, 0, 2000),
                        'language'      => $lang,
                        'reach_estimate' => rand(10000, 200000),
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

logCron('collect_rss_news', 'completed', $count);
echo "RSS News: collected $count new mentions\n";
