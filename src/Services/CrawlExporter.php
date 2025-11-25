<?php
/**
 * Crawl Exporter
 * 
 * Exports crawl results to various formats (CSV, JSON, XML, HTML)
 */

class CrawlExporter
{
    /**
     * Export crawl results to CSV
     * 
     * @param array $crawlResult Crawl result array
     * @return string CSV content
     */
    public static function exportCsv(array $crawlResult): string
    {
        $items = $crawlResult['items'] ?? [];
        
        $csv = [];
        $csv[] = ['URL', 'Status', 'Has Error', 'Has Marker', 'Body Length', 'Depth', 'Note'];
        
        foreach ($items as $item) {
            $csv[] = [
                $item['url'] ?? '',
                $item['status'] ?? 0,
                ($item['error_flag'] ?? $item['has_error'] ?? false) ? 'Yes' : 'No',
                ($item['has_marker'] ?? false) ? 'Yes' : 'No',
                $item['body_length'] ?? 0,
                $item['depth'] ?? 0,
                $item['note'] ?? '',
            ];
        }
        
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        return $content;
    }
    
    /**
     * Export crawl results to JSON
     * 
     * @param array $crawlResult Crawl result array
     * @return string JSON content
     */
    public static function exportJson(array $crawlResult): string
    {
        return json_encode($crawlResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Export crawl results to XML
     * 
     * @param array $crawlResult Crawl result array
     * @return string XML content
     */
    public static function exportXml(array $crawlResult): string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $root = $xml->createElement('crawlResults');
        $xml->appendChild($root);
        
        $root->appendChild($xml->createElement('baseUrl', $crawlResult['base_url'] ?? ''));
        $root->appendChild($xml->createElement('username', $crawlResult['username'] ?? ''));
        $root->appendChild($xml->createElement('totalCount', $crawlResult['total_count'] ?? 0));
        $root->appendChild($xml->createElement('successCount', $crawlResult['success_count'] ?? 0));
        $root->appendChild($xml->createElement('errorCount', $crawlResult['error_count'] ?? 0));
        
        $itemsElement = $xml->createElement('items');
        $root->appendChild($itemsElement);
        
        foreach ($crawlResult['items'] ?? [] as $item) {
            $itemElement = $xml->createElement('item');
            $itemsElement->appendChild($itemElement);
            
            $itemElement->appendChild($xml->createElement('url', $item['url'] ?? ''));
            $itemElement->appendChild($xml->createElement('status', $item['status'] ?? 0));
            $itemElement->appendChild($xml->createElement('hasError', ($item['error_flag'] ?? $item['has_error'] ?? false) ? 'true' : 'false'));
            $itemElement->appendChild($xml->createElement('hasMarker', ($item['has_marker'] ?? false) ? 'true' : 'false'));
            $itemElement->appendChild($xml->createElement('bodyLength', $item['body_length'] ?? 0));
            $itemElement->appendChild($xml->createElement('depth', $item['depth'] ?? 0));
            $itemElement->appendChild($xml->createElement('note', $item['note'] ?? ''));
        }
        
        return $xml->saveXML();
    }
    
    /**
     * Export crawl results to HTML
     * 
     * @param array $crawlResult Crawl result array
     * @return string HTML content
     */
    public static function exportHtml(array $crawlResult): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Crawl Results</title>';
        $html .= '<style>table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background-color:#f2f2f2}</style>';
        $html .= '</head><body><h1>Crawl Results</h1>';
        $html .= '<p>Total: ' . ($crawlResult['total_count'] ?? 0) . ' | Success: ' . ($crawlResult['success_count'] ?? 0) . ' | Errors: ' . ($crawlResult['error_count'] ?? 0) . '</p>';
        $html .= '<table><thead><tr><th>URL</th><th>Status</th><th>Error</th><th>Marker</th><th>Body Length</th><th>Depth</th><th>Note</th></tr></thead><tbody>';
        
        foreach ($crawlResult['items'] ?? [] as $item) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['url'] ?? '') . '</td>';
            $html .= '<td>' . ($item['status'] ?? 0) . '</td>';
            $html .= '<td>' . (($item['error_flag'] ?? $item['has_error'] ?? false) ? 'Yes' : 'No') . '</td>';
            $html .= '<td>' . (($item['has_marker'] ?? false) ? 'Yes' : 'No') . '</td>';
            $html .= '<td>' . ($item['body_length'] ?? 0) . '</td>';
            $html .= '<td>' . ($item['depth'] ?? 0) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['note'] ?? '') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table></body></html>';
        return $html;
    }
}

