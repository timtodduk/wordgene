<?php
header('Content-Type: application/json');
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class BusinessScraper {
    private $client;
    private $cacheDir = 'cache/';
    
    public function __construct() {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; UKBusinessScraper/1.0)'
            ]
        ]);
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    private function getMockData($region, $industry, $page) {
        $mockData = [
            'technology' => [
                ['name' => 'Tech Solutions Ltd', 'email' => 'contact@techsolutions.co.uk'],
                ['name' => 'Digital Innovations', 'email' => 'info@digitalinnovations.co.uk'],
                ['name' => 'Cloud Systems UK', 'email' => 'sales@cloudsystems.uk']
            ],
            'retail' => [
                ['name' => 'Retail Express', 'email' => 'info@retailexpress.co.uk'],
                ['name' => 'Shop Direct', 'email' => 'contact@shopdirect.co.uk'],
                ['name' => 'Retail Plus', 'email' => 'sales@retailplus.co.uk']
            ],
            'manufacturing' => [
                ['name' => 'Manufacturing Pro', 'email' => 'info@manufacturingpro.co.uk'],
                ['name' => 'Industrial Solutions', 'email' => 'contact@industrialsolutions.co.uk'],
                ['name' => 'Factory Systems', 'email' => 'info@factorysystems.co.uk']
            ]
        ];

        $businesses = [];
        if ($industry === 'all') {
            foreach ($mockData as $ind => $items) {
                foreach ($items as $item) {
                    $businesses[] = array_merge($item, [
                        'industry' => $ind,
                        'region' => $region,
                        'source' => 'https://example.com/' . $ind
                    ]);
                }
            }
        } else {
            $industryData = $mockData[$industry] ?? [];
            foreach ($industryData as $item) {
                $businesses[] = array_merge($item, [
                    'industry' => $industry,
                    'region' => $region,
                    'source' => 'https://example.com/' . $industry
                ]);
            }
        }

        // Implement basic pagination
        $itemsPerPage = 3;
        $start = ($page - 1) * $itemsPerPage;
        return array_slice($businesses, $start, $itemsPerPage);
    }

    public function scrape($region = 'uk', $industry = 'all', $page = 1) {
        // Using mock data for now
        return $this->getMockData($region, $industry, $page);
    }
}

try {
    $region = $_GET['region'] ?? 'uk';
    $industry = $_GET['industry'] ?? 'all';
    $page = max(1, (int)($_GET['page'] ?? 1));

    $scraper = new BusinessScraper();
    $businesses = $scraper->scrape($region, $industry, $page);

    $totalItems = count($businesses);
    $hasMorePages = $totalItems === 3; // Since we're showing 3 items per page

    echo json_encode([
        'success' => true,
        'count' => $totalItems,
        'region' => $region,
        'industry' => $industry,
        'page' => $page,
        'hasMorePages' => $hasMorePages,
        'businesses' => $businesses
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}