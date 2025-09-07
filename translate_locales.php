<?php
/**
 * Roundcube localization auto-translator
 * 
 * - Reads en_US.inc as source of truth
 * - For each target locale file (*.inc), fills missing keys with machine-translated text
 * - Preserves existing translations unless --force is provided
 * - Supports LibreTranslate (self-hosted) or DeepL
 *
 * Usage:
 *   php translate_locales.php --src=localization/en_US.inc --dir=localization \
 *     --provider=libre --lt-url="http://localhost:5000" --lt-api-key="" --force=0
 *
 *   php translate_locales.php --src=localization/en_US.inc --dir=localization \
 *     --provider=deepl --deepl-key="YOUR_KEY" --deepl-plan="free|pro" --force=1
 *
 * Notes:
 * - Placeholders like %s, %d, %1$s are preserved.
 * - HTML entities and simple markup are preserved.
 * - Escapes single quotes for PHP string literals.
 * - Produces a report JSON at localization/mt_report.json
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

function arg($name, $default=null) {
    foreach ($GLOBALS['argv'] as $a) {
        if (strpos($a, "--$name=") === 0) {
            return substr($a, strlen("--$name="));
        }
    }
    return $default;
}

$src_file  = arg('src', 'localization/en_US.inc');
$dir       = rtrim(arg('dir', 'localization'), '/');
$provider  = strtolower(arg('provider', 'libre')); // libre|deepl
$force     = (int) arg('force', '0');

$lt_url    = rtrim(arg('lt-url', 'http://localhost:5000'), '/');
$lt_key    = arg('lt-api-key', '');

$deepl_key = arg('deepl-key', '');
$deepl_plan= strtolower(arg('deepl-plan', 'free')); // free|pro

if (!file_exists($src_file)) {
    fwrite(STDERR, "Source file not found: $src_file\n");
    exit(1);
}

$src_labels = include_labels($src_file);
if (!is_array($src_labels) || empty($src_labels)) {
    fwrite(STDERR, "Failed to load labels from $src_file\n");
    exit(1);
}

// Discover target locale files (*.inc) in $dir
$targets = [];
$dh = opendir($dir);
while (($entry = readdir($dh)) !== false) {
    if (substr($entry, -4) === '.inc' && $entry !== 'en_US.inc') {
        $targets[] = $dir . '/' . $entry;
    }
}
closedir($dh);
sort($targets);

$report = [
    'provider' => $provider,
    'force' => $force,
    'timestamp' => date('c'),
    'locales' => []
];

foreach ($targets as $file) {
    $locale = basename($file, '.inc'); // e.g. de_DE
    $labels = file_exists($file) ? include_labels($file) : [];
    if (!is_array($labels)) { $labels = []; }

    $added = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($src_labels as $k => $en_val) {
        $needs = $force || !array_key_exists($k, $labels) || trim((string)$labels[$k]) === '';
        if ($needs) {
            $text = (string)$en_val;
            $placeholders = extract_placeholders($text);

            $translated = translate_text($provider, $text, $locale, [
                'lt_url' => $lt_url, 'lt_key' => $lt_key,
                'deepl_key' => $deepl_key, 'deepl_plan' => $deepl_plan,
            ]);

            // Put placeholders back if translation broke them
            $translated = restore_placeholders($translated, $placeholders);

            $labels[$k] = $translated;
            if (array_key_exists($k, $labels)) {
                $updated++;
            } else {
                $added++;
            }
        } else {
            $skipped++;
        }
    }

    // Write back file preserving Roundcube format
    $php = "<?php\n\$labels = array();\n";
    foreach ($labels as $k => $v) {
        $php .= "\$labels['" . php_escape($k) . "'] = '" . php_escape($v) . "';\n";
    }
    $php .= "?>\n";

    file_put_contents($file, $php);

    $report['locales'][] = [
        'file' => $file,
        'added' => $added,
        'updated' => $updated,
        'skipped' => $skipped,
        'total' => count($labels),
    ];

    echo "[OK] $file => total=" . count($labels) . " added=$added updated=$updated skipped=$skipped\n";
}

file_put_contents($dir . '/mt_report.json', json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

// --------- helpers ----------

function include_labels($file) {
    $labels = [];
    try {
        // Sandbox include
        $code = file_get_contents($file);
        // Expect format: $labels['key'] = 'value';
        // We'll eval in a limited scope
        $labels = [];
        include $file;
        if (!isset($labels)) $labels = [];
        return $labels;
    } catch (Throwable $e) {
        return [];
    }
}

function php_escape($s) {
    // Escape backslashes and single quotes for PHP single-quoted strings
    $s = str_replace('\\', '\\\\', $s);
    $s = str_replace("'", "\\'", $s);
    // Keep newlines readable
    $s = str_replace(["\r\n", "\n", "\r"], ['\n', '\n', '\n'], $s);
    return $s;
}

function extract_placeholders($s) {
    // Match sprintf-style placeholders and %n$s variants
    preg_match_all('/%(\d+\$)?[sdfoxu]/', $s, $m1);
    // Also match bare %s, %d etc.
    return $m1[0];
}

function restore_placeholders($translated, $placeholders) {
    // crude: ensure the same count of placeholders exists; if not, append missing
    $count = 0;
    foreach ($placeholders as $ph) {
        if (strpos($translated, $ph) === false) {
            // try to reinsert sequentially
            $translated .= ' ' . $ph;
        }
        $count++;
    }
    return $translated;
}

function translate_text($provider, $text, $locale, $opts) {
    // Map Roundcube style locale to BCP-47-ish codes
    $map = [
        'en_GB'=>'en', 'en_US'=>'en', 'en_AU'=>'en', 'en_CA'=>'en',
        'de_DE'=>'de', 'fr_FR'=>'fr', 'es_ES'=>'es', 'it_IT'=>'it',
        'pt_BR'=>'pt', 'pt_PT'=>'pt', 'ru_RU'=>'ru', 'pl_PL'=>'pl',
        'nl_NL'=>'nl', 'cs_CZ'=>'cs', 'sv_SE'=>'sv', 'fi_FI'=>'fi',
        'da_DK'=>'da', 'nb_NO'=>'no', 'nn_NO'=>'no', 'tr_TR'=>'tr',
        'ja_JP'=>'ja', 'ko_KR'=>'ko', 'zh_CN'=>'zh', 'zh_TW'=>'zh-TW',
        'ar_SA'=>'ar', 'he_IL'=>'he', 'el_GR'=>'el', 'hu_HU'=>'hu',
        'ro_RO'=>'ro', 'uk_UA'=>'uk', 'bg_BG'=>'bg', 'hr_HR'=>'hr',
        'sk_SK'=>'sk', 'sl_SI'=>'sl', 'lt_LT'=>'lt', 'lv_LV'=>'lv',
        'et_EE'=>'et', 'sr_RS'=>'sr', 'fa_IR'=>'fa', 'hi_IN'=>'hi',
    ];
    $target = isset($map[$locale]) ? $map[$locale] : substr($locale,0,2);
    if ($target === 'en') {
        return $text; // no-op to English
    }

    if ($provider === 'deepl') {
        return deepl_translate($text, $target, $opts['deepl_key'], $opts['deepl_plan']);
    } else {
        return libre_translate($text, $target, $opts['lt_url'], $opts['lt_key']);
    }
}

function http_post_json($url, $payload, $headers=[]) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(['Content-Type: application/json'], $headers));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("HTTP error: $err");
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 400) {
        throw new Exception("HTTP $code: $resp");
    }
    return $resp;
}

function libre_translate($text, $target, $base_url, $api_key='') {
    $payload = [
        'q' => $text,
        'source' => 'en',
        'target' => $target,
        'format' => 'text',
        'api_key' => $api_key
    ];
    $resp = http_post_json($base_url . '/translate', $payload);
    $data = json_decode($resp, true);
    if (isset($data['translatedText'])) return $data['translatedText'];
    if (is_array($data) && isset($data[0]['translatedText'])) return $data[0]['translatedText'];
    return $text;
}

function deepl_translate($text, $target, $key, $plan='free') {
    // Choose endpoint
    $host = ($plan === 'pro')
        ? 'https://api.deepl.com/v2/translate'
        : 'https://api-free.deepl.com/v2/translate';

    // Normalize DeepL target codes that need special forms
    $target = strtoupper($target);
    // Map common ambiguities → DeepL's expected codes
    if ($target === 'PT')  $target = 'PT-BR';      // pick one; or decide per your locale
    if ($target === 'NO')  $target = 'NB';        // DeepL supports NB (Bokmål)
    if ($target === 'EN')  return $text;          // nothing to do
    // DeepL uses ZH (Simplified). There is no ZH-TW target.
    if ($target === 'ZH-CN' || $target === 'ZH-TW') $target = 'ZH';

    // Build base fields (DO NOT put text here as an array)
    $fields = [
        'target_lang'         => $target,
        'source_lang'         => 'EN',
        'preserve_formatting' => 1,
    ];
    $post = http_build_query($fields, '', '&', PHP_QUERY_RFC3986);

    // Append text as repeated fields exactly as DeepL expects
    foreach ((array)$text as $t) {
        $post .= '&text=' . urlencode($t);
    }

    $ch = curl_init($host);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: DeepL-Auth-Key ' . $key,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("HTTP error: $err");
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 400) {
        throw new Exception("HTTP $code: $resp");
    }

    $data = json_decode($resp, true);
    if (isset($data['translations'][0]['text'])) {
        return $data['translations'][0]['text'];
    }
    return $text;
}
?>
