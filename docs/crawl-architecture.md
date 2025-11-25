# Crawl System Architecture

## Overview

The crawl system consists of multiple components working together to test application pages.

## Components

### 1. Crawl Runners
- `BaseCrawlRunner`: Abstract base class with common crawl logic
- `AdminCrawlRunner`: Admin role crawl runner
- `SysadminCrawlRunner`: Sysadmin role crawl runner

### 2. Services
- `InternalCrawlService`: Direct route execution (no HTTP)
- `CrawlClient`: HTTP-based crawling
- `SessionManager`: Session backup/restore
- `ErrorDetector`: Error pattern detection
- `CrawlLogger`: Centralized logging
- `CrawlExporter`: Export results to various formats
- `CrawlComparator`: Compare crawl results
- `CrawlProgressTracker`: Track crawl progress

### 3. Configuration
- `CrawlConfig`: Centralized configuration management

### 4. Helpers
- `RouterHelper`: Router instance management

## Flow

1. User initiates crawl via web UI or API
2. Session is backed up using SessionManager
3. Test user login is performed
4. Crawl starts from seed URLs
5. Links are extracted and queued
6. Each URL is visited and checked for errors
7. Results are collected
8. Session is restored
9. Results are displayed or exported

