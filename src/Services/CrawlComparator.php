<?php
/**
 * Crawl Comparator
 * 
 * Compares two crawl results and generates a diff report
 */

class CrawlComparator
{
    /**
     * Compare two crawl results
     * 
     * @param array $result1 First crawl result
     * @param array $result2 Second crawl result
     * @return array Comparison report
     */
    public static function compare(array $result1, array $result2): array
    {
        $items1 = self::indexItems($result1['items'] ?? []);
        $items2 = self::indexItems($result2['items'] ?? []);
        
        $newUrls = [];
        $removedUrls = [];
        $changedStatus = [];
        $unchanged = [];
        
        // Find new URLs
        foreach ($items2 as $url => $item2) {
            if (!isset($items1[$url])) {
                $newUrls[] = $item2;
            }
        }
        
        // Find removed URLs
        foreach ($items1 as $url => $item1) {
            if (!isset($items2[$url])) {
                $removedUrls[] = $item1;
            }
        }
        
        // Find changed status codes
        foreach ($items1 as $url => $item1) {
            if (isset($items2[$url])) {
                $item2 = $items2[$url];
                if (($item1['status'] ?? 0) !== ($item2['status'] ?? 0)) {
                    $changedStatus[] = [
                        'url' => $url,
                        'old_status' => $item1['status'] ?? 0,
                        'new_status' => $item2['status'] ?? 0,
                        'old_has_error' => $item1['error_flag'] ?? $item1['has_error'] ?? false,
                        'new_has_error' => $item2['error_flag'] ?? $item2['has_error'] ?? false,
                    ];
                } else {
                    $unchanged[] = $item1;
                }
            }
        }
        
        return [
            'total1' => $result1['total_count'] ?? 0,
            'total2' => $result2['total_count'] ?? 0,
            'success1' => $result1['success_count'] ?? 0,
            'success2' => $result2['success_count'] ?? 0,
            'error1' => $result1['error_count'] ?? 0,
            'error2' => $result2['error_count'] ?? 0,
            'new_urls' => $newUrls,
            'removed_urls' => $removedUrls,
            'changed_status' => $changedStatus,
            'unchanged' => $unchanged,
            'new_urls_count' => count($newUrls),
            'removed_urls_count' => count($removedUrls),
            'changed_status_count' => count($changedStatus),
            'unchanged_count' => count($unchanged),
        ];
    }
    
    /**
     * Index items by URL for faster lookup
     * 
     * @param array $items
     * @return array
     */
    private static function indexItems(array $items): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $url = $item['url'] ?? '';
            if ($url) {
                $indexed[$url] = $item;
            }
        }
        return $indexed;
    }
}

