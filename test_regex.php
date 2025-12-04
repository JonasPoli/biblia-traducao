<?php

$text = 'Então<S>G5119</S> <n>τότε</n><S>G5119</S>, lhes<S>G846</S> <n>αὐτός</n><S>G846</S> direi explicitamente<S>G3670</S> <n>ὁμολογέω</n><S>G3670</S> <S>G5692</S>: <n>ἌΝ</n><S>G302</S> nunca<S>G3763</S> <n>οὐδέποτε</n><S>G3763</S>';

// Current Regex (simulated failure)
// It allows consuming <S> if the rest doesn't match
echo "--- Current Regex ---\n";
preg_match_all('/(.*?)<S>(G\d+)<\/S>\s*<n>([^<]+)<\/n><S>\2<\/S>(?:\s*<S>(G\d+)<\/S>)?/us', $text, $matches, PREG_SET_ORDER);
foreach ($matches as $m) {
    echo "Translation: " . trim($m[1]) . " | Strong: " . $m[2] . "\n";
}

// Proposed Regex
// Forbids <S> inside translation
echo "\n--- Proposed Regex ---\n";
preg_match_all('/((?:(?!<S>).)*?)<S>(G\d+)<\/S>\s*<n>([^<]+)<\/n><S>\2<\/S>(?:\s*<S>(G\d+)<\/S>)?/us', $text, $matches, PREG_SET_ORDER);
foreach ($matches as $m) {
    echo "Translation: " . trim($m[1]) . " | Strong: " . $m[2] . "\n";
}
