<?php
header('Content-Type: application/json');

function generatePseudoword($type) {
    $consonants = ['b','c','d','f','g','h','j','k','l','m','n','p','r','s','t','v','w'];
    $vowels = ['a','e','i','o','u'];
    
    // Business-specific letter preferences
    $typePreferences = [
        'tech' => [
            'prefix' => ['cy', 'te', 'bi', 'di', 'ai'],
            'suffix' => ['ly', 'io', 'ex', 'ix']
        ],
        'food' => [
            'prefix' => ['ta', 'yu', 'me', 'fo', 'di'],
            'suffix' => ['to', 'la', 'mi', 'ro']
        ],
        'health' => [
            'prefix' => ['vi', 'me', 'he', 'ze', 'nu'],
            'suffix' => ['ax', 'ex', 'va', 'ta']
        ],
        'finance' => [
            'prefix' => ['pa', 'mo', 'ca', 'fi', 'ba'],
            'suffix' => ['fy', 'nx', 'ex', 'ix']
        ],
        'retail' => [
            'prefix' => ['sh', 'bu', 'ma', 'st', 'de'],
            'suffix' => ['ly', 'go', 'la', 'ra']
        ]
    ];
    
    // Use business-specific patterns
    if (array_rand([true, false]) && isset($typePreferences[$type])) {
        $prefix = $typePreferences[$type]['prefix'][array_rand($typePreferences[$type]['prefix'])];
        $remainingLength = 6 - strlen($prefix);
        $word = $prefix;
        
        while(strlen($word) < 6) {
            $word .= (strlen($word) % 2 == 0) ? 
                $consonants[array_rand($consonants)] :
                $vowels[array_rand($vowels)];
        }
    } else {
        $patterns = [
            ['C', 'V', 'C', 'V', 'C', 'V'],
            ['C', 'V', 'C', 'C', 'V', 'C'],
            ['C', 'V', 'V', 'C', 'V', 'C'],
        ];
        
        $pattern = $patterns[array_rand($patterns)];
        $word = '';
        $lastChar = '';
        
        foreach ($pattern as $letterType) {
            if ($letterType === 'C') {
                do {
                    $char = $consonants[array_rand($consonants)];
                } while ($char === $lastChar);
                $word .= $char;
            } else {
                do {
                    $char = $vowels[array_rand($vowels)];
                } while ($char === $lastChar);
                $word .= $char;
            }
            $lastChar = $char;
        }
    }
    
    // Check against common brands/trademarks
    $commonBrands = ['google', 'amazon', 'apple', 'micro', 'intel', 'cisco', 'oracle', 'adobe', 'nokia', 'tesla'];
    if (in_array($word, $commonBrands)) {
        return generatePseudoword($type);
    }
    
    return $word;
}

function checkDomainAvailability($domain) {
    $domain = $domain . ".com";
    $dns = @dns_get_record($domain, DNS_A);
    return empty($dns);
}

$type = isset($_GET['type']) ? $_GET['type'] : 'tech';
$words = [];
$attempts = 0;
$maxAttempts = 20;

while (count($words) < 5 && $attempts < $maxAttempts) {
    $word = generatePseudoword($type);
    $available = checkDomainAvailability($word);
    
    if ($available) {
        $words[] = [
            'word' => $word,
            'available' => true
        ];
    }
    $attempts++;
}

echo json_encode($words);