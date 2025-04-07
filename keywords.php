<?php
header('Content-Type: application/json');

function extractKeywords($content, $count) {
    // Remove special characters and convert to lowercase
    $content = strtolower(preg_replace('/[^\w\s]/', '', $content));
    
    // Split into words
    $words = str_word_count($content, 1);
    
    // Common words to exclude
    $stopWords = ['the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i', 'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at'];
    
    // Filter out stop words and short words
    $words = array_filter($words, function($word) use ($stopWords) {
        return strlen($word) > 2 && !in_array($word, $stopWords);
    });
    
    // Count word frequencies
    $wordFreq = array_count_values($words);
    arsort($wordFreq);
    
    // Get top words based on requested count
    $topWords = array_slice(array_keys($wordFreq), 0, $count);
    
    // Generate primary hashtags
    $primaryHashtags = array_map(function($word) {
        return '#' . $word;
    }, $topWords);
    
    // Generate combined hashtags
    $combinedCount = min(intval($count/2), count($topWords));
    $combinedHashtags = [];
    for ($i = 0; $i < $combinedCount; $i++) {
        if (isset($topWords[$i]) && isset($topWords[$i + 1])) {
            $combinedHashtags[] = '#' . $topWords[$i] . $topWords[$i + 1];
        }
    }
    
    // Generate trending style hashtags
    $trendingCount = min(intval($count/2), count($topWords));
    $trendingHashtags = array_map(function($word) {
        $suffixes = ['Official', 'Today', '2024', 'Trending', 'Now'];
        return '#' . $word . $suffixes[array_rand($suffixes)];
    }, array_slice($topWords, 0, $trendingCount));
    
    return [
        'Popular Hashtags' => $primaryHashtags,
        'Combined Hashtags' => $combinedHashtags,
        'Trending Hashtags' => $trendingHashtags
    ];
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$content = $data['content'] ?? '';
$count = intval($data['count'] ?? 10);

if (empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'No content provided']);
    exit;
}

$keywords = extractKeywords($content, $count);
echo json_encode($keywords);