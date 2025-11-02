<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

$word = isset($_GET['word']) ? trim($_GET['word']) : '';
if (!$word) {
    echo json_encode(["error" => "No word provided"]);
    exit;
}

function safe_json($json) {
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

$urban_meaning = "";
$urbanURL = "https://api.urbandictionary.com/v0/define?term=" . urlencode($word);
$urbanJson = @file_get_contents($urbanURL);
if ($urbanJson) {
    $urbanData = safe_json($urbanJson);
    if (!empty($urbanData['list'][0]['definition'])) {
        $urban_meaning = strip_tags($urbanData['list'][0]['definition']);
    }
}

$bangla_translation = [];
$anomaki_examples = [];

$anomakiURL = "https://www.apis-anomaki.zone.id/tools/translate?word=" . urlencode($word) . "&from=EN&to=BN";
$anomakiJson = @file_get_contents($anomakiURL);
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

        if (!empty($res['definitions'])) {
            foreach ($res['definitions'] as $def) {
                if (!empty($def['example'][0]['source'])) {
                    $anomaki_examples[] = $def['example'][0]['source'];
                }
            }
        }
    }
}

$dictionaryDatabaseLink = 'https://raw.githubusercontent.com/Nafisa41/Dictionary--English-to-Bangla-/master/Database/E2Bdatabase.json';
$dictionaryJSON = @file_get_contents($dictionaryDatabaseLink);
$bangla_translation2 = "";

if ($dictionaryJSON) {
    $words = safe_json($dictionaryJSON);

    $radix = 128;
    $mod = 100000000003;
    $primeForPrimaryHash = 103643;
    $primaryHashA = 1; 

    $primaryHashB = 0;

    function calculateKeyValue($word, $radix, $mod) {
        $value = 0;
        $len = mb_strlen($word, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $value = ($value * $radix + mb_ord(mb_substr($word, $i, 1, 'UTF-8'))) % $mod;
        }
        return $value;
    }

    function calculateHashValue($key, $a, $b, $primeForPrimaryHash) {
        return ($a * $key + $b) % $primeForPrimaryHash;
    }

    $wordLower = mb_strtolower($word, 'UTF-8');
    $key = calculateKeyValue($wordLower, $radix, $mod);
    $phash = calculateHashValue($key, $primaryHashA, $primaryHashB, $primeForPrimaryHash);

    if (isset($words[$wordLower])) {
        $bangla_translation2 = $words[$wordLower]['bn'];
    } else {

        foreach ($words as $entry) {
            if (mb_strtolower($entry['en'], 'UTF-8') === $wordLower) {
                $bangla_translation2 = $entry['bn'];
                break;
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

$dictURL = "https://api.dictionaryapi.dev/api/v2/entries/en/" . urlencode($word);
$dictJson = @file_get_contents($dictURL);
if ($dictJson) {
    $dictData = safe_json($dictJson);
    if (!empty($dictData[0])) {
        $entry = $dictData[0];
        $dictionary_data["phonetic"] = $entry['phonetic'] ?? "";

        if (!empty($entry['phonetics'])) {
            foreach ($entry['phonetics'] as $p) {
                if (!empty($p['text']) || !empty($p['audio'])) {
                    $dictionary_data["phonetics"][] = [
                        "text" => $p['text'] ?? "",
                        "audio" => $p['audio'] ?? ""
                    ];
                }
            }
        }

        if (!empty($entry['meanings'])) {
            foreach ($entry['meanings'] as $m) {
                $pos = $m['partOfSpeech'] ?? "";
                $definitions = [];
                $examples = [];

                if (!empty($m['synonyms'])) {
                    $dictionary_data['synonyms'] = array_merge($dictionary_data['synonyms'], $m['synonyms']);
                }
                if (!empty($m['antonyms'])) {
                    $dictionary_data['antonyms'] = array_merge($dictionary_data['antonyms'], $m['antonyms']);
                }

                if (!empty($m['definitions'])) {
                    foreach ($m['definitions'] as $d) {
                        if (!empty($d['definition'])) $definitions[] = $d['definition'];
                        if (!empty($d['example'])) $examples[] = $d['example'];

                        if (!empty($d['synonyms'])) {
                            $dictionary_data['synonyms'] = array_merge($dictionary_data['synonyms'], $d['synonyms']);
                        }
                        if (!empty($d['antonyms'])) {
                            $dictionary_data['antonyms'] = array_merge($dictionary_data['antonyms'], $d['antonyms']);
                        }
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
$pinURL = "https://www.apis-anomaki.zone.id/search/pinsearch?query=" . urlencode($word);
$pinJson = @file_get_contents($pinURL);
if ($pinJson) {
    $pinData = safe_json($pinJson);
    if (!empty($pinData['result'])) {
        $images = $pinData['result'];
    }
}

$all_examples = [];
foreach ($dictionary_data["meanings"] as $m) {
    $all_examples = array_merge($all_examples, $m['examples']);
}
$all_examples = array_unique(array_merge($all_examples, $anomaki_examples));
$all_examples = array_slice($all_examples, 0, 20);

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
    "sources" => [
        "anomaki_api" => (bool)$anomakiJson,
        "dictionaryapi" => (bool)$dictJson,
        "urban" => (bool)$urbanJson,
        "pinsearch" => (bool)$pinJson,
        "e2b_json" => (bool)$dictionaryJSON
    ]
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>