<?php
header('Content-Type: application/json');

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['type'])) {
    echo json_encode(['error' => 'Invalid request type']);
    exit;
}

switch ($data['type']) {
    case 'meta':
        analyzeMetaTags($data['url']);
        break;
    case 'keywords':
        analyzeKeywords($data['content']);
        break;
    case 'readability':
        analyzeReadability($data['content']);
        break;
    default:
        echo json_encode(['error' => 'Invalid analysis type']);
        exit;
}

function analyzeMetaTags($url) {
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['error' => 'Invalid URL']);
        exit;
    }

    // Get the webpage content
    $html = @file_get_contents($url);
    if ($html === false) {
        echo json_encode(['error' => 'Could not fetch the webpage']);
        exit;
    }

    // Create a DOM document
    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    // Get title tag
    $title = $dom->getElementsByTagName('title')->item(0);
    $titleText = $title ? $title->nodeValue : '';
    $titleScore = calculateTitleScore($titleText);
    $titleRecommendations = getTitleRecommendations($titleText);

    // Get meta description
    $metaDescription = '';
    $metaTags = $dom->getElementsByTagName('meta');
    foreach ($metaTags as $tag) {
        if ($tag->getAttribute('name') === 'description') {
            $metaDescription = $tag->getAttribute('content');
            break;
        }
    }
    $descriptionScore = calculateDescriptionScore($metaDescription);
    $descriptionRecommendations = getDescriptionRecommendations($metaDescription);

    // Analyze heading structure
    $headings = [];
    for ($i = 1; $i <= 6; $i++) {
        $hTags = $dom->getElementsByTagName('h' . $i);
        $headings['h' . $i] = $hTags->length;
    }
    $headingsScore = calculateHeadingsScore($headings);
    $headingsRecommendations = getHeadingsRecommendations($headings);

    echo json_encode([
        'title' => $titleText,
        'titleScore' => $titleScore,
        'titleRecommendations' => $titleRecommendations,
        'description' => $metaDescription,
        'descriptionScore' => $descriptionScore,
        'descriptionRecommendations' => $descriptionRecommendations,
        'headingsScore' => $headingsScore,
        'headingsRecommendations' => $headingsRecommendations
    ]);
}

function analyzeKeywords($content) {
    // Remove HTML tags and special characters
    $content = strip_tags($content);
    $content = preg_replace('/[^a-zA-Z0-9\s]/', '', $content);
    
    // Convert to lowercase and split into words
    $words = explode(' ', strtolower($content));
    
    // Remove common words
    $commonWords = ['the', 'and', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
    $words = array_diff($words, $commonWords);
    
    // Count word frequency
    $wordCounts = array_count_values($words);
    arsort($wordCounts);
    
    // Calculate density
    $totalWords = count($words);
    $topKeywords = [];
    $i = 0;
    foreach ($wordCounts as $word => $count) {
        if ($i >= 10) break; // Get top 10 keywords
        $density = ($count / $totalWords) * 100;
        $topKeywords[] = [
            'word' => $word,
            'count' => $count,
            'density' => round($density, 2)
        ];
        $i++;
    }
    
    // Generate recommendations
    $recommendations = generateKeywordRecommendations($topKeywords);
    
    echo json_encode([
        'topKeywords' => $topKeywords,
        'recommendations' => $recommendations
    ]);
}

function analyzeReadability($content) {
    // Remove HTML tags
    $content = strip_tags($content);
    
    // Count sentences
    $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
    $sentenceCount = count($sentences);
    
    // Count words
    $words = str_word_count($content);
    
    // Calculate average sentence length
    $avgSentenceLength = $words / $sentenceCount;
    
    // Calculate average word length
    $totalChars = strlen(preg_replace('/[^a-zA-Z]/', '', $content));
    $avgWordLength = $totalChars / $words;
    
    // Calculate readability score (simplified Flesch-Kincaid)
    $score = 206.835 - (1.015 * $avgSentenceLength) - (84.6 * ($totalChars / $words));
    $score = max(0, min(10, $score / 10)); // Normalize to 0-10
    
    // Determine readability level
    $level = getReadabilityLevel($score);
    
    // Generate recommendations
    $recommendations = generateReadabilityRecommendations($score, $avgSentenceLength, $avgWordLength);
    
    echo json_encode([
        'score' => round($score, 1),
        'level' => $level,
        'avgSentenceLength' => round($avgSentenceLength, 1),
        'avgWordLength' => round($avgWordLength, 1),
        'totalWords' => $words,
        'recommendations' => $recommendations
    ]);
}

function calculateTitleScore($title) {
    $length = strlen($title);
    $score = 0;
    
    if ($length >= 30 && $length <= 60) $score += 4;
    elseif ($length > 0) $score += 2;
    
    if (strpos($title, '|') !== false) $score += 2;
    if (strpos($title, '-') !== false) $score += 2;
    
    return min(10, $score);
}

function calculateDescriptionScore($description) {
    $length = strlen($description);
    $score = 0;
    
    if ($length >= 120 && $length <= 160) $score += 6;
    elseif ($length > 0) $score += 3;
    
    if (strpos($description, '.') !== false) $score += 2;
    if (strpos($description, ',') !== false) $score += 2;
    
    return min(10, $score);
}

function calculateHeadingsScore($headings) {
    $score = 0;
    
    if ($headings['h1'] == 1) $score += 4;
    if ($headings['h2'] > 0) $score += 3;
    if ($headings['h3'] > 0) $score += 2;
    if ($headings['h4'] > 0) $score += 1;
    
    return min(10, $score);
}

function getTitleRecommendations($title) {
    $recommendations = [];
    $length = strlen($title);
    
    if ($length == 0) {
        $recommendations[] = "Add a title tag to your page";
    } else {
        if ($length < 30) {
            $recommendations[] = "Consider making your title longer (aim for 30-60 characters)";
        } elseif ($length > 60) {
            $recommendations[] = "Consider making your title shorter (aim for 30-60 characters)";
        }
        
        if (strpos($title, '|') === false && strpos($title, '-') === false) {
            $recommendations[] = "Consider adding a separator (| or -) to improve readability";
        }
    }
    
    return implode('. ', $recommendations);
}

function getDescriptionRecommendations($description) {
    $recommendations = [];
    $length = strlen($description);
    
    if ($length == 0) {
        $recommendations[] = "Add a meta description to your page";
    } else {
        if ($length < 120) {
            $recommendations[] = "Consider making your description longer (aim for 120-160 characters)";
        } elseif ($length > 160) {
            $recommendations[] = "Consider making your description shorter (aim for 120-160 characters)";
        }
        
        if (strpos($description, '.') === false) {
            $recommendations[] = "Consider adding a period to improve readability";
        }
    }
    
    return implode('. ', $recommendations);
}

function getHeadingsRecommendations($headings) {
    $recommendations = [];
    
    if ($headings['h1'] == 0) {
        $recommendations[] = "Add an H1 heading to your page";
    } elseif ($headings['h1'] > 1) {
        $recommendations[] = "Consider using only one H1 heading per page";
    }
    
    if ($headings['h2'] == 0) {
        $recommendations[] = "Consider adding H2 headings to structure your content";
    }
    
    if ($headings['h3'] == 0 && $headings['h2'] > 0) {
        $recommendations[] = "Consider adding H3 headings to further structure your content";
    }
    
    return implode('. ', $recommendations);
}

function generateKeywordRecommendations($keywords) {
    $recommendations = [];
    
    foreach ($keywords as $keyword) {
        if ($keyword['density'] > 3) {
            $recommendations[] = "Consider reducing the use of '{$keyword['word']}' (current density: {$keyword['density']}%)";
        } elseif ($keyword['density'] < 1) {
            $recommendations[] = "Consider increasing the use of '{$keyword['word']}' (current density: {$keyword['density']}%)";
        }
    }
    
    if (empty($recommendations)) {
        $recommendations[] = "Your keyword density looks good!";
    }
    
    return implode('. ', $recommendations);
}

function getReadabilityLevel($score) {
    if ($score >= 8) return "Very Easy to Read";
    if ($score >= 6) return "Easy to Read";
    if ($score >= 4) return "Moderately Easy to Read";
    if ($score >= 2) return "Moderately Difficult to Read";
    return "Difficult to Read";
}

function generateReadabilityRecommendations($score, $avgSentenceLength, $avgWordLength) {
    $recommendations = [];
    
    if ($avgSentenceLength > 20) {
        $recommendations[] = "Consider using shorter sentences (current average: {$avgSentenceLength} words)";
    }
    
    if ($avgWordLength > 5) {
        $recommendations[] = "Consider using simpler words (current average: {$avgWordLength} characters per word)";
    }
    
    if ($score < 6) {
        $recommendations[] = "Consider simplifying your content to improve readability";
    }
    
    if (empty($recommendations)) {
        $recommendations[] = "Your content is well-written and easy to read!";
    }
    
    return implode('. ', $recommendations);
}
?> 