<?php
/**
 * gemini_voice.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Server-side proxy for Gemini 2.5 Flash multimodal audio parsing.
 * Receives Base64 audio from the browser, calls Gemini, returns structured JSON
 * matching the Rare Cafe database schema (incoming_tea + outgoing_expenses).
 *
 * POST body (JSON): { "audio_base64": "...", "mime_type": "audio/webm" }
 * Response (JSON):  { "transcript": "...", "sales": [...], "expenses": [...] }
 * ─────────────────────────────────────────────────────────────────────────────
 */

session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth guard ────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in first.']);
    exit;
}

// ── Validate input ────────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['audio_base64']) || empty($input['mime_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: audio_base64 and mime_type.']);
    exit;
}

// ── Check API key ─────────────────────────────────────────────────────────────
if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY) || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
    http_response_code(500);
    echo json_encode(['error' => 'Gemini API key is not configured. Open config.php and set GEMINI_API_KEY.']);
    exit;
}

$audio_base64 = $input['audio_base64'];
$mime_type    = trim($input['mime_type']);    // e.g. "audio/webm"

// ── Prompt ────────────────────────────────────────────────────────────────────
$prompt = <<<PROMPT
You are an intelligent voice-to-ledger assistant for a tea shop called "Rare Cafe" in Bangladesh.

Listen carefully to the audio. The speaker may use English, Bangla, or Banglish (a mix of both).

=== EXACT MENU ITEMS (map spoken words to these exact names) ===
1.  Dudh Cha          - milk tea / doodh cha / dudh cha
2.  Lal Cha           - black tea / red tea / lal cha / rong cha
3.  Lebu Cha          - lemon tea / lebu cha
4.  Tetul Cha         - tamarind tea / tetul cha
5.  Horlicks Cha      - Horlicks tea / horlicks cha
6.  Malai Cha         - cream tea / malai cha
7.  Pora Ruti [Small] - small burnt bread / choto pora ruti / small ruti
8.  Pora Ruti [Large] - large burnt bread / baro pora ruti / large ruti
9.  Chicken Singara   - chicken samosa / singara / shingara
10. Dudh Danish       - milk Danish / dudh danish / danish

=== EXTRACTION RULES ===
- sales    -> Any tea or food item sold. Use EXACT menu names above. Extract quantity (default 1 if not stated).
- expenses -> Any purchase, ingredient buy, cost, or bill mentioned. Extract item/ingredient name, quantity description (e.g. "5 kg"), and amount in Bangladeshi Taka (default 0 if not stated).
- transcript -> Verbatim or close transcription of what was said.

Return ONLY a valid JSON object. No markdown, no explanation, just JSON.
PROMPT;

// ── Build Gemini request body ─────────────────────────────────────────────────
$request_body = [
    'contents' => [[
        'parts' => [
            ['text' => $prompt],
            [
                'inline_data' => [
                    'mime_type' => $mime_type,
                    'data'      => $audio_base64,
                ]
            ]
        ]
    ]],
    'generationConfig' => [
        'response_mime_type' => 'application/json',
        'response_schema'    => [
            'type'       => 'OBJECT',
            'properties' => [
                'transcript' => ['type' => 'STRING'],
                'sales'      => [
                    'type'  => 'ARRAY',
                    'items' => [
                        'type'       => 'OBJECT',
                        'properties' => [
                            'tea_type' => ['type' => 'STRING'],
                            'quantity' => ['type' => 'INTEGER'],
                        ],
                        'required' => ['tea_type', 'quantity'],
                    ]
                ],
                'expenses' => [
                    'type'  => 'ARRAY',
                    'items' => [
                        'type'       => 'OBJECT',
                        'properties' => [
                            'ingredient_name' => ['type' => 'STRING'],
                            'quantity'        => ['type' => 'STRING'],
                            'amount'          => ['type' => 'NUMBER'],
                        ],
                        'required' => ['ingredient_name', 'quantity', 'amount'],
                    ]
                ],
            ],
            'required' => ['transcript', 'sales', 'expenses'],
        ],
    ],
];

// ── Call Gemini API ───────────────────────────────────────────────────────────
$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY;

if (!function_exists('curl_init')) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL is not enabled on this server. Please enable it in php.ini.']);
    exit;
}

$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($request_body),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . GEMINI_API_KEY
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 90,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$raw_response = curl_exec($ch);
$http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error   = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Network error reaching Gemini API: ' . $curl_error]);
    exit;
}

if ($http_code !== 200) {
    $err_data = json_decode($raw_response, true);
    $err_msg  = $err_data['error']['message'] ?? ('Gemini API returned HTTP ' . $http_code . '.');
    http_response_code(502);
    echo json_encode(['error' => $err_msg]);
    exit;
}

// ── Extract structured text from Gemini response ──────────────────────────────
$gemini_data = json_decode($raw_response, true);
$result_text = $gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$result_text) {
    $finish_reason = $gemini_data['candidates'][0]['finishReason'] ?? 'UNKNOWN';
    http_response_code(500);
    echo json_encode(['error' => 'Empty response from Gemini. Finish reason: ' . $finish_reason]);
    exit;
}

// ── Validate the parsed JSON before returning ─────────────────────────────────
$parsed = json_decode($result_text, true);
if (!$parsed || !isset($parsed['transcript'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Gemini returned malformed JSON. Raw: ' . substr($result_text, 0, 300)]);
    exit;
}

// ── Return clean structured data to the browser ───────────────────────────────
echo $result_text;
exit;
