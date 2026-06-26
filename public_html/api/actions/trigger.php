<?php
ini_set('max_execution_time', 90);
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

$user  = requireAuth();
$db    = getDB();
$input = getInput();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, null, 'Method not allowed', 405);
}

$clientId   = sanitise($input['client_id'] ?? '');
$actionType = sanitise($input['action_type'] ?? '');
$context    = $input['context'] ?? [];

if (!$clientId || !$actionType) {
    respond(false, null, 'client_id and action_type required', 400);
}
if (!canAccessClient($user, $clientId)) {
    respond(false, null, 'Access denied', 403);
}

// Load client
$stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch();
if (!$client) {
    respond(false, null, 'Client not found', 404);
}

$validTypes = [
    'counter_brief', 'rapid_response', 'press_kit', 'outreach_message',
    'whatsapp_forward', 'keyword_blocking_list', 'narrative_brief',
    'daily_report', 'weekly_digest', 'individual_strategy',
];
if (!in_array($actionType, $validTypes)) {
    respond(false, null, 'Invalid action_type', 400);
}

$systemPrompt = "You are a senior political ORM strategist and communications expert specialising in Indian politics. You work for Saptanga Labs LLP, a political intelligence consultancy. Your client is a high-profile public figure. Be accurate, professional, and politically astute. Never invent facts. Always write in the tone appropriate for Indian political communication.";

$clientContext = "Client: {$client['name']} | Role: {$client['role']} | Party: {$client['party']} | State: {$client['state']} | Constituency: {$client['constituency']}";

try {
    switch ($actionType) {

        case 'counter_brief': {
            $attacker = $context['attacker'] ?? 'unknown account';
            $claim    = $context['claim'] ?? 'unspecified claim';
            $platform = $context['platform'] ?? 'social media';
            $userPrompt = "Generate a structured rebuttal document in Markdown for:\n\n$clientContext\n\nAttack: \"$claim\" posted by $attacker on $platform\n\nInclude:\n1. Attack summary (what is being said)\n2. Factual corrections with data points\n3. Five talking points for the client's team\n4. Recommended public statement (2-3 sentences)\n5. Social media response drafts for Twitter/X and Facebook\n6. Do-not-say list\n\nLanguage: English with Hindi phrases where impactful. Temperature: low.";
            $output = callClaude($systemPrompt, $userPrompt, 0.3, 3000);
            break;
        }

        case 'rapid_response': {
            $topic    = $context['topic'] ?? 'recent attack';
            $platform = $context['platform'] ?? 'Twitter/X';
            $userPrompt = "Generate exactly 3 tweet drafts as a JSON array for:\n\n$clientContext\n\nTopic: $topic\nPlatform: $platform\n\nEach object: {\"tone\":\"...\",\"text\":\"...\",\"character_count\":0}\nTones: [\"factual_data_driven\", \"firm_assertive\", \"conciliatory_bridge_building\"]\nEach tweet under 280 characters.\n\nReturn ONLY valid JSON array, no markdown, no explanation.";
            $output = callClaude($systemPrompt, $userPrompt, 0.7, 800);
            break;
        }

        case 'press_kit': {
            $event = $context['event'] ?? 'upcoming media interaction';
            $userPrompt = "Generate a structured media brief in Markdown for:\n\n$clientContext\n\nFor: $event\n\nInclude:\n1. Client bio (3 sentences)\n2. Key achievements in last 6 months (5 bullet points with data)\n3. Current scheme highlights\n4. 3 pre-approved quotable quotes\n5. Media contact placeholder\n\nProfessional, factual tone.";
            $output = callClaude($systemPrompt, $userPrompt, 0.2, 2500);
            break;
        }

        case 'outreach_message': {
            $targetName   = $context['target_name'] ?? 'journalist';
            $targetHandle = $context['target_handle'] ?? '';
            $targetBeat   = $context['target_beat'] ?? 'politics';
            $ask          = $context['ask'] ?? 'interview';
            $userPrompt = "Generate a personalised outreach message (email/DM) for:\n\n$clientContext\n\nTo: $targetName ($targetHandle), covering $targetBeat beat\nAsk: $ask\n\nInclude: personalised opener referencing their work, value proposition, specific ask, clear next step. Professional relationship-building tone.";
            $output = callClaude($systemPrompt, $userPrompt, 0.7, 600);
            break;
        }

        case 'whatsapp_forward': {
            $topic    = $context['topic'] ?? 'recent achievement';
            $audience = $context['audience'] ?? 'general supporters';
            $userPrompt = "Generate a short viral-ready WhatsApp forward in Hindi (with English translation below) for:\n\n$clientContext\n\nTopic: $topic\nAudience: $audience\n\nRules: Max 250 words. Short sentences. No jargon. Ends with shareable call-to-action. Use emojis sparingly. First write Hindi version, then ---  then English translation.";
            $output = callClaude($systemPrompt, $userPrompt, 0.8, 600);
            break;
        }

        case 'keyword_blocking_list': {
            $platform = $context['platform'] ?? 'Facebook';
            $userPrompt = "Generate a keyword blocking list for $platform comments for:\n\n$clientContext\n\nCategories: personal attacks, sexual content, communal language, opposition slogans, defamatory terms, spam phrases, Hinglish variants.\n\nReturn ONLY a JSON array: [{\"keyword\":\"...\",\"category\":\"...\",\"language\":\"en|hi|hinglish\",\"severity\":\"high|medium\"}]\n\nMinimum 40 keywords. No markdown, no explanation.";
            $output = callClaude($systemPrompt, $userPrompt, 0.1, 2000);
            break;
        }

        case 'narrative_brief': {
            $event = $context['event'] ?? 'upcoming event';
            $userPrompt = "Generate a 2-page strategic narrative brief for:\n\n$clientContext\n\nFor: $event\n\nInclude:\n1. Current narrative landscape\n2. Key risks to anticipate\n3. Core messages to push\n4. Phrases and positions to avoid\n5. Media Q&A preparation (5 likely questions with recommended answers)\n\nFormat as professional Markdown document.";
            $output = callClaude($systemPrompt, $userPrompt, 0.3, 3000);
            break;
        }

        case 'individual_strategy': {
            $indName     = $context['individual_name'] ?? 'unknown';
            $indHandle   = $context['individual_handle'] ?? '';
            $indCategory = $context['individual_category'] ?? 'Influencer';
            $indRisk     = $context['risk_score'] ?? 'unknown';
            $userPrompt = "Generate a 2-page strategic note on individual: $indName ($indHandle, $indCategory, risk score $indRisk) for:\n\n$clientContext\n\nInclude:\n1. Who they are and why they matter\n2. Influence and risk assessment\n3. Their pattern of content about the client\n4. Recommended strategic approach (engage/counter/ignore/neutralise)\n5. Specific tactical steps with realistic timelines\n\nFormat as professional Markdown.";
            $output = callClaude($systemPrompt, $userPrompt, 0.4, 3000);
            break;
        }

        case 'daily_report': {
            $dateRange = $context['date_range'] ?? 'last 24 hours';
            $stmt2 = $db->prepare(
                "SELECT platform, sentiment, COUNT(*) as cnt, COALESCE(SUM(reach_estimate),0) as reach
                 FROM mentions WHERE client_id = ? AND collected_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 GROUP BY platform, sentiment"
            );
            $stmt2->execute([$clientId]);
            $mentionData = json_encode($stmt2->fetchAll());
            $userPrompt = "Generate a daily intelligence report in Markdown for:\n\n$clientContext\n\nData ($dateRange):\n$mentionData\n\nInclude: executive summary, sentiment snapshot, platform breakdown, top threats, top positive stories, recommended actions today. Professional, concise.";
            $output = callClaude($systemPrompt, $userPrompt, 0.2, 3000);
            break;
        }

        case 'weekly_digest': {
            $stmt2 = $db->prepare(
                "SELECT platform, sentiment, COUNT(*) as cnt, COALESCE(SUM(reach_estimate),0) as reach
                 FROM mentions WHERE client_id = ? AND collected_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY platform, sentiment"
            );
            $stmt2->execute([$clientId]);
            $mentionData = json_encode($stmt2->fetchAll());
            $userPrompt = "Generate a weekly intelligence digest in Markdown for:\n\n$clientContext\n\nWeek data:\n$mentionData\n\nInclude: week-in-review summary, sentiment trend analysis, top stories, emerging threats, narrative wins, recommended strategic focus for next week.";
            $output = callClaude($systemPrompt, $userPrompt, 0.2, 4000);
            break;
        }

        default:
            respond(false, null, 'Action type not implemented', 400);
    }

    // Store in DB
    $actionId = generateUUID();
    $db->prepare(
        "INSERT INTO actions (id, client_id, action_type, status, input_context, output_content, output_language,
         triggered_by_alert_id, triggered_by_individual_id)
         VALUES (?, ?, ?, 'completed', ?, ?, 'English', ?, ?)"
    )->execute([
        $actionId, $clientId, $actionType,
        json_encode($context),
        $output,
        $context['alert_id'] ?? null,
        $context['individual_id'] ?? null,
    ]);

    respond(true, [
        'action_id' => $actionId,
        'output'    => $output,
    ]);

} catch (Exception $e) {
    // Store failed action
    $actionId = generateUUID();
    $db->prepare(
        "INSERT INTO actions (id, client_id, action_type, status, input_context, output_content)
         VALUES (?, ?, ?, 'failed', ?, ?)"
    )->execute([$actionId, $clientId, $actionType, json_encode($context), $e->getMessage()]);

    respond(false, null, 'Action generation failed: ' . $e->getMessage(), 500);
}
