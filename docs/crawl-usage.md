# Crawl Usage Guide

## Configuration

Set environment variables:

```bash
CRAWL_TEST_PASSWORD=your_password
CRAWL_MAX_URLS=100
CRAWL_MAX_DEPTH=5
CRAWL_MAX_EXECUTION_TIME=60
CRAWL_LOG_LEVEL=INFO
CRAWL_DEBUG=false
```

## Web UI

1. Navigate to `/sysadmin/crawl?role=SUPERADMIN`
2. Select role from dropdown
3. Click "Test Başlat"
4. View results in table

## API Usage

```bash
curl -X POST http://example.com/sysadmin/remote-crawl \
  -H "Content-Type: application/json" \
  -d '{"role": "SUPERADMIN"}'
```

## Export Results

Use `CrawlExporter` class:

```php
$csv = CrawlExporter::exportCsv($crawlResult);
$json = CrawlExporter::exportJson($crawlResult);
$xml = CrawlExporter::exportXml($crawlResult);
$html = CrawlExporter::exportHtml($crawlResult);
```

## Compare Results

```php
$comparison = CrawlComparator::compare($result1, $result2);
```

## Troubleshooting

- Check logs in `sys_get_temp_dir()/crawl_*.log`
- Verify `CRAWL_TEST_PASSWORD` is set
- Check session restore after crawl
- Verify router is available

