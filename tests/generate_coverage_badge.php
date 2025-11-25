<?php
/**
 * Coverage Badge Generator
 * Generates SVG badge showing coverage percentage
 */

$coverageFile = __DIR__ . '/coverage/coverage.json';

if (!file_exists($coverageFile)) {
    echo "Coverage file not found. Run tests with coverage first.\n";
    exit(1);
}

$coverageData = json_decode(file_get_contents($coverageFile), true);
$coverage = $coverageData['totals']['lines']['percent'] ?? 0;
$coverage = round($coverage, 1);

// Determine badge color
if ($coverage >= 80) {
    $color = 'brightgreen';
} elseif ($coverage >= 60) {
    $color = 'yellow';
} elseif ($coverage >= 40) {
    $color = 'orange';
} else {
    $color = 'red';
}

// Generate SVG badge
$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="20">
  <linearGradient id="b" x2="0" y2="100%">
    <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <mask id="a">
    <rect width="120" height="20" rx="3" fill="#fff"/>
  </mask>
  <g mask="url(#a)">
    <path fill="#555" d="M0 0h63v20H0z"/>
    <path fill="#{$color}" d="M63 0h57v20H63z"/>
    <path fill="url(#b)" d="M0 0h120v20H0z"/>
  </g>
  <g fill="#fff" text-anchor="middle" font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">
    <text x="31.5" y="15" fill="#010101" fill-opacity=".3">coverage</text>
    <text x="31.5" y="14">coverage</text>
    <text x="91.5" y="15" fill="#010101" fill-opacity=".3">{$coverage}%</text>
    <text x="91.5" y="14">{$coverage}%</text>
  </g>
</svg>
SVG;

$badgeFile = __DIR__ . '/coverage/badge.svg';
file_put_contents($badgeFile, $svg);

echo "Coverage badge generated: {$badgeFile}\n";
echo "Coverage: {$coverage}%\n";

