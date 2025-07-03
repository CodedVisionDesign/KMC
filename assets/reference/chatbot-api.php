<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Rate limiting
require_once 'rate-limiter.php';
if (!checkRateLimit('chatbot', 20, 60)) { // 20 requests per minute
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Please wait before sending another message.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty(trim($userMessage))) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// OpenAI API configuration
$openaiApiKey = $_ENV['OPENAI_API_KEY'] ?? 'YOUR_OPENAI_API_KEY_HERE';
$openaiUrl = 'https://api.openai.com/v1/chat/completions';

// Knowledge base content
$knowledgeBase = "
KRAV MAGA COLCHESTER - COMPREHENSIVE INFORMATION

ADULT CLASSES:
- Monthly fees: £85 unlimited classes, £65 up to 2 classes/week, £40 beginners only (12 weeks max)
- No joining fee, but equipment and KMG licence required
- Equipment needed: Boxing gloves, grappling gloves, shin guards, groin guard, gum shield
- KMG essentials pack available for £116 (includes 1 year licence)
- Insurance: KMG licence required (£30/year minimum)
- Classes: Mon 7-8pm (beginners), Tue 7-8:30pm (all levels), Wed 6:30-7:30pm (beginners), Thu 7-8:30pm (all levels), Fri 7-8pm (bag work), Sat 10-11:30am (sparring)
- Open 48 weeks/year, no annual contract

SENIORS (School Age):
- Monthly fees: £50 up to 3 classes/week, £30 for 1 class/week
- Equipment: Boxing gloves, shin guards, groin guard, gum shield
- KMC teens pack available for £54.99
- Insurance: Junior licence £20/year
- Classes: Tue 4:40-5:40pm, Thu 6-7pm, Fri 6-7pm
- Sparring class: Sat 10-11:30am (by invitation, £7.50/class)

INFANTS:
- Monthly fees: £20 for 1 class/week, £38 for 2 classes/week
- No equipment or uniform required
- Insurance: Junior licence £20/year (after 4 sessions)
- Classes: Tue 4-4:30pm, Thu 4-4:30pm
- Focus on coordination, confidence, basic safety concepts

INSTRUCTOR:
Alex Hunt - Graduate Level 5, qualified instructor since 2018, 180 hours training
Enhanced DBS, BTEC Level 3 Self-defence Instruction, First Aid certified
Wife Kelly assists, also DBS checked and Safeguarding Level 2

CODE OF CONDUCT:
- Personal hygiene essential, no jewellery, appropriate equipment required
- No photography without permission, children must be supervised
- Arrive 5 minutes early, respect training environment

CONTACT:
Location: 7b The Orchards Business Units, Cockaynes Lane, Alresford, CO7 8BZ
Phone: +447376516126
";

// System prompt for the AI
$systemPrompt = "You are a friendly, professional AI assistant for Krav Maga Colchester, a premier self-defence training centre in Essex. Your role is to help potential students learn about our classes and encourage them to try a free trial.

Key personality traits:
- Friendly and approachable but professional
- Use UK spelling and formal language
- Be concise (maximum 4 lines per response)
- Focus on encouraging people to try Krav Maga
- Emphasise safety, professionalism, and the welcoming environment
- Mention the free trial opportunity when appropriate

You have access to comprehensive information about our adult, senior (school-age), and infant classes, including pricing, schedules, equipment requirements, and instructor qualifications. Always provide accurate information from the knowledge base.

When users ask about booking or trials, encourage them to use the trial booking form or contact us directly.

Keep responses warm, helpful, and persuasive while maintaining professionalism.";

// Prepare the API request
$messages = [
    ['role' => 'system', 'content' => $systemPrompt . "\n\nKNOWLEDGE BASE:\n" . $knowledgeBase],
    ['role' => 'user', 'content' => $userMessage]
];

$requestData = [
    'model' => 'gpt-4o-mini',
    'messages' => $messages,
    'max_tokens' => 200,
    'temperature' => 0.7,
    'top_p' => 1,
    'frequency_penalty' => 0,
    'presence_penalty' => 0
];

// Make the API request
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $openaiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openaiApiKey
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(500);
    echo json_encode(['error' => 'Connection error. Please try again.']);
    exit;
}

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode(['error' => 'Service temporarily unavailable. Please try again later.']);
    exit;
}

$apiResponse = json_decode($response, true);

if (!isset($apiResponse['choices'][0]['message']['content'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid response from AI service.']);
    exit;
}

$aiMessage = trim($apiResponse['choices'][0]['message']['content']);

// Return the response
echo json_encode([
    'success' => true,
    'message' => $aiMessage,
    'disclaimer' => 'AI Assistant'
]);
?> 