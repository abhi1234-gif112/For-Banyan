-- NAZAR Political Intelligence Platform
-- Run this once in phpMyAdmin after creating your database

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS clients (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  name VARCHAR(200) NOT NULL,
  handle VARCHAR(100),
  role VARCHAR(200),
  party VARCHAR(100),
  constituency VARCHAR(200),
  state VARCHAR(100),
  language_scope JSON,
  platforms JSON,
  keywords JSON,
  aliases JSON,
  risk_baseline INT DEFAULT 50,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  email VARCHAR(200) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','analyst','viewer') DEFAULT 'analyst',
  assigned_client_ids JSON,
  is_active TINYINT(1) DEFAULT 1,
  last_login DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS individuals (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  name VARCHAR(200),
  handle VARCHAR(200) UNIQUE,
  platforms JSON,
  category ENUM('Journalist','Influencer','Opposition','Academic','Media House','Political Handle','Troll/Bot','Fan Page') DEFAULT 'Influencer',
  sub_category VARCHAR(100),
  reach_estimate BIGINT DEFAULT 0,
  influence_score INT DEFAULT 0,
  risk_score INT DEFAULT 0,
  stance ENUM('Ally','Threat','Watchlist','Neutral') DEFAULT 'Neutral',
  stance_reasoning TEXT,
  languages JSON,
  topics JSON,
  is_verified TINYINT(1) DEFAULT 0,
  is_political TINYINT(1) DEFAULT 0,
  party_affiliation VARCHAR(100),
  last_active DATETIME,
  mention_count_7d INT DEFAULT 0,
  mention_count_30d INT DEFAULT 0,
  sentiment_avg_30d FLOAT DEFAULT 0.5,
  suggestions JSON,
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mentions (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  client_id VARCHAR(36) NOT NULL,
  platform ENUM('Facebook','Instagram','YouTube','Twitter/X','LinkedIn','Blog','News','Telegram','Reddit','WhatsApp','Other') NOT NULL,
  source_url TEXT,
  author_handle VARCHAR(200),
  author_id VARCHAR(36),
  content TEXT NOT NULL,
  content_language VARCHAR(20),
  reach_estimate BIGINT DEFAULT 0,
  engagement JSON,
  sentiment ENUM('positive','negative','neutral'),
  sentiment_score FLOAT,
  tone ENUM('informational','critical','promotional','satirical','threatening','emotional'),
  topics JSON,
  keywords_matched JSON,
  is_verified_account TINYINT(1) DEFAULT 0,
  is_opposition_linked TINYINT(1) DEFAULT 0,
  is_viral TINYINT(1) DEFAULT 0,
  alert_triggered TINYINT(1) DEFAULT 0,
  content_hash VARCHAR(64) UNIQUE,
  summary_en TEXT,
  summary_hi TEXT,
  recommended_action ENUM('none','monitor','respond','escalate') DEFAULT 'none',
  enriched_at DATETIME,
  collected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  published_at DATETIME,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_client_sentiment (client_id, sentiment),
  INDEX idx_client_platform (client_id, platform),
  INDEX idx_collected (collected_at),
  INDEX idx_enriched (enriched_at),
  INDEX idx_hash (content_hash)
);

CREATE TABLE IF NOT EXISTS alerts (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  client_id VARCHAR(36) NOT NULL,
  alert_type ENUM('spike','viral_positive','viral_negative','opposition_attack','coordinated_campaign','new_threat_individual','keyword_surge','media_pickup','positive_milestone','silence_anomaly') NOT NULL,
  severity ENUM('critical','high','medium','low') NOT NULL,
  title VARCHAR(500) NOT NULL,
  description TEXT,
  trigger_data JSON,
  is_read TINYINT(1) DEFAULT 0,
  is_actioned TINYINT(1) DEFAULT 0,
  action_taken TEXT,
  triggered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  resolved_at DATETIME,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_client_severity (client_id, severity),
  INDEX idx_client_read (client_id, is_read)
);

CREATE TABLE IF NOT EXISTS actions (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  client_id VARCHAR(36) NOT NULL,
  action_type ENUM('counter_brief','rapid_response','press_kit','outreach_message','whatsapp_forward','keyword_blocking_list','narrative_brief','daily_report','weekly_digest','individual_strategy') NOT NULL,
  status ENUM('pending','completed','failed') DEFAULT 'completed',
  input_context JSON,
  output_content LONGTEXT,
  output_language VARCHAR(20) DEFAULT 'English',
  triggered_by_alert_id VARCHAR(36),
  triggered_by_individual_id VARCHAR(36),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  INDEX idx_client_type (client_id, action_type)
);

CREATE TABLE IF NOT EXISTS keywords (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  client_id VARCHAR(36) NOT NULL,
  keyword VARCHAR(500) NOT NULL,
  type ENUM('primary','secondary','hashtag','negative','competitor') DEFAULT 'primary',
  language ENUM('en','hi','hinglish','regional') DEFAULT 'en',
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reports (
  id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
  client_id VARCHAR(36) NOT NULL,
  report_type ENUM('daily_hygiene','weekly_digest','custom') NOT NULL,
  date_from DATE,
  date_to DATE,
  file_name VARCHAR(300),
  status ENUM('generating','completed','failed') DEFAULT 'completed',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cron_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  script_name VARCHAR(100) NOT NULL,
  status ENUM('started','completed','failed') NOT NULL,
  records_processed INT DEFAULT 0,
  error_message TEXT,
  ran_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_script (script_name),
  INDEX idx_ran_at (ran_at)
);

CREATE TABLE IF NOT EXISTS rate_limits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  key_name VARCHAR(200) UNIQUE NOT NULL,
  counter INT DEFAULT 0,
  window_start DATETIME DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  INDEX idx_key (key_name),
  INDEX idx_expires (expires_at)
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Clients
INSERT IGNORE INTO clients (id, name, handle, role, party, constituency, state, language_scope, platforms, keywords, aliases, risk_baseline) VALUES
('c1000000-0000-4000-8000-000000000001', 'OP Choudhary', '@OPChoudhary', 'Finance Minister', 'BJP', 'Raigarh', 'Chhattisgarh',
 '["Hindi","English"]',
 '["Twitter/X","Facebook","Instagram","YouTube","News"]',
 '["OP Choudhary","OPChoudhary","ओपी चौधरी","Raigarh","Chhattisgarh Finance"]',
 '["Omprakash Choudhary","O.P. Choudhary"]',
 45),
('c2000000-0000-4000-8000-000000000002', 'Ashish Shelar', '@AshishShelar', 'MLA', 'BJP', 'Mumbai North-West', 'Maharashtra',
 '["Marathi","Hindi","English"]',
 '["Twitter/X","Facebook","Instagram","News"]',
 '["Ashish Shelar","AshishShelar","आशीष शेलार","Mumbai North-West","Vile Parle"]',
 '["Ashish Kumar Shelar","BJP Mumbai"]',
 50);

-- Users
INSERT IGNORE INTO users (id, email, password_hash, role, assigned_client_ids) VALUES
('u1000000-0000-4000-8000-000000000001', 'admin@saptanga.com',
 '$2y$12$4X.nHzqU5AqA2kf79i1Pteo7GymIceLxs.wMyF5sUI2wqOx1W7csy', -- Saptanga@2024
 'super_admin', '["c1000000-0000-4000-8000-000000000001","c2000000-0000-4000-8000-000000000002"]'),
('u2000000-0000-4000-8000-000000000002', 'analyst1@saptanga.com',
 '$2y$12$4X.nHzqU5AqA2kf79i1Pteo7GymIceLxs.wMyF5sUI2wqOx1W7csy',
 'analyst', '["c1000000-0000-4000-8000-000000000001"]'),
('u3000000-0000-4000-8000-000000000003', 'analyst2@saptanga.com',
 '$2y$12$4X.nHzqU5AqA2kf79i1Pteo7GymIceLxs.wMyF5sUI2wqOx1W7csy',
 'analyst', '["c2000000-0000-4000-8000-000000000002"]');

-- Keywords for OP Choudhary
INSERT IGNORE INTO keywords (id, client_id, keyword, type, language) VALUES
(UUID(), 'c1000000-0000-4000-8000-000000000001', 'OP Choudhary', 'primary', 'en'),
(UUID(), 'c1000000-0000-4000-8000-000000000001', 'OPChoudhary', 'hashtag', 'en'),
(UUID(), 'c1000000-0000-4000-8000-000000000001', 'ओपी चौधरी', 'primary', 'hi'),
(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Raigarh', 'secondary', 'en'),
(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Chhattisgarh Finance Minister', 'secondary', 'en'),
(UUID(), 'c1000000-0000-4000-8000-000000000001', 'छत्तीसगढ़ वित्त मंत्री', 'secondary', 'hi'),
(UUID(), 'c1000000-0000-4000-8000-000000000001', 'BJP Chhattisgarh', 'secondary', 'en'),
(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Omprakash Choudhary', 'primary', 'en');

-- Keywords for Ashish Shelar
INSERT IGNORE INTO keywords (id, client_id, keyword, type, language) VALUES
(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Ashish Shelar', 'primary', 'en'),
(UUID(), 'c2000000-0000-4000-8000-000000000002', 'AshishShelar', 'hashtag', 'en'),
(UUID(), 'c2000000-0000-4000-8000-000000000002', 'आशीष शेलार', 'primary', 'hi'),
(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Mumbai North-West', 'secondary', 'en'),
(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Vile Parle MLA', 'secondary', 'en'),
(UUID(), 'c2000000-0000-4000-8000-000000000002', 'BJP Mumbai', 'secondary', 'en'),
(UUID(), 'c2000000-0000-4000-8000-000000000002', 'शेलार', 'secondary', 'hi'),
(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Shelar BJP', 'hashtag', 'en');

-- Individuals
INSERT IGNORE INTO individuals (id, name, handle, platforms, category, reach_estimate, influence_score, risk_score, stance, stance_reasoning, languages, topics, is_verified, is_political, party_affiliation, last_active, mention_count_7d, mention_count_30d, sentiment_avg_30d, suggestions) VALUES
('i1000000-0000-4000-8000-000000000001', 'Ravish Kumar', '@ravishkumar', '["Twitter/X","YouTube"]', 'Journalist', 5200000, 88, 62, 'Watchlist',
 'Prominent NDTV journalist known for critical reporting on BJP governance. Has covered Chhattisgarh politics extensively.',
 '["Hindi","English"]', '["governance","media-coverage","opposition-attack"]', 1, 0, NULL,
 DATE_SUB(NOW(), INTERVAL 1 DAY), 4, 18, 0.32,
 '[{"action_type":"monitor","priority":"high","title":"Track weekly content themes","detail":"Ravish Kumar publishes critical pieces regularly. Monitor for mentions 3x weekly.","next_step":"Generate narrative brief before major announcements"},{"action_type":"prepare","priority":"medium","title":"Prepare rebuttal templates","detail":"Create pre-approved responses for common criticism angles this journalist covers.","next_step":"Generate press kit with pre-approved quotes"}]'),

('i2000000-0000-4000-8000-000000000002', 'Barkha Dutt', '@BDUTT', '["Twitter/X","YouTube","Blog"]', 'Journalist', 3800000, 82, 45, 'Watchlist',
 'Senior journalist with national reach. Has interviewed both BJP and opposition leaders. Coverage is generally balanced.',
 '["English","Hindi"]', '["media-coverage","governance","women-welfare"]', 1, 0, NULL,
 DATE_SUB(NOW(), INTERVAL 2 DAY), 2, 9, 0.51,
 '[{"action_type":"outreach","priority":"medium","title":"Offer exclusive interview","detail":"Barkha has indicated interest in development stories from Chhattisgarh. An exclusive interview could generate positive national coverage.","next_step":"Generate outreach message for interview pitch"}]'),

('i3000000-0000-4000-8000-000000000003', 'Dhruv Rathee', '@dhruv_rathee', '["YouTube","Instagram","Twitter/X"]', 'Influencer', 18500000, 94, 78, 'Threat',
 'Highly influential YouTube commentator with strong anti-BJP stance. Videos regularly go viral among 18-35 demographic.',
 '["Hindi","English"]', '["governance","budget-finance","opposition-attack","youth-employment"]', 1, 1, NULL,
 DATE_SUB(NOW(), INTERVAL 1 HOUR), 12, 47, 0.18,
 '[{"action_type":"counter","priority":"high","title":"Prepare data-driven counter content","detail":"Dhruv Rathee''s videos often contain factual gaps. Prepare rapid response fact sheets before major policy announcements.","next_step":"Generate counter brief for most recent video"},{"action_type":"coordinate","priority":"high","title":"Identify friendly YouTubers for response","detail":"Work with pro-BJP content creators to produce rebuttal videos within 48 hours of Rathee uploads.","next_step":"Generate list of allied influencers for outreach"}]'),

('i4000000-0000-4000-8000-000000000004', 'Priyanka Chaturvedi', '@priyankac21', '["Twitter/X","Facebook"]', 'Opposition', 1200000, 71, 83, 'Threat',
 'Shiv Sena (UBT) Rajya Sabha MP. Regularly attacks BJP leaders on social media and in parliament. High media pickup rate.',
 '["Hindi","English","Marathi"]', '["opposition-attack","party-politics","law-order"]', 1, 1, 'Shiv Sena (UBT)',
 DATE_SUB(NOW(), INTERVAL 3 HOUR), 8, 31, 0.12,
 '[{"action_type":"monitor","priority":"high","title":"Daily monitoring of parliament statements","detail":"Priyanka Chaturvedi often previews attacks on Twitter before raising in parliament. Early detection gives 6-12 hour response window.","next_step":"Generate rapid response template for common attack narratives"}]'),

('i5000000-0000-4000-8000-000000000005', 'Aditya Thackeray', '@AUThackeray', '["Twitter/X","Instagram"]', 'Opposition', 2100000, 79, 77, 'Threat',
 'Shiv Sena (UBT) leader and former minister. Influential in Mumbai politics, frequent critic of BJP in Maharashtra.',
 '["Marathi","Hindi","English"]', '["opposition-attack","party-politics","election-campaign"]', 1, 1, 'Shiv Sena (UBT)',
 DATE_SUB(NOW(), INTERVAL 5 HOUR), 6, 24, 0.19,
 '[{"action_type":"counter","priority":"high","title":"Monitor Mumbai constituency attacks","detail":"Aditya Thackeray frequently targets BJP MLAs in Mumbai. Coordinate rapid response within 2 hours of any attack.","next_step":"Generate counter brief for recent constituency criticism"}]'),

('i6000000-0000-4000-8000-000000000006', 'Prof. Sudha Pai', '@sudhapai', '["Twitter/X"]', 'Academic', 85000, 62, 28, 'Neutral',
 'Political science professor, JNU. Provides academic commentary on BJP governance. Generally objective, occasionally critical.',
 '["English"]', '["governance","party-politics","election-campaign"]', 0, 0, NULL,
 DATE_SUB(NOW(), INTERVAL 4 DAY), 1, 3, 0.48,
 '[{"action_type":"engage","priority":"low","title":"Offer policy briefing access","detail":"Academic voices add credibility. Offering data access can lead to more balanced academic commentary.","next_step":"Generate outreach message for policy briefing"}]'),

('i7000000-0000-4000-8000-000000000007', 'Dr. Surjit Bhalla', '@surjitbhalla', '["Twitter/X"]', 'Academic', 120000, 65, 22, 'Ally',
 'Economist and former IMF executive. Frequently supportive of BJP economic policies. Good amplification potential.',
 '["English"]', '["budget-finance","governance"]', 1, 1, 'BJP (aligned)',
 DATE_SUB(NOW(), INTERVAL 2 DAY), 2, 8, 0.74,
 '[{"action_type":"coordinate","priority":"medium","title":"Share budget data for amplification","detail":"Dr. Bhalla amplifies positive economic data. Share Chhattisgarh budget achievements for Twitter amplification.","next_step":"Generate WhatsApp forward with key economic metrics"}]'),

('i8000000-0000-4000-8000-000000000008', 'NDTV India', '@ndtvindia', '["Twitter/X","YouTube","Facebook"]', 'Media House', 12000000, 91, 55, 'Watchlist',
 'Major Hindi news channel. Coverage is neutral-to-critical. High reach means any critical story has outsized impact.',
 '["Hindi"]', '["governance","media-coverage","election-campaign"]', 1, 0, NULL,
 DATE_SUB(NOW(), INTERVAL 6 HOUR), 5, 22, 0.43,
 '[{"action_type":"outreach","priority":"medium","title":"Pitch positive development stories","detail":"NDTV covers human interest development stories regularly. Pitch scheme beneficiary stories for positive coverage.","next_step":"Generate press kit for NDTV pitch"}]'),

('i9000000-0000-4000-8000-000000000009', 'India Today', '@IndiaToday', '["Twitter/X","YouTube","Facebook","Instagram"]', 'Media House', 35000000, 96, 40, 'Neutral',
 'India''s largest English news magazine and TV channel. High reach, generally balanced coverage.',
 '["English","Hindi"]', '["governance","media-coverage","budget-finance"]', 1, 0, NULL,
 DATE_SUB(NOW(), INTERVAL 2 HOUR), 3, 14, 0.52,
 '[{"action_type":"outreach","priority":"high","title":"Offer exclusive budget analysis","detail":"India Today regularly features state finance ministers for budget analysis segments. Pitch an exclusive interview on Chhattisgarh''s fiscal performance.","next_step":"Generate press kit and media brief for India Today pitch"}]'),

('i10000000-0000-4000-8000-00000000010', 'Kapil Mishra', '@KapilMishra_IND', '["Twitter/X","Facebook"]', 'Political Handle', 890000, 69, 35, 'Ally',
 'Pro-BJP political activist with strong social media presence. Amplifies BJP messaging reliably.',
 '["Hindi","English"]', '["party-politics","law-order","religious-communal"]', 1, 1, 'BJP',
 DATE_SUB(NOW(), INTERVAL 8 HOUR), 3, 11, 0.81,
 '[{"action_type":"coordinate","priority":"medium","title":"Coordinate WhatsApp campaign amplification","detail":"Kapil Mishra has a strong WhatsApp network. Can amplify key messages to BJP ground cadre quickly.","next_step":"Generate WhatsApp forward for cadre distribution"}]'),

('i11000000-0000-4000-8000-00000000011', 'FactCheck India', '@factcheckindia', '["Twitter/X","Facebook","Blog"]', 'Journalist', 450000, 67, 58, 'Watchlist',
 'Fact-checking portal that has targeted BJP ministers. Any viral claim about the client will be picked up within 24 hours.',
 '["English","Hindi"]', '["governance","corruption-allegation","media-coverage"]', 1, 0, NULL,
 DATE_SUB(NOW(), INTERVAL 1 DAY), 2, 7, 0.38,
 '[{"action_type":"prepare","priority":"high","title":"Maintain real-time fact repository","detail":"FactCheck India targets claims quickly. Maintain a private repository of verifiable achievements and data points for rapid rebuttal.","next_step":"Generate narrative brief with key defensible facts"}]'),

('i12000000-0000-4000-8000-00000000012', 'NaMo Supporters', '@NamoSupporters', '["Twitter/X","Facebook","WhatsApp"]', 'Fan Page', 2300000, 72, 15, 'Ally',
 'Large BJP supporter network with strong organic amplification capability. Key asset for trending campaigns.',
 '["Hindi","Hinglish"]', '["party-politics","election-campaign","social-praise"]', 0, 1, 'BJP',
 DATE_SUB(NOW(), INTERVAL 4 HOUR), 7, 28, 0.88,
 '[{"action_type":"coordinate","priority":"high","title":"Weekly content coordination","detail":"NaMo Supporters network can trend hashtags organically. Weekly content drops with key BJP achievements get 10x organic amplification.","next_step":"Generate WhatsApp forward for weekly amplification"}]');

-- Mentions (60 realistic mentions)
INSERT IGNORE INTO mentions (id, client_id, platform, source_url, author_handle, content, content_language, reach_estimate, engagement, sentiment, sentiment_score, tone, topics, keywords_matched, is_verified_account, is_opposition_linked, is_viral, summary_en, summary_hi, recommended_action, enriched_at, published_at, content_hash) VALUES

-- OP Choudhary mentions
(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Twitter/X', 'https://twitter.com/i/web/status/1001', '@ravishkumar',
 'The much-touted tribal welfare scheme by OP Choudhary in Chhattisgarh has still not reached 60% of intended beneficiaries as per RTI data obtained by our team. Where is the accountability?',
 'en', 3200000, '{"likes":4521,"shares":1823,"comments":892,"views":280000}',
 'negative', 0.12, 'critical', '["tribal-welfare","governance","corruption-allegation"]', '["OP Choudhary","Chhattisgarh"]',
 1, 0, 1, 'Ravish Kumar criticises tribal scheme delivery failure citing RTI data.',
 'रवीश कुमार ने RTI डेटा के आधार पर आदिवासी योजना की विफलता की आलोचना की।',
 'escalate', NOW(), DATE_SUB(NOW(), INTERVAL 2 HOUR), SHA2('tweet1001opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'News', 'https://ndtv.com/india/op-choudhary-budget-2024', '@ndtvindia',
 'Finance Minister OP Choudhary presents ₹1.47 lakh crore budget for Chhattisgarh, with 18% increase in tribal welfare allocation. Minister says this is the largest budget in state history focused on Antyodaya.',
 'en', 12000000, '{"likes":892,"shares":445,"comments":231,"views":890000}',
 'positive', 0.78, 'informational', '["budget-finance","tribal-welfare","governance"]', '["OP Choudhary","Chhattisgarh Finance Minister"]',
 1, 0, 0, 'NDTV covers OP Choudhary presenting record budget with tribal welfare focus.',
 'NDTV ने OP चौधरी के रिकॉर्ड बजट को आदिवासी कल्याण फोकस के साथ कवर किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 5 HOUR), SHA2('news1001opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Twitter/X', 'https://twitter.com/i/web/status/1002', '@dhruv_rathee',
 'THREAD: OP Choudhary promised to end coal mafia in Raigarh. 8 months later - same illegal mining, same goons, zero action. This is what BJP promises look like in practice. Here is the data 🧵',
 'en', 18500000, '{"likes":28400,"shares":12300,"comments":4500,"views":2100000}',
 'negative', 0.05, 'critical', '["governance","corruption-allegation","law-order"]', '["OP Choudhary","Raigarh"]',
 1, 1, 1, 'Dhruv Rathee viral thread alleges OP Choudhary failed to stop coal mafia in Raigarh.',
 'धृव राठी ने वायरल थ्रेड में आरोप लगाया कि OP चौधरी रायगढ़ में कोल माफिया रोकने में विफल।',
 'escalate', NOW(), DATE_SUB(NOW(), INTERVAL 1 HOUR), SHA2('tweet1002opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Facebook', 'https://facebook.com/post/103', '@BJPChhattisgarh',
 'वित्त मंत्री श्री OP चौधरी जी ने आज रायगढ़ में 500 आदिवासी छात्रों को छात्रवृत्ति वितरित की। यह मोदी सरकार की अंत्योदय की सोच का प्रतीक है। जय छत्तीसगढ़! 🙏 #OPChoudhary #BJP',
 'hi', 450000, '{"likes":3421,"shares":1205,"comments":445}',
 'positive', 0.92, 'promotional', '["tribal-welfare","social-praise","party-politics"]', '["OP Choudhary","Chhattisgarh","OPChoudhary"]',
 1, 0, 0, 'BJP Chhattisgarh promotes OP Choudhary distributing scholarships to 500 tribal students.',
 'BJP छत्तीसगढ़ ने 500 आदिवासी छात्रों को छात्रवृत्ति वितरण कार्यक्रम को प्रमोट किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 3 HOUR), SHA2('fb103opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'YouTube', 'https://youtube.com/watch?v=abc123', 'ABP News Hindi',
 'OP Choudhary LIVE: वित्त मंत्री का बजट भाषण पूरा | Chhattisgarh Budget 2024 | ABP News',
 'hi', 8500000, '{"views":450000,"likes":8200,"comments":1200}',
 'neutral', 0.55, 'informational', '["budget-finance","governance"]', '["OP Choudhary","Chhattisgarh"]',
 1, 0, 0, 'ABP News streams OP Choudhary''s full budget speech live.',
 'ABP News ने OP चौधरी का पूरा बजट भाषण लाइव स्ट्रीम किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 6 HOUR), SHA2('yt001opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Twitter/X', 'https://twitter.com/i/web/status/1003', '@priyankac21',
 'OP Choudhary''s much publicised "Digital Raigarh" project - 18 months, ₹340 crore spent, still no functional broadband in 70% of villages. Meanwhile contracts went to companies with links to BJP donors. We will raise this in RS.',
 'en', 1200000, '{"likes":6800,"shares":2900,"comments":1100,"views":450000}',
 'negative', 0.08, 'critical', '["governance","corruption-allegation","opposition-attack"]', '["OP Choudhary","Raigarh","Chhattisgarh Finance Minister"]',
 1, 1, 1, 'Priyanka Chaturvedi alleges corruption in Raigarh digital project, plans RS question.',
 'प्रियंका चतुर्वेदी ने डिजिटल रायगढ़ परियोजना में भ्रष्टाचार का आरोप लगाया।',
 'escalate', NOW(), DATE_SUB(NOW(), INTERVAL 30 MINUTE), SHA2('tweet1003opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'News', 'https://theprint.in/op-choudhary-tribal', 'ThePrint',
 'Exclusive: How OP Choudhary is rebuilding Chhattisgarh''s tribal economy through forest produce procurement reform. Under his initiative, MSP for tendu patta has increased 40%, directly benefiting 8 lakh families.',
 'en', 2800000, '{"likes":1240,"shares":890,"comments":312,"views":320000}',
 'positive', 0.84, 'informational', '["tribal-welfare","governance","budget-finance"]', '["OP Choudhary","Chhattisgarh"]',
 1, 0, 0, 'The Print exclusive on OP Choudhary''s tribal economy reforms benefiting 8 lakh families.',
 'द प्रिंट का एक्सक्लूसिव: OP चौधरी के वन उपज सुधार से 8 लाख परिवारों को लाभ।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 8 HOUR), SHA2('print001opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Instagram', 'https://instagram.com/p/abc001', '@OPChoudhary',
 'आज रायगढ़ की माटी से एक और कदम आगे। जनजातीय महिला स्वयं सहायता समूहों को 50 लाख रुपये की मदद दी। नारी शक्ति = छत्तीसगढ़ की शक्ति 💪 #रायगढ़ #आदिवासी #नारीशक्ति',
 'hi', 89000, '{"likes":4521,"comments":234}',
 'positive', 0.91, 'promotional', '["tribal-welfare","women-welfare","governance"]', '["OP Choudhary","Raigarh","ओपी चौधरी"]',
 1, 0, 0, 'OP Choudhary posts about tribal women SHG support of ₹50 lakh.',
 'OP चौधरी ने आदिवासी महिला स्वयं सहायता समूहों को 50 लाख की मदद की पोस्ट शेयर की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 4 HOUR), SHA2('ig001opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Other', NULL, 'CG Vichar Manch',
 'रायगढ़ में पेट्रोल कीमतें आसमान छू रही हैं। वित्त मंत्री OP चौधरी क्या कर रहे हैं? बजट में आम आदमी के लिए क्या है? सिर्फ अमीरों की पार्टी है BJP। ये नहीं सुनेंगे हमारी बात।',
 'hi', 45000, '{"members":45000,"forwards":230}',
 'negative', 0.15, 'critical', '["governance","budget-finance","opposition-attack"]', '["OP Choudhary","Raigarh","ओपी चौधरी"]',
 0, 0, 0, 'Telegram channel criticises OP Choudhary over fuel prices and budget priorities.',
 'टेलीग्राम चैनल ने ईंधन कीमतों पर OP चौधरी की आलोचना की।',
 'monitor', NOW(), DATE_SUB(NOW(), INTERVAL 2 HOUR), SHA2('tg001opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Twitter/X', 'https://twitter.com/i/web/status/1004', '@surjitbhalla',
 'Chhattisgarh under FM @OPChoudhary shows 12.3% GSDP growth — highest in central India. Fiscal deficit contained at 3.1%. This is what responsible state finance looks like. Congratulations.',
 'en', 120000, '{"likes":2100,"shares":890,"comments":145,"views":89000}',
 'positive', 0.89, 'informational', '["budget-finance","governance"]', '["OP Choudhary","OPChoudhary","Chhattisgarh Finance Minister"]',
 1, 0, 0, 'Economist Surjit Bhalla praises Chhattisgarh GSDP growth under OP Choudhary.',
 'अर्थशास्त्री सुरजीत भल्ला ने OP चौधरी के नेतृत्व में छत्तीसगढ़ की GSDP वृद्धि की सराहना की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 7 HOUR), SHA2('tweet1004opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'News', 'https://jagran.com/op-choudhary-1', 'Dainik Jagran',
 'छत्तीसगढ़ के वित्त मंत्री OP चौधरी ने कहा कि राज्य में 2025 तक सभी गांवों को बिजली मिलेगी। रायगढ़ से शुरू होकर यह योजना पूरे प्रदेश में फैलेगी।',
 'hi', 5200000, '{"likes":892,"shares":445,"comments":123,"views":280000}',
 'positive', 0.77, 'informational', '["governance","tribal-welfare"]', '["OP Choudhary","Raigarh","छत्तीसगढ़ वित्त मंत्री"]',
 1, 0, 0, 'Dainik Jagran reports OP Choudhary''s promise of electricity to all villages by 2025.',
 'दैनिक जागरण ने 2025 तक सभी गांवों को बिजली देने का OP चौधरी का वादा रिपोर्ट किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 12 HOUR), SHA2('jagran001opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Reddit', 'https://reddit.com/r/india/comments/abc1', 'u/cg_citizen_2024',
 'Has anyone else noticed that OP Choudhary has been unusually quiet about the Raigarh coal scam allegations? Three questions raised in Vidhan Sabha, zero answers. Classic BJP stonewalling.',
 'en', 12000, '{"likes":342,"comments":89}',
 'negative', 0.22, 'critical', '["corruption-allegation","governance","law-order"]', '["OP Choudhary","Raigarh"]',
 0, 0, 0, 'Reddit user questions OP Choudhary''s silence on Raigarh coal scam allegations.',
 'रेडिट यूजर ने रायगढ़ कोल घोटाले पर OP चौधरी की चुप्पी पर सवाल उठाया।',
 'monitor', NOW(), DATE_SUB(NOW(), INTERVAL 9 HOUR), SHA2('reddit001opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Twitter/X', 'https://twitter.com/i/web/status/1005', '@NamoSupporters',
 'Finance Minister OP Choudhary Ji has allocated ₹8,400 crore for tribal education in the new budget. Under Modi Ji''s leadership, BJP governments are transforming tribal India. Share this! 🇮🇳 #OPChoudhary #BJPDevelopment',
 'en', 2300000, '{"likes":12400,"shares":5600,"comments":890,"views":780000}',
 'positive', 0.94, 'promotional', '["tribal-welfare","budget-finance","party-politics"]', '["OP Choudhary","OPChoudhary","BJP Chhattisgarh"]',
 0, 0, 0, 'NaMo Supporters amplify OP Choudhary''s tribal education budget allocation.',
 'NaMo Supporters ने OP चौधरी के आदिवासी शिक्षा बजट आवंटन को प्रमोट किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 3 HOUR), SHA2('tweet1005opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Facebook', 'https://facebook.com/post/104', 'Congress Chhattisgarh',
 'OP चौधरी जी, आपके बजट में किसानों के लिए क्या है? धान की MSP बढ़ाने का वादा कब पूरा होगा? रायगढ़ के किसान आपसे जवाब मांग रहे हैं। #KisanVirodhi #BJP',
 'hi', 180000, '{"likes":2890,"shares":1200,"comments":567}',
 'negative', 0.14, 'critical', '["opposition-attack","governance","party-politics"]', '["OP Choudhary","Raigarh","छत्तीसगढ़ वित्त मंत्री"]',
 1, 1, 0, 'Congress Chhattisgarh attacks OP Choudhary over farmer MSP promises.',
 'कांग्रेस छत्तीसगढ़ ने किसान MSP वादों पर OP चौधरी पर हमला किया।',
 'respond', NOW(), DATE_SUB(NOW(), INTERVAL 6 HOUR), SHA2('fb104opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'News', 'https://bhaskar.com/op-choudhary-2', 'Dainik Bhaskar',
 'रायगढ़ में OP चौधरी की पहल से 200 आदिवासी युवाओं को सरकारी नौकरी। वित्त मंत्री ने कहा - युवाओं का भविष्य हमारी प्राथमिकता।',
 'hi', 4800000, '{"likes":1560,"shares":780,"comments":234,"views":340000}',
 'positive', 0.82, 'informational', '["youth-employment","tribal-welfare","governance"]', '["OP Choudhary","Raigarh","ओपी चौधरी"]',
 1, 0, 0, 'Dainik Bhaskar reports 200 tribal youth get government jobs through OP Choudhary initiative.',
 'दैनिक भास्कर ने 200 आदिवासी युवाओं को सरकारी नौकरी मिलने की रिपोर्ट की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 10 HOUR), SHA2('bhaskar001opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'YouTube', 'https://youtube.com/watch?v=def456', 'Lallantop',
 'OP Choudhary - Chhattisgarh का नया Budget Hero या PR स्टंट? | Budget 2024 Analysis',
 'hi', 6200000, '{"views":820000,"likes":14500,"comments":3400}',
 'neutral', 0.50, 'satirical', '["budget-finance","governance","media-coverage"]', '["OP Choudhary","Chhattisgarh"]',
 1, 0, 0, 'Lallantop analyses whether OP Choudhary is a real budget hero or PR stunt.',
 'Lallantop ने OP चौधरी के बजट की वास्तविकता का विश्लेषण किया।',
 'monitor', NOW(), DATE_SUB(NOW(), INTERVAL 15 HOUR), SHA2('yt002opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Twitter/X', 'https://twitter.com/i/web/status/1006', '@factcheckindia',
 'CLAIM: OP Choudhary said Chhattisgarh achieved 100% toilet coverage. FACT: NFHS-5 data shows 34% households in rural Chhattisgarh still lack toilets. The minister''s claim is FALSE.',
 'en', 450000, '{"likes":3200,"shares":4500,"comments":890,"views":320000}',
 'negative', 0.06, 'critical', '["governance","corruption-allegation","media-coverage"]', '["OP Choudhary","Chhattisgarh"]',
 1, 0, 1, 'FactCheck India labels OP Choudhary''s toilet coverage claim as false.',
 'FactCheck India ने OP चौधरी के शौचालय कवरेज दावे को झूठा बताया।',
 'escalate', NOW(), DATE_SUB(NOW(), INTERVAL 4 HOUR), SHA2('tweet1006opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'News', 'https://theprint.in/op-choudhary-2', 'ThePrint',
 'OP Choudhary''s Chhattisgarh collects highest GST revenue in state history — ₹2,890 crore in Q2 2024, up 22% YoY. Finance ministry credits simplified filing and anti-evasion drive.',
 'en', 2800000, '{"likes":890,"shares":340,"comments":123,"views":120000}',
 'positive', 0.81, 'informational', '["budget-finance","governance"]', '["OP Choudhary","Chhattisgarh Finance Minister"]',
 1, 0, 0, 'The Print reports record GST collection in Chhattisgarh under OP Choudhary.',
 'द प्रिंट ने OP चौधरी के कार्यकाल में छत्तीसगढ़ की रिकॉर्ड GST वसूली रिपोर्ट की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 20 HOUR), SHA2('print002opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Twitter/X', 'https://twitter.com/i/web/status/1007', '@KapilMishra_IND',
 'Congress ruled Chhattisgarh for 15 years — gave us corruption and poverty. BJP''s OP Choudhary in just 8 months has allocated highest ever funds for tribal development. RESULTS speak for themselves. 🙏 @OPChoudhary',
 'en', 890000, '{"likes":8900,"shares":3400,"comments":450,"views":290000}',
 'positive', 0.88, 'promotional', '["party-politics","tribal-welfare","governance"]', '["OP Choudhary","OPChoudhary","BJP Chhattisgarh"]',
 1, 0, 0, 'Kapil Mishra praises OP Choudhary''s tribal development allocation vs Congress record.',
 'कपिल मिश्रा ने कांग्रेस रिकॉर्ड के मुकाबले OP चौधरी के आदिवासी विकास आवंटन की प्रशंसा की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 18 HOUR), SHA2('tweet1007opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Other', NULL, 'Raigarh Samachar Group',
 'आज OP चौधरी साहब ने रायगढ़ के किसान मेले का उद्घाटन किया। हमारे इलाके के 3000 किसान भाग ले रहे हैं। मंत्री जी ने खाद और बीज की नई सब्सिडी की घोषणा की। जय किसान!',
 'hi', 28000, '{"members":28000,"forwards":445}',
 'positive', 0.86, 'informational', '["governance","tribal-welfare","social-praise"]', '["OP Choudhary","Raigarh","ओपी चौधरी"]',
 0, 0, 0, 'Raigarh Telegram group shares positive coverage of OP Choudhary farmer fair and subsidy announcement.',
 'रायगढ़ टेलीग्राम ग्रुप में किसान मेले और नई सब्सिडी की सकारात्मक जानकारी।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 6 HOUR), SHA2('tg002opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Twitter/X', 'https://twitter.com/i/web/status/1008', '@bdutt',
 'Spoke to Finance Minister @OPChoudhary for 30 mins today. He is clearly passionate about Chhattisgarh''s tribal economy. Whether the passion translates to ground impact — that is the question. Full interview tonight at 9.',
 'en', 3800000, '{"likes":5600,"shares":1200,"comments":780,"views":340000}',
 'neutral', 0.58, 'informational', '["media-coverage","governance","tribal-welfare"]', '["OP Choudhary","OPChoudhary"]',
 1, 0, 0, 'Barkha Dutt interviews OP Choudhary, notes his passion for tribal economy.',
 'बरखा दत्त ने OP चौधरी का साक्षात्कार लिया, आदिवासी अर्थव्यवस्था के प्रति उत्साह नोट किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 22 HOUR), SHA2('tweet1008opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'News', 'https://amarujala.com/op-choudhary', 'Amar Ujala',
 'छत्तीसगढ़ में विपक्ष का हमला: कांग्रेस ने OP चौधरी पर लगाया आरोप — बजट में आंकड़े फर्जी हैं, धरातल पर कुछ नहीं। विपक्ष ने विधानसभा में नोटिस दिया।',
 'hi', 3100000, '{"likes":1200,"shares":890,"comments":345,"views":230000}',
 'negative', 0.21, 'critical', '["opposition-attack","budget-finance","governance"]', '["OP Choudhary","छत्तीसगढ़ वित्त मंत्री"]',
 1, 1, 0, 'Amar Ujala reports Congress opposition attacking OP Choudhary over budget figures.',
 'अमर उजाला ने कांग्रेस के OP चौधरी पर बजट आंकड़ों को लेकर हमले की रिपोर्ट की।',
 'respond', NOW(), DATE_SUB(NOW(), INTERVAL 16 HOUR), SHA2('ujala001opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Twitter/X', 'https://twitter.com/i/web/status/1009', '@sudhapai',
 'OP Choudhary''s Chhattisgarh budget is an interesting case — high allocation for tribal welfare but weak institutional mechanism for delivery. The governance gap between allocation and absorption remains a structural challenge for BJP state governments.',
 'en', 85000, '{"likes":890,"shares":234,"comments":78,"views":45000}',
 'neutral', 0.52, 'informational', '["governance","tribal-welfare","budget-finance"]', '["OP Choudhary","Chhattisgarh Finance Minister"]',
 0, 0, 0, 'Academic Prof. Sudha Pai notes governance delivery gap in OP Choudhary budget.',
 'अकादमिक प्रो. सुधा पाई ने OP चौधरी बजट में गवर्नेंस डिलीवरी गैप नोट किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 28 HOUR), SHA2('tweet1009opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'Facebook', 'https://facebook.com/post/105', 'Raigarh Youth Forum',
 'OP Choudhary Sir ne hamare gaon mein naya school building ka work start karwaya! Finally 2 saal ki demand puri hui. Thank you Minister Ji! ❤️ #Raigarh #Development',
 'hi', 12000, '{"likes":1890,"shares":345,"comments":234}',
 'positive', 0.93, 'emotional', '["social-praise","governance","youth-employment"]', '["OP Choudhary","Raigarh"]',
 0, 0, 0, 'Local Raigarh youth forum praises OP Choudhary for delivering school building work.',
 'स्थानीय युवा मंच ने स्कूल भवन निर्माण के लिए OP चौधरी की प्रशंसा की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 11 HOUR), SHA2('fb105opc', 256)),

(UUID(), 'c1000000-0000-4000-8000-000000000001', 'News', 'https://indiatoday.com/op-choudhary-1', 'India Today',
 'Rising star or risky bet? OP Choudhary emerges as BJP''s face in Chhattisgarh politics. Analysis of his first year as Finance Minister shows strong economic metrics but communication gaps.',
 'en', 35000000, '{"likes":4500,"shares":2300,"comments":890,"views":1200000}',
 'neutral', 0.60, 'informational', '["governance","media-coverage","budget-finance"]', '["OP Choudhary","Chhattisgarh Finance Minister"]',
 1, 0, 0, 'India Today profiles OP Choudhary as BJP''s rising face in Chhattisgarh.',
 'India Today ने OP चौधरी को छत्तीसगढ़ में BJP के उभरते चेहरे के रूप में प्रोफाइल किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 24 HOUR), SHA2('it001opc', 256)),

-- Ashish Shelar mentions
(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Twitter/X', 'https://twitter.com/i/web/status/2001', '@AshishShelar',
 'Mumbai North-West ke har ek nagrik ke liye kaam karna mera sankalp hai. Aaj Vile Parle mein 500 naye streetlights lagwaye. Andhera ab nahi rahega! #Mumbai #AshishShelar #BJPMumbai',
 'hi', 450000, '{"likes":8900,"shares":2300,"comments":678,"views":290000}',
 'positive', 0.91, 'promotional', '["governance","social-praise","party-politics"]', '["Ashish Shelar","AshishShelar","Mumbai North-West"]',
 1, 0, 0, 'Ashish Shelar announces 500 new streetlights installed in Vile Parle.',
 'आशीष शेलार ने विले पार्ले में 500 नई स्ट्रीटलाइट लगाने की घोषणा की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 3 HOUR), SHA2('tweet2001ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Twitter/X', 'https://twitter.com/i/web/status/2002', '@AUThackeray',
 'Ashish Shelar has been MLA from Vile Parle for 4 terms. Still no solution for the chronic flooding in Juhu-Koliwada. Every monsoon same suffering, same promises, zero delivery. Shame!',
 'en', 2100000, '{"likes":12400,"shares":5600,"comments":2300,"views":890000}',
 'negative', 0.08, 'critical', '["opposition-attack","governance","law-order"]', '["Ashish Shelar","Mumbai North-West","Vile Parle MLA"]',
 1, 1, 1, 'Aditya Thackeray attacks Ashish Shelar over 4-term failure to fix Juhu flooding.',
 'आदित्य ठाकरे ने जुहू बाढ़ समस्या पर आशीष शेलार पर 4 कार्यकाल की विफलता का हमला किया।',
 'escalate', NOW(), DATE_SUB(NOW(), INTERVAL 1 HOUR), SHA2('tweet2002ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'News', 'https://hindustan-times.com/ashish-shelar-1', 'Hindustan Times',
 'Ashish Shelar inaugurates ₹180 crore flyover project at DN Nagar, Mumbai. The project is expected to cut travel time between Vile Parle and Andheri by 25 minutes during peak hours.',
 'en', 8900000, '{"likes":2300,"shares":890,"comments":345,"views":450000}',
 'positive', 0.79, 'informational', '["governance","social-praise"]', '["Ashish Shelar","Mumbai North-West","Vile Parle MLA"]',
 1, 0, 0, 'Hindustan Times covers Ashish Shelar inaugurating ₹180 crore flyover in Mumbai.',
 'हिंदुस्तान टाइम्स ने मुंबई में ₹180 करोड़ के फ्लाईओवर उद्घाटन को कवर किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 8 HOUR), SHA2('ht001ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Facebook', 'https://facebook.com/post/201', 'BJP Mumbai',
 'श्री आशीष शेलार जी के नेतृत्व में मुंबई उत्तर-पश्चिम में 1000 से अधिक महिलाओं को स्वयं सहायता समूह से जोड़ा गया। मोदी जी के नेतृत्व में महिला सशक्तिकरण का यही है असली चेहरा! 🙏',
 'hi', 890000, '{"likes":12300,"shares":4500,"comments":890}',
 'positive', 0.90, 'promotional', '["women-welfare","governance","party-politics"]', '["Ashish Shelar","आशीष शेलार","BJP Mumbai"]',
 1, 0, 0, 'BJP Mumbai Facebook promotes Ashish Shelar connecting 1000+ women to SHGs.',
 'BJP मुंबई ने 1000+ महिलाओं को SHG से जोड़ने में आशीष शेलार की प्रशंसा की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 5 HOUR), SHA2('fb201ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Twitter/X', 'https://twitter.com/i/web/status/2003', '@priyankac21',
 'Ashish Shelar was Education Minister — Mumbai schools had one of the highest dropout rates in Maharashtra under his watch. Now he wants another term? Voters of Vile Parle deserve better. #MaharashtraElections',
 'en', 1200000, '{"likes":7800,"shares":3400,"comments":1200,"views":380000}',
 'negative', 0.11, 'critical', '["opposition-attack","party-politics","youth-employment"]', '["Ashish Shelar","Vile Parle MLA","BJP Mumbai"]',
 1, 1, 0, 'Priyanka Chaturvedi attacks Ashish Shelar''s education record as Mumbai school dropout rates rise.',
 'प्रियंका चतुर्वेदी ने मुंबई स्कूल ड्रॉपआउट दरों पर आशीष शेलार की आलोचना की।',
 'respond', NOW(), DATE_SUB(NOW(), INTERVAL 2 HOUR), SHA2('tweet2003ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'YouTube', 'https://youtube.com/watch?v=ghi789', 'ABP Majha',
 'Ashish Shelar Mumbai Interview FULL | Vile Parle विकास आणि भविष्याच्या योजना | ABP Majha',
 'hi', 4200000, '{"views":380000,"likes":9200,"comments":1890}',
 'positive', 0.71, 'informational', '["governance","media-coverage","party-politics"]', '["Ashish Shelar","आशीष शेलार","Mumbai North-West"]',
 1, 0, 0, 'ABP Majha airs full interview of Ashish Shelar on Vile Parle development plans.',
 'ABP माझाने विले पार्ले विकास योजनाओं पर आशीष शेलार का पूरा साक्षात्कार प्रसारित किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 14 HOUR), SHA2('yt003ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Instagram', 'https://instagram.com/p/bcd002', '@AshishShelar',
 'आज गणेशोत्सव की शुभकामनाएं! विले पार्ले में हमारी गणेश मंडलियों के साथ यह पल अविस्मरणीय है। मुंबई की संस्कृति और एकता ही हमारी शक्ति है। 🙏🎉 #GaneshChaturthi #Mumbai',
 'hi', 234000, '{"likes":28900,"comments":1240}',
 'positive', 0.95, 'emotional', '["social-praise","party-politics"]', '["Ashish Shelar","आशीष शेलार","Mumbai North-West"]',
 1, 0, 0, 'Ashish Shelar Instagram post on Ganesh Chaturthi with Vile Parle community.',
 'आशीष शेलार ने विले पार्ले समुदाय के साथ गणेशोत्सव का इंस्टाग्राम पोस्ट शेयर किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 7 HOUR), SHA2('ig002ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Other', NULL, 'Mumbai BJP WhatsApp',
 'Ashish Shelar bhai ne aaj Vile Parle mein water pipeline kaam ka inspection kiya. 3 saal se problem thi, aakhir kaam shuru ho gaya. 👏 Share karo sabko!',
 'hi', 35000, '{"members":35000,"forwards":890}',
 'positive', 0.87, 'informational', '["governance","social-praise"]', '["Ashish Shelar","Vile Parle MLA","BJP Mumbai"]',
 0, 0, 0, 'Mumbai BJP WhatsApp group shares Ashish Shelar''s water pipeline inspection.',
 'मुंबई BJP WhatsApp ग्रुप ने आशीष शेलार के वाटर पाइपलाइन निरीक्षण की जानकारी शेयर की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 4 HOUR), SHA2('tg003ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'News', 'https://mid-day.com/ashish-shelar', 'Mid-Day',
 'Vile Parle residents call out Ashish Shelar: "4 terms but Juhu beach still floods every monsoon." Residents association submits memorandum demanding flood mitigation plan.',
 'en', 1200000, '{"likes":3400,"shares":1800,"comments":560,"views":280000}',
 'negative', 0.19, 'critical', '["governance","opposition-attack","law-order"]', '["Ashish Shelar","Vile Parle MLA","Mumbai North-West"]',
 1, 0, 0, 'Mid-Day reports Vile Parle residents blaming Ashish Shelar for Juhu flooding across 4 terms.',
 'Mid-Day ने विले पार्ले निवासियों द्वारा जुहू बाढ़ के लिए आशीष शेलार को जिम्मेदार ठहराने की रिपोर्ट की।',
 'respond', NOW(), DATE_SUB(NOW(), INTERVAL 18 HOUR), SHA2('midday001ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Twitter/X', 'https://twitter.com/i/web/status/2004', '@ndtvindia',
 'BREAKING: Maharashtra Education Minister Ashish Shelar announces 100% textbook distribution before school year starts — first time in 15 years. Over 85 lakh students to benefit. #Maharashtra #Education',
 'en', 12000000, '{"likes":15600,"shares":8900,"comments":2100,"views":1800000}',
 'positive', 0.88, 'informational', '["governance","youth-employment","social-praise"]', '["Ashish Shelar","आशीष शेलार","BJP Mumbai"]',
 1, 0, 1, 'NDTV breaks news of Ashish Shelar achieving 100% textbook distribution for first time in 15 years.',
 'NDTV ने 15 साल में पहली बार 100% पाठ्यपुस्तक वितरण की आशीष शेलार की उपलब्धि रिपोर्ट की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 26 HOUR), SHA2('tweet2004ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Reddit', 'https://reddit.com/r/mumbai/comments/def2', 'u/mumbaikar_2024',
 'Genuinely curious — has Ashish Shelar ever addressed the Versova fishing community issues? They have been complaining for years about the harbour development project stalling. Any updates?',
 'en', 8000, '{"likes":45,"comments":23}',
 'neutral', 0.50, 'informational', '["governance","law-order"]', '["Ashish Shelar","Mumbai North-West"]',
 0, 0, 0, 'Reddit user asks about Ashish Shelar''s response to Versova fishing community concerns.',
 'रेडिट यूजर ने वर्सोवा मछुआरा समुदाय पर आशीष शेलार की प्रतिक्रिया पूछी।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 32 HOUR), SHA2('reddit002ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'News', 'https://loksatta.com/ashish-shelar', 'Loksatta',
 'आशीष शेलार यांनी मुंबई उत्तर-पश्चिम मतदारसंघात 500 कोटींच्या विकासकामांचे उद्घाटन केले. यात रस्ते, पाणी, वीज अशा मूलभूत सुविधांचा समावेश आहे.',
 'hi', 1800000, '{"likes":4500,"shares":1200,"comments":456,"views":290000}',
 'positive', 0.83, 'informational', '["governance","social-praise"]', '["Ashish Shelar","आशीष शेलार","Mumbai North-West"]',
 1, 0, 0, 'Loksatta reports Ashish Shelar inaugurating ₹500 crore development works in constituency.',
 'लोकसत्ताने आशीष शेलार द्वारा मतदारसंघात ₹500 करोड़ के विकास कार्यों के उद्घाटन की रिपोर्ट की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 36 HOUR), SHA2('loksatta001ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Twitter/X', 'https://twitter.com/i/web/status/2005', '@dhruv_rathee',
 'Mumbai''s BJP MLA Ashish Shelar was Education Minister for 5 years. Under his tenure — Maharashtra dropped from rank 7 to rank 14 in ASER educational outcomes. But sure, let''s make him MLA again. 🙄 #Maharashtra',
 'en', 18500000, '{"likes":34500,"shares":18900,"comments":5600,"views":3200000}',
 'negative', 0.04, 'critical', '["opposition-attack","youth-employment","governance"]', '["Ashish Shelar","BJP Mumbai","Vile Parle MLA"]',
 1, 1, 1, 'Dhruv Rathee viral tweet attacking Ashish Shelar''s education ministry performance.',
 'धृव राठी ने आशीष शेलार की शिक्षा मंत्रालय में प्रदर्शन पर वायरल ट्वीट किया।',
 'escalate', NOW(), DATE_SUB(NOW(), INTERVAL 45 MINUTE), SHA2('tweet2005ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Facebook', 'https://facebook.com/post/202', 'Shiv Sena UBT Mumbai',
 'आशीष शेलार 4 वेळा आमदार. Juhu, Versova, Vile Parle मध्ये पाणी तुंबणे थांबलेले नाही. फक्त फोटो आणि घोषणा. मुंबईकरांनो, बदल घडवा! #ShivSena #Mumbai',
 'hi', 320000, '{"likes":8900,"shares":3400,"comments":1200}',
 'negative', 0.10, 'critical', '["opposition-attack","party-politics","governance"]', '["Ashish Shelar","आशीष शेलार","Mumbai North-West"]',
 1, 1, 0, 'Shiv Sena UBT Facebook attacks Ashish Shelar''s 4-term flooding failure.',
 'शिव सेना UBT ने 4 कार्यकाल की बाढ़ विफलता पर आशीष शेलार पर फेसबुक हमला किया।',
 'respond', NOW(), DATE_SUB(NOW(), INTERVAL 6 HOUR), SHA2('fb202ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'News', 'https://timesofindia.com/ashish-shelar-1', 'Times of India',
 'Ashish Shelar launches ''Mumbai Reads'' initiative, distributing 2 lakh books to underprivileged children across Mumbai North-West. MLA says education is his personal mission.',
 'en', 15000000, '{"likes":5600,"shares":2300,"comments":678,"views":780000}',
 'positive', 0.85, 'informational', '["youth-employment","governance","social-praise"]', '["Ashish Shelar","Mumbai North-West","Vile Parle MLA"]',
 1, 0, 0, 'Times of India reports Ashish Shelar distributing 2 lakh books in Mumbai Reads initiative.',
 'टाइम्स ऑफ इंडिया ने मुंबई रीड्स में आशीष शेलार द्वारा 2 लाख किताबें वितरण की रिपोर्ट की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 48 HOUR), SHA2('toi001ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Twitter/X', 'https://twitter.com/i/web/status/2006', '@NamoSupporters',
 'Mumbai se Good News! BJP''s @AshishShelar has completed 100% road repair work in Vile Parle before monsoon — something NO MLA has achieved before! This is Modi Ji''s Gujarat model working in Mumbai. 🙏 #BJPDelivers',
 'en', 2300000, '{"likes":18900,"shares":8900,"comments":1200,"views":1200000}',
 'positive', 0.93, 'promotional', '["governance","social-praise","party-politics"]', '["Ashish Shelar","AshishShelar","BJP Mumbai"]',
 0, 0, 0, 'NaMo Supporters amplify Ashish Shelar''s 100% road repair completion before monsoon.',
 'NaMo Supporters ने मानसून से पहले 100% सड़क मरम्मत पूर्ण करने पर आशीष शेलार की प्रशंसा की।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 20 HOUR), SHA2('tweet2006ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Instagram', 'https://instagram.com/p/bcd003', 'BJP Mumbai Official',
 'आशीष शेलार जी ने आज Vile Parle में नया sports complex का भूमिपूजन किया। मुंबई के युवाओं के लिए यह तोहफा है। ₹45 करोड़ का प्रोजेक्ट, 2025 तक ready। 🏋️‍♂️⚽ #Mumbai #Youth',
 'hi', 560000, '{"likes":34500,"comments":2340}',
 'positive', 0.89, 'promotional', '["youth-employment","governance","social-praise"]', '["Ashish Shelar","आशीष शेलार","Vile Parle MLA"]',
 1, 0, 0, 'BJP Mumbai Instagram promotes Ashish Shelar groundbreaking for ₹45 crore sports complex.',
 'BJP मुंबई ने ₹45 करोड़ के स्पोर्ट्स कॉम्प्लेक्स के भूमिपूजन में आशीष शेलार को प्रमोट किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 30 HOUR), SHA2('ig003ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'News', 'https://indiatoday.com/ashish-shelar-1', 'India Today',
 'Maharashtra''s Ashish Shelar: How the 4-term MLA is reinventing his political brand ahead of assembly elections. From education controversies to infrastructure wins — an India Today deep-dive.',
 'en', 35000000, '{"likes":8900,"shares":4500,"comments":1890,"views":2300000}',
 'neutral', 0.55, 'informational', '["media-coverage","party-politics","governance"]', '["Ashish Shelar","Mumbai North-West","Vile Parle MLA"]',
 1, 0, 0, 'India Today deep-dive on Ashish Shelar''s political brand reinvention ahead of elections.',
 'India Today ने चुनाव से पहले आशीष शेलार के राजनीतिक ब्रांड पुनर्निर्माण का विश्लेषण किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 40 HOUR), SHA2('it002ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Twitter/X', 'https://twitter.com/i/web/status/2007', '@ravishkumar',
 'A constituent from Vile Parle writes to me: "I have voted for Shelar saab 4 times. But the nullah outside my building has been the same for 20 years. When will development reach the last person?" — The real question.',
 'en', 5200000, '{"likes":18900,"shares":9800,"comments":3400,"views":890000}',
 'negative', 0.16, 'emotional', '["governance","opposition-attack","social-praise"]', '["Ashish Shelar","Vile Parle MLA","Mumbai North-West"]',
 1, 0, 1, 'Ravish Kumar shares viral constituent letter questioning Ashish Shelar''s development delivery.',
 'रवीश कुमार ने वायरल नागरिक पत्र साझा किया जो आशीष शेलार के विकास पर सवाल उठाता है।',
 'escalate', NOW(), DATE_SUB(NOW(), INTERVAL 90 MINUTE), SHA2('tweet2007ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'News', 'https://theprint.in/ashish-shelar-1', 'ThePrint',
 'Opinion: Ashish Shelar''s focus on education infrastructure during his ministerial tenure set the stage for Maharashtra''s school digitisation. Critics ignore these systemic wins.',
 'en', 2800000, '{"likes":2300,"shares":890,"comments":345,"views":290000}',
 'positive', 0.73, 'informational', '["governance","youth-employment","media-coverage"]', '["Ashish Shelar","BJP Mumbai"]',
 1, 0, 0, 'ThePrint opinion defends Ashish Shelar''s education infrastructure legacy.',
 'द प्रिंट ने आशीष शेलार की शिक्षा बुनियादी ढांचे की विरासत का बचाव किया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 52 HOUR), SHA2('print003ash', 256)),

(UUID(), 'c2000000-0000-4000-8000-000000000002', 'Other', NULL, 'Vile Parle Residents Group',
 'Shelar saab aaj bade aaye the yahan. Bolte hain parking problem solve ho jayegi 6 months mein. Hum wait kar rahe hain... 😒 Waise unki rally mein bheed thi. Kuch log khush bhi the.',
 'hi', 18000, '{"members":18000,"forwards":230}',
 'neutral', 0.50, 'emotional', '["governance","social-praise"]', '["Ashish Shelar","Vile Parle MLA"]',
 0, 0, 0, 'Vile Parle residents Telegram group with mixed reaction to Ashish Shelar parking promise.',
 'विले पार्ले रेसिडेंट टेलीग्राम ग्रुप में पार्किंग वादे पर मिलीजुली प्रतिक्रिया।',
 'none', NOW(), DATE_SUB(NOW(), INTERVAL 5 HOUR), SHA2('tg004ash', 256));

-- Alerts
INSERT IGNORE INTO alerts (id, client_id, alert_type, severity, title, description, trigger_data, is_read, triggered_at) VALUES
('a1000000-0000-4000-8000-000000000001', 'c1000000-0000-4000-8000-000000000001',
 'viral_negative', 'critical',
 'Dhruv Rathee viral thread alleging coal mafia protection by OP Choudhary',
 'A thread by @dhruv_rathee (18.5M followers) alleging OP Choudhary failed to act on coal mafia in Raigarh has crossed 28,000 likes and 12,000 shares within 2 hours. Immediate counter-narrative required.',
 '{"mention_id":"tweet1002opc","author":"@dhruv_rathee","reach":18500000,"likes":28400,"shares":12300}',
 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),

('a2000000-0000-4000-8000-000000000001', 'c1000000-0000-4000-8000-000000000001',
 'opposition_attack', 'high',
 'FactCheck India labels Finance Minister''s toilet coverage claim FALSE',
 '@factcheckindia (450K followers) has fact-checked OP Choudhary''s claim of 100% toilet coverage and labelled it false citing NFHS-5 data. Post has 4,500 shares. Media pickup risk is high within 24 hours.',
 '{"mention_id":"tweet1006opc","author":"@factcheckindia","shares":4500}',
 0, DATE_SUB(NOW(), INTERVAL 4 HOUR)),

('a3000000-0000-4000-8000-000000000001', 'c1000000-0000-4000-8000-000000000001',
 'coordinated_campaign', 'high',
 'Opposition coordinated attack on Raigarh Digital project — Rajya Sabha question planned',
 'Priyanka Chaturvedi (RS MP) has posted about Digital Raigarh corruption and announced an RS question. Combined with Dhruv Rathee content and Congress Facebook posts, this shows a coordinated 3-front attack.',
 '{"accounts":["@priyankac21","@dhruv_rathee","Congress Chhattisgarh"],"rs_question":true}',
 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),

('a4000000-0000-4000-8000-000000000002', 'c2000000-0000-4000-8000-000000000002',
 'viral_negative', 'critical',
 'Dhruv Rathee viral tweet — Ashish Shelar''s education rank drop claim crossing 34,000 likes',
 '@dhruv_rathee''s tweet alleging Maharashtra dropped from rank 7 to 14 in education under Shelar has 34,500 likes, 18,900 shares and 3.2M views. This is the most viral negative content targeting the client this month.',
 '{"mention_id":"tweet2005ash","author":"@dhruv_rathee","reach":18500000,"likes":34500,"shares":18900,"views":3200000}',
 0, DATE_SUB(NOW(), INTERVAL 45 MINUTE)),

('a5000000-0000-4000-8000-000000000002', 'c2000000-0000-4000-8000-000000000002',
 'spike', 'high',
 'Negative mention spike around Juhu flooding — 3 coordinated sources in 2 hours',
 'Three high-reach posts (Aditya Thackeray, Ravish Kumar, Shiv Sena UBT) targeting Ashish Shelar over Juhu flooding have appeared within 90 minutes. Combined reach: 8.5M. This is a likely coordinated pre-election attack.',
 '{"sources":["@AUThackeray","@ravishkumar","Shiv Sena UBT"],"combined_reach":8500000}',
 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),

('a6000000-0000-4000-8000-000000000002', 'c2000000-0000-4000-8000-000000000002',
 'positive_milestone', 'low',
 'NDTV breaking news on 100% textbook distribution — 1.8M views and climbing',
 'NDTV''s breaking news coverage of Ashish Shelar''s textbook achievement has 15,600 likes and 1.8M views. This is organic positive coverage with high amplification potential. Consider boosting this content.',
 '{"mention_id":"tweet2004ash","author":"@ndtvindia","views":1800000,"likes":15600}',
 1, DATE_SUB(NOW(), INTERVAL 26 HOUR));

-- Actions
INSERT IGNORE INTO actions (id, client_id, action_type, status, input_context, output_content, output_language, created_at) VALUES
('ac100000-0000-4000-8000-000000000001', 'c1000000-0000-4000-8000-000000000001',
 'counter_brief', 'completed',
 '{"alert_type":"viral_negative","attacker":"@dhruv_rathee","claim":"coal mafia not stopped"}',
 '# Counter Brief: Responding to @dhruv_rathee Coal Mafia Claims

## Attack Summary
YouTuber @dhruv_rathee (18.5M followers) has posted a viral thread (28K likes, 12K shares) alleging that Finance Minister OP Choudhary promised to end coal mafia in Raigarh but took no action in 8 months.

## Factual Corrections

1. **Mining enforcement has increased, not decreased.** Under FM Choudhary''s tenure, the Chhattisgarh Mineral Development Authority (CMDA) conducted 847 enforcement drives in Raigarh district — a 340% increase over the previous year.

2. **Revenue recovery.** ₹184 crore in mining dues recovered in 8 months vs ₹23 crore in the corresponding period under previous government. Data available from Mines Directorate press releases dated March 2024.

3. **FIRs filed.** 67 FIRs registered against illegal mining operators, 23 arrests made, 145 trucks and 12 excavators seized. Source: Raigarh SP press conference, November 2023.

4. **Systemic reform underway.** The Coal and Mineral Governance Reform Bill (CG, 2024) is currently in committee stage — the most comprehensive anti-mafia legislation in state history.

## Five Talking Points for Your Team

1. "Dhruv Rathee''s claims are based on zero data. We have 847 enforcement drives, ₹184 crore recovered, 67 FIRs. Ask him to check official CMDA records."
2. "Congress ran Chhattisgarh for 15 years. They created this problem. We are cleaning it up in months, not decades."
3. "Real reform takes institutional change, not Twitter videos. Our Mineral Governance Reform Bill is the first of its kind in India."
4. "The same people who protected coal mafia for 15 years are now using influencers to spread misinformation. The people of Raigarh know the truth."
5. "We welcome any fact-checker to visit our enforcement camps in Raigarh. The gates are open."

## Recommended Public Statement (2-3 sentences)
*"Under Finance Minister OP Choudhary, Chhattisgarh has conducted 847 enforcement drives, recovered ₹184 crore in mining dues, and registered 67 FIRs — all in 8 months. The systematic dismantling of illegal mining networks requires institutional change, not viral threads. We invite @dhruv_rathee to visit Raigarh and speak directly with the mining enforcement teams working on the ground."*

## Social Media Drafts

**Twitter/X:**
"847 enforcement drives. ₹184 Cr recovered. 67 FIRs. 23 arrests. This is what fighting coal mafia looks like. Facts > viral threads. Data from CMDA available publicly. @dhruv_rathee — the gates of Raigarh are open. Come see for yourself. #Raigarh #FactsMatter"

**Facebook:**
"कुछ लोग YouTube पर झूठ फैलाते हैं, हम रायगढ़ में काम करते हैं। 8 महीने में 847 mining enforcement drives, ₹184 करोड़ recovery, 67 FIR। यही है असली action। कोई भी आकर देख सकता है।"

## Do-Not-Say List
- Do not name Dhruv Rathee directly in official statements (amplifies him)
- Do not use aggressive language — stick to data
- Do not make promises about timeline ("we will fix this by X") without verification
- Do not dispute the existence of coal mafia — acknowledge it and emphasise action',
 'English',
 DATE_SUB(NOW(), INTERVAL 3 HOUR)),

('ac200000-0000-4000-8000-000000000001', 'c1000000-0000-4000-8000-000000000001',
 'rapid_response', 'completed',
 '{"context":"FactCheck India claim about toilet coverage","platform":"Twitter/X"}',
 '[{"tone":"factual_data_driven","text":"NFHS-5 was conducted in 2019-21 under previous govt. Current data (2024 survey) shows 78% coverage in Chhattisgarh, up from 61%. Progress is real and ongoing. @factcheckindia — please update your data. #FactsMatter","character_count":238},{"tone":"firm_assertive","text":"We reject this fact-check. The minister cited 2024 state survey data, not NFHS-5 from 2019. Comparing 5-year-old central data with current state data is misleading. We stand by our numbers and invite any audit. #SwachhBharat","character_count":240},{"tone":"conciliatory_bridge_building","text":"Fair point on data transparency. We will publish the full state sanitation survey 2024 by this Friday. Accountability matters to us. The goal — 100% sanitation — remains our commitment to every family in Chhattisgarh. 🙏","character_count":231}]',
 'English',
 DATE_SUB(NOW(), INTERVAL 5 HOUR)),

('ac300000-0000-4000-8000-000000000002', 'c2000000-0000-4000-8000-000000000002',
 'counter_brief', 'completed',
 '{"alert_type":"viral_negative","attacker":"@dhruv_rathee","claim":"education rank drop under Shelar"}',
 '# Counter Brief: Responding to Education Rank Claims

## Attack Summary
@dhruv_rathee claims Maharashtra dropped from education rank 7 to 14 under Ashish Shelar''s tenure as Education Minister. The post has 34,500 likes and 3.2M views.

## Factual Corrections

1. **ASER rankings measure rural learning outcomes, not urban education quality.** Maharashtra ranks in top 5 states for urban education infrastructure (DISE 2023). @dhruv_rathee has conflated two different metrics.

2. **What changed under Shelar:** School digitisation increased from 8% to 67% of schools having functional digital infrastructure. Maharashtra was ranked #1 for mid-day meal quality by NCPCR in 2022.

3. **100% textbook distribution** — achieved for the first time in 15 years under Shelar. This year, 85 lakh students received books before term began. No previous minister achieved this.

4. **Teacher recruitment:** 32,000 teachers recruited under Shelar, the largest single recruitment in Maharashtra education history.

## Five Talking Points

1. "Dhruv Rathee compared rural learning outcomes data with our urban infrastructure achievements — that is like comparing apples to tractors."
2. "Under Shelar: 67% school digitisation, 32,000 new teachers, 100% textbook distribution. These are verifiable milestones."
3. "Maharashtra is a complex state with 120,000+ schools. Systemic improvement takes years. The foundation was set."
4. "The same opposition that never spent on teacher recruitment is now citing learning outcome data. Where were they for 15 years?"
5. "We challenge anyone to show a more digitised school system than Maharashtra''s today."

## Recommended Statement
*"Ashish Shelar digitised 67% of Maharashtra''s schools, recruited 32,000 teachers, and achieved 100% textbook distribution for the first time in 15 years. These are verifiable facts. We welcome a full audit of our education record."*',
 'English',
 DATE_SUB(NOW(), INTERVAL 1 HOUR)),

('ac400000-0000-4000-8000-000000000002', 'c2000000-0000-4000-8000-000000000002',
 'whatsapp_forward', 'completed',
 '{"topic":"NDTV textbook distribution milestone","audience":"BJP cadre"}',
 '🎉 *बड़ी खबर — मुंबई से!* 🎉

आशीष शेलार जी की बड़ी उपलब्धि!

महाराष्ट्र में 15 साल में पहली बार —
✅ सभी 85 लाख बच्चों को पहले दिन किताबें मिलीं
✅ एक भी बच्चा बिना किताब के क्लास में नहीं गया
✅ NDTV ने इसे "ऐतिहासिक" बताया

जो काम कांग्रेस-NCP 15 साल में नहीं कर सकी,
वो BJP ने कर दिखाया! 💪

*आगे भेजें — हर मुंबईकर को पता होना चाहिए!*

---
*(English: For the first time in 15 years, all 85 lakh Maharashtra students received their textbooks on Day 1 of the school year under Education Minister Ashish Shelar. NDTV called it historic.)*',
 'Hindi',
 DATE_SUB(NOW(), INTERVAL 28 HOUR));

SET foreign_key_checks = 1;
