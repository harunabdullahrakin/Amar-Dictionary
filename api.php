<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

$word = isset($_GET['word']) ? trim($_GET['word']) : '';
if (!$word) {
    echo json_encode(["error" => "No word provided"]);
    exit;
}

$cacheDir = __DIR__ . '/cache';
if (!file_exists($cacheDir)) mkdir($cacheDir, 0755, true);

$e2bRemote = 'https://raw.githubusercontent.com/Nafisa41/Dictionary--English-to-Bangla-/master/Database/E2Bdatabase.json';
$e2bLocal = $cacheDir . '/E2Bdatabase.json';
$e2bTTL = 24 * 3600;

$perWordCacheFile = $cacheDir . '/' . md5(mb_strtolower($word, 'UTF-8')) . '.json';
$perWordCacheTTL = 3600;

$httpTimeout = 3;
$multiTimeout = 4;

if (file_exists($perWordCacheFile) && (time() - filemtime($perWordCacheFile) < $perWordCacheTTL)) {
    $cached = @file_get_contents($perWordCacheFile);
    if ($cached) {
        header('X-Cache: HIT');
        echo $cached;
        exit;
    }
}

function safe_json($json) {
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function curl_get_single($url, $timeout = 3) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'E2B-API/1.0'
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res ?: '';
}

function curl_multi_get(array $map, $timeout = 4) {
    $mh = curl_multi_init();
    $handles = [];
    $results = [];
    foreach ($map as $key => $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'E2B-API/1.0'
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }
    $running = null;
    $start = time();
    do {
        $status = curl_multi_exec($mh, $running);
        curl_multi_select($mh, 0.5);
        if ((time() - $start) > $timeout + 1) break;
    } while ($running && $status == CURLM_OK);
    foreach ($handles as $key => $ch) {
        $results[$key] = curl_multi_getcontent($ch) ?: '';
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $results;
}

function atomic_write($path, $data) {
    $tmp = $path . '.tmp';
    file_put_contents($tmp, $data);
    rename($tmp, $path);
}

if (!file_exists($e2bLocal) || (time() - filemtime($e2bLocal) > $e2bTTL)) {
    $content = curl_get_single($e2bRemote, 6);
    if ($content) @atomic_write($e2bLocal, $content);
}

$dictionaryJSON = '';
if (file_exists($e2bLocal)) {
    $dictionaryJSON = @file_get_contents($e2bLocal) ?: '';
} else {
    $dictionaryJSON = curl_get_single($e2bRemote, 6) ?: '';
    if ($dictionaryJSON) @atomic_write($e2bLocal, $dictionaryJSON);
}

$urbanURL = "https://api.urbandictionary.com/v0/define?term=" . urlencode($word);
$anomakiURL = "https://www.apis-anomaki.zone.id/tools/translate?word=" . urlencode($word) . "&from=EN&to=BN";
$dictURL = "https://api.dictionaryapi.dev/api/v2/entries/en/" . urlencode($word);
$pinURL = "https://www.apis-anomaki.zone.id/search/pinsearch?query=" . urlencode($word);

$toFetch = [
    'urban' => $urbanURL,
    'anomaki' => $anomakiURL,
    'dict' => $dictURL,
    'pin' => $pinURL
];

$responses = curl_multi_get($toFetch, $multiTimeout);

foreach ($toFetch as $k => $u) {
    if (empty($responses[$k])) {
        $responses[$k] = curl_get_single($u, $httpTimeout);
    }
}

$urban_meaning = "";
$urbanJson = $responses['urban'] ?? '';
if ($urbanJson) {
    $urbanData = safe_json($urbanJson);
    if (!empty($urbanData['list'][0]['definition'])) {
        $urban_meaning = strip_tags($urbanData['list'][0]['definition']);
    }
}

$bangla_translation = [];
$anomaki_examples = [];
$anomakiJson = $responses['anomaki'] ?? '';
if ($anomakiJson) {
    $anomakiData = safe_json($anomakiJson);
    if (!empty($anomakiData['result'])) {
        $res = $anomakiData['result'];
        $bangla_translation = [
            "translated" => $res['translated'] ?? "",
            "pronunciation" => $res['pronunciation'] ?? "",
            "pron_audio" => [
                "uk" => $res['uk_audio'] ?? "",
                "us" => $res['us_audio'] ?? ""
            ]
        ];
        if (!empty($res['definitions']) && is_array($res['definitions'])) {
            foreach ($res['definitions'] as $def) {
                if (!empty($def['example'][0]['source'])) {
                    $anomaki_examples[] = $def['example'][0]['source'];
                }
            }
        }
    }
}

$bangla_translation2 = "";
if ($dictionaryJSON) {
    $words = safe_json($dictionaryJSON);
    $wordLower = mb_strtolower($word, 'UTF-8');
    if (isset($words[$wordLower]) && isset($words[$wordLower]['bn'])) {
        $bangla_translation2 = $words[$wordLower]['bn'];
        $bangla_translation2 = preg_replace('/\((\w+)\.\)/u', '($1)', $bangla_translation2);
    } else {
        if (is_array($words)) {
            foreach ($words as $entry) {
                if (!empty($entry['en']) && mb_strtolower($entry['en'], 'UTF-8') === $wordLower) {
                    $bangla_translation2 = $entry['bn'] ?? "";
                    $bangla_translation2 = preg_replace('/\((\w+)\.\)/u', '($1)', $bangla_translation2);
                    break;
                }
            }
        }
    }
}

$dictionary_data = [
    "phonetic" => "",
    "phonetics" => [],
    "meanings" => [],
    "synonyms" => [],
    "antonyms" => []
];
$dictJson = $responses['dict'] ?? '';
if ($dictJson) {
    $dictData = safe_json($dictJson);
    if (!empty($dictData[0])) {
        $entry = $dictData[0];
        $dictionary_data["phonetic"] = $entry['phonetic'] ?? "";
        if (!empty($entry['phonetics']) && is_array($entry['phonetics'])) {
            foreach ($entry['phonetics'] as $p) {
                if (!empty($p['text']) || !empty($p['audio'])) {
                    $dictionary_data["phonetics"][] = [
                        "text" => $p['text'] ?? "",
                        "audio" => $p['audio'] ?? ""
                    ];
                }
            }
        }
        if (!empty($entry['meanings']) && is_array($entry['meanings'])) {
            foreach ($entry['meanings'] as $m) {
                $pos = $m['partOfSpeech'] ?? "";
                $definitions = [];
                $examples = [];
                if (!empty($m['synonyms'])) $dictionary_data['synonyms'] = array_merge($dictionary_data['synonyms'], (array)$m['synonyms']);
                if (!empty($m['antonyms'])) $dictionary_data['antonyms'] = array_merge($dictionary_data['antonyms'], (array)$m['antonyms']);
                if (!empty($m['definitions']) && is_array($m['definitions'])) {
                    foreach ($m['definitions'] as $d) {
                        if (!empty($d['definition'])) $definitions[] = $d['definition'];
                        if (!empty($d['example'])) $examples[] = $d['example'];
                        if (!empty($d['synonyms'])) $dictionary_data['synonyms'] = array_merge($dictionary_data['synonyms'], (array)$d['synonyms']);
                        if (!empty($d['antonyms'])) $dictionary_data['antonyms'] = array_merge($dictionary_data['antonyms'], (array)$d['antonyms']);
                    }
                }
                $dictionary_data["meanings"][] = [
                    "partOfSpeech" => $pos,
                    "definitions" => $definitions,
                    "examples" => $examples
                ];
            }
        }
    }
}
$dictionary_data['synonyms'] = array_values(array_unique($dictionary_data['synonyms']));
$dictionary_data['antonyms'] = array_values(array_unique($dictionary_data['antonyms']));

$images = [];
$pinJson = $responses['pin'] ?? '';
if ($pinJson) {
    $pinData = safe_json($pinJson);
    if (!empty($pinData['result']) && is_array($pinData['result'])) $images = $pinData['result'];
}

$all_examples = [];
foreach ($dictionary_data["meanings"] as $m) $all_examples = array_merge($all_examples, $m['examples']);
$all_examples = array_unique(array_merge($all_examples, $anomaki_examples));
$all_examples = array_slice($all_examples, 0, 20);

$sources = [
    "anomaki_api" => (bool)$anomakiJson,
    "dictionaryapi" => (bool)$dictJson,
    "urban" => (bool)$urbanJson,
    "pinsearch" => (bool)$pinJson,
    "e2b_json" => (bool)$dictionaryJSON
];

$result = [
    "word" => $word,
    "bangla_translation" => $bangla_translation,
    "bangla_translation2" => $bangla_translation2,
    "english_pronunciation" => [
        "phonetic" => $dictionary_data["phonetic"],
        "phonetics" => $dictionary_data["phonetics"]
    ],
    "parts_of_speech" => $dictionary_data["meanings"],
    "synonyms" => array_values(array_unique($dictionary_data["synonyms"])),
    "antonyms" => array_values(array_unique($dictionary_data["antonyms"])),
    "urban_meaning" => $urban_meaning,
    "examples" => $all_examples,
    "images" => $images,
    "sources" => $sources
];

$jsonOut = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
@file_put_contents($perWordCacheFile, $jsonOut);

header('X-Cache: MISS');
echo $jsonOut;
exit;
