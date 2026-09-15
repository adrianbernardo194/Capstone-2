<?php
include 'db.php';
header('Content-Type: application/json');

$complaint_id = (int)($_POST['complaint_id'] ?? 0);
if (!$complaint_id) { echo json_encode(['error' => 'No complaint ID']); exit; }

$comp = $conn->query("SELECT * FROM complaints WHERE id=$complaint_id")->fetch_assoc();
if (!$comp) { echo json_encode(['error' => 'Complaint not found']); exit; }

if (!empty($comp['ai_processed']) && $comp['ai_processed'] == 1) {
    echo json_encode(['parsed_result' => true, 'status' => 'already analyzed']);
    exit;
}

define('GEMINI_KEY', 'AQ.Ab8RN6Jp4UhgK9dCMHWyJQPqlopWX9GX_DTZp0H73nWMx6YlCA');
define('GEMINI_URL',
    'https://generativelanguage.googleapis.com/v1/models/' .
    'gemini-2.5-flash:generateContent?key=' . GEMINI_KEY
);

// Clean text before inserting into prompt
$subject   = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $comp['subject']);
$narrative = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $comp['narrative']);

$prompt = "
You are an assistant for a Philippine barangay complaint system (Katarungang Pambarangay / Lupon Tagapamayapa).
Analyze this complaint and return ONLY a valid JSON object with no markdown.

Subject: {$subject}
Narrative: {$narrative}

Return this exact JSON structure:
{
  \"category\": \"Pagnanakaw (Theft - Art. 309)\",
  \"priority\": \"Medium\",
  \"priority_reason\": \"one sentence\",
  \"suggested_resolution\": \"two sentences\"
}

Choose the category from the OFFICIAL LIST OF LUPON-COGNIZABLE CASES below. Match the complaint
to the MOST SPECIFIC and ACCURATE item on this list, including its article/law reference exactly
as written:

1. Pagnanakaw (kung ang halaga ng ninakaw na ari-arian ay hindi hihigit sa P50.00) (Art. 309)
2. Pagnanakaw (kung ang halaga ay hindi hihigit sa P500.00) (Art. 310)
3. Pagsakop ng mga tigil na ari-arian o sapilitang pagkuha ng karapatan sa ari-arian (Art. 312)
4. Paglilipat ng mga hangganan o muhon (Art. 313)
5. Pandaraya o panggagantso (kung ang halaga ay hindi hihigit sa P200.00) (Art. 315)
6. Iba pang anyo ng pandaraya (Art. 316)
7. Pandaraya sa isang menor de edad (Art. 317)
8. Iba pang panloloko (Art. 318)
9. Pagtanggal, pagbebenta o pagperenda ng nakasanlang pag-aari (Art. 319)
10. Mga kakaibang kaso ng panlolokong may masamang hangarin (kung ang halaga ng nasirang ari-arian ay hindi hihigit sa P1,000.00) (Art. 328)
11. Pag-iwan ng mga taong pinagkatiwalaang mag-aalaga sa isang menor de edad; kawalan ng pagmamalasakit ng mga magulang (Art. 277)
12. Pagpasok sa isang tirahan nang walang pahintulot (hindi gumamit ng dahas at pananakot) (Art. 280)
13. Mga ibang anyo nang pagpasok na walang pahintulot (Art. 281)
14. Magaan na mga pagbabanta (Art. 283)
15. Iba pang magaan na pagbabanta (Art. 285)
16. Grabeng pamumuwersa (Art. 286)
17. Hindi grabeng pamumuwersa (Art. 287)
18. Iba pang katulad na pamumuwersa (sapilitang pagbili ng mga paninda at pagbabayad ng suweldo sa pamamagitan ng pagsingil ng utang na loob) (Art. 288)
19. Pagbubuo, pananatili at pagbabawal ng pagsasanib ng puhunan at lakas-paggawa sa pamamagitan ng dahas at pananakot (Art. 289)
20. Pag-alam sa lihim nang sapilitan at sa pamamagitan ng kasunduan (Art. 290)
21. Pagsisiwalat ng lihim nang mga pang-aabuso ng kapangyarihan (Art. 291)
22. Paglalathala at pamamahayag nang labag sa batas (Art. 154)
23. Mga pananakot at paninirang-puri (Art. 155)
24. Paggamit ng huwad na katibayan (Art. 175)
25. Paggamit ng hindi totoong pangalan at pagtatago ng totoong pangalan (Art. 178)
26. Pinsala sa katawan dala ng marahas na pag-aaway (Art. 252)
27. Pagtulong sa naganap na pagpapatiwakal (Art. 253)
28. Pananagutan ng mga sangkot sa isang labanan kapag nagkaroon lang ng mga pinsala sa katawan o wala mang nangyaring pinsala (Art. 260)
29. Mga hindi gaanong malalang pinsala sa katawan (Art. 265)
30. Mga bahagyang pinsala sa katawan at pagmamalupit (Art. 266)
31. Pagdakip nang labag sa batas (Art. 269)
32. Paghimok sa isang menor de edad na lumayas sa kaniyang tahanan (Art. 271)
33. Pag-iwan sa isang taong nasa panganib at pag-iwan sa isang naging biktima (Art. 275)
34. Pagpapabaya sa isang menor de edad (isang bata na mababa ang edad sa pitong (7) taong gulang) (Art. 276)
35. Iba pang kalokohan (kung ang halaga ng nasirang ari-arian ay hindi hihigit sa P1,000.00) (Art. 329)
36. Simpleng panunulsol (Art. 338)
37. Paggawa ng kahalayan na mayroong pagsang-ayon na naagrabyadong partido (Art. 339)
38. Pagbanta na isisiwalat at alok na pagpigil sa pagsisiwalat na may kabayaran (Art. 356)
39. Pagpigil sa paglalathala ng mga gawaing tinutukoy sa panahon ng opisyal na proseso (Art. 357)
40. Pagdadawit sa mga inosenteng tao (Art. 363)
41. Mga pakana laban sa dangal (Art. 364)
42. Paglalabas ng tseke nang walang sapat na pondo (BP 22)
43. Pagbili ng nakaw na ari-arian kung ang halaga ng ari-ariang sangkot ay hindi hihigit sa P50.00 (PD 1612)

IMPORTANT RULES:
- If the complaint clearly matches one of the 43 items above, use that EXACT category text
  (including the article/law reference) as the \"category\" value.
- If the complaint does NOT match any item on the list, create your OWN short, descriptive
  category in Filipino or English that best describes the complaint (e.g. 'Ingay sa Gabi /
  Noise Disturbance', 'Hindi Pagbabayad ng Utang / Unpaid Debt'). Do not force-fit it into
  the list if it does not belong there.
- priority must be one of: High, Medium, Low.
";

$payload = json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 2048]
]);

$max_retries = 2;
$attempt = 0;
$response = '';
$http_code = 0;

while ($attempt < $max_retries) {
    $ch = curl_init(GEMINI_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) break;
    if ($http_code === 429) break; // don't retry on quota error
    $attempt++;
    sleep(3);
}

$data     = json_decode($response, true);
$raw_text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
$raw_text = mb_convert_encoding($raw_text, 'UTF-8', 'UTF-8');
$raw_text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $raw_text);

$clean = preg_replace('/^```[\w]*\s*/m', '', $raw_text);
$clean = preg_replace('/\s*```$/m', '', $clean);
$clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);
$clean = trim($clean);

$result = json_decode($clean, true);

if ($result) {
    $category             = $conn->real_escape_string($result['category']);
    $priority             = $conn->real_escape_string($result['priority']);
    $priority_reason      = $conn->real_escape_string($result['priority_reason']);
    $suggested_resolution = $conn->real_escape_string($result['suggested_resolution']);
    $ai_suggestion        = $conn->real_escape_string($suggested_resolution . ' | Priority reason: ' . $priority_reason);

    $conn->query("UPDATE complaints SET
        ai_category   = '$category',
        ai_priority   = '$priority',
        ai_suggestion = '$ai_suggestion',
        ai_processed  = 1
        WHERE id = $complaint_id
    ");
}

echo json_encode([
    'http_code'     => $http_code,
    'parsed_result' => $result,
    'json_error'    => json_last_error_msg(),
    'raw_text'      => $raw_text,
    'clean_text'    => $clean,
    'raw_length'    => strlen($raw_text),
]);
?>
