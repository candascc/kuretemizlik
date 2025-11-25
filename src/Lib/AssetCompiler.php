<?php

/**
 * Simple asset bundler for CSS/JS files.
 */
class AssetCompiler
{
    /**
     * Build and minify JavaScript bundle.
     *
     * @param string[] $relativeFiles
     */
    public static function buildJs(array $relativeFiles, string $relativeOutput): void
    {
        $base = self::assetBasePath();
        $buffer = [];

        foreach ($relativeFiles as $file) {
            $path = $base . $file;
            if (!is_file($path)) {
                throw new RuntimeException("JS asset not found: {$file}");
            }
            $buffer[] = file_get_contents($path);
        }

        $combined = implode("\n;\n", $buffer);
        $minified = AssetCompiler_JSMin::minify($combined);

        self::writeAsset($relativeOutput, $minified);
    }

    /**
     * Build and minify CSS bundle.
     *
     * @param string[] $relativeFiles
     */
    public static function buildCss(array $relativeFiles, string $relativeOutput): void
    {
        $base = self::assetBasePath();
        $buffer = [];

        foreach ($relativeFiles as $file) {
            $path = $base . $file;
            if (!is_file($path)) {
                throw new RuntimeException("CSS asset not found: {$file}");
            }
            $buffer[] = file_get_contents($path);
        }

        $combined = implode("\n", $buffer);
        $minified = self::minifyCss($combined);

        self::writeAsset($relativeOutput, $minified);
    }

    private static function writeAsset(string $relativeOutput, string $contents): void
    {
        $outputPath = self::assetBasePath() . ltrim($relativeOutput, '/');
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
                throw new RuntimeException("Unable to create asset directory: {$outputDir}");
            }
        }

        file_put_contents($outputPath, $contents);
    }

    private static function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*.*?\*/!s', '', $css);
        $css = preg_replace('/\s*([{};:,])\s*/', '$1', $css);
        $css = preg_replace('/,\s+/', ',', $css);
        $css = preg_replace('/\s+!important/', '!important', $css);
        $css = preg_replace('/\s*\)\s*/', ')', $css);
        $css = preg_replace('/\s*\(\s*/', '(', $css);
        // Trim leading zeroes on decimals (e.g. 0.5 -> .5)
        $css = preg_replace('/(?<=[:\s])0+(\.\d+)/', '$1', $css);
        // Collapse multiple spaces that may remain from custom utilities
        $css = preg_replace('/;}/', '}', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        return trim($css);
    }

    private static function assetBasePath(): string
    {
        return dirname(__DIR__, 2) . '/assets/';
    }
}

/**
 * JSMin implementation (MIT Licensed) adapted from https://github.com/rgrove/jsmin-php
 */
class AssetCompiler_JSMin
{
    const ORD_LF = 10;
    const ORD_SPACE = 32;

    protected $a = '';
    protected $b = '';
    protected $input = '';
    protected $inputIndex = 0;
    protected $inputLength = 0;
    protected $lookAhead = null;
    protected $output = '';

    /**
     * Minify Javascript.
     */
    public static function minify($js)
    {
        $jsmin = new self($js);
        return $jsmin->min();
    }

    public function __construct($input)
    {
        if (!extension_loaded('mbstring')) {
            throw new RuntimeException('JSMin requires mbstring extension.');
        }

        // Normalize line endings
        $input = str_replace("\r\n", "\n", $input);
        $input = str_replace("\r", "\n", $input);

        $this->input = $input;
        $this->inputLength = mb_strlen($input, 'ASCII');
    }

    protected function min()
    {
        $this->a = "\n";
        $this->b = '';

        $this->action(3);

        while ($this->a !== null) {
            switch ($this->a) {
                case ' ':
                    if ($this->isAlphaNum($this->b)) {
                        $this->action(1);
                    } else {
                        $this->action(2);
                    }
                    break;

                case "\n":
                    switch ($this->b) {
                        case '{':
                        case '[':
                        case '(':
                        case '+':
                        case '-':
                            $this->action(1);
                            break;

                        case ' ':
                            $this->action(3);
                            break;

                        default:
                            if ($this->isAlphaNum($this->b)) {
                                $this->action(1);
                            } else {
                                $this->action(2);
                            }
                    }
                    break;

                default:
                    switch ($this->b) {
                        case ' ':
                            if ($this->isAlphaNum($this->a)) {
                                $this->action(1);
                                break;
                            }
                            $this->action(3);
                            break;

                        case "\n":
                            switch ($this->a) {
                                case '}':
                                case ']':
                                case ')':
                                case '+':
                                case '-':
                                case '"':
                                case '\'':
                                    $this->action(1);
                                    break 2;

                                default:
                                    if ($this->isAlphaNum($this->a)) {
                                        $this->action(1);
                                    } else {
                                        $this->action(3);
                                    }
                            }
                            break;

                        default:
                            $this->action(1);
                            break;
                    }
            }
        }

        return $this->output;
    }

    protected function action($d)
    {
        switch ($d) {
            case 1:
                $this->output .= $this->a;

            case 2:
                $this->a = $this->b;

                if ($this->a === '\'' || $this->a === '"') {
                    for (;;) {
                        $this->output .= $this->a;
                        $this->a = $this->get();

                        if ($this->a === $this->b) {
                            break;
                        }

                        if ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->a = $this->get();
                        }

                        if ($this->a === null) {
                            throw new RuntimeException('Unterminated string literal.');
                        }
                    }
                }

            case 3:
                $this->b = $this->next();

                if ($this->b === '/' && (
                        $this->a === '(' || $this->a === ',' || $this->a === '=' ||
                        $this->a === ':' || $this->a === '[' || $this->a === '!' ||
                        $this->a === '&' || $this->a === '|' || $this->a === '?' ||
                        $this->a === '{' || $this->a === '}' || $this->a === ';' ||
                        $this->a === "\n"
                    )) {

                    $this->output .= $this->a . $this->b;

                    for (;;) {
                        $this->a = $this->get();

                        if ($this->a === '/') {
                            break;
                        } elseif ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->a = $this->get();
                        } elseif ($this->a === null) {
                            throw new RuntimeException('Unterminated RegExp literal.');
                        }

                        $this->output .= $this->a;
                    }

                    $this->b = $this->next();
                }
        }
    }

    protected function next()
    {
        $c = $this->get();

        if ($c === '/') {
            switch ($this->peek()) {
                case '/':
                    for (;;) {
                        $c = $this->get();
                        if ($c === "\n" || $c === null) {
                            return $c;
                        }
                    }

                case '*':
                    $this->get();
                    for (;;) {
                        switch ($this->get()) {
                            case '*':
                                if ($this->peek() === '/') {
                                    $this->get();
                                    return ' ';
                                }
                                break;

                            case null:
                                throw new RuntimeException('Unterminated comment.');
                        }
                    }

                default:
                    return $c;
            }
        }

        return $c;
    }

    protected function get()
    {
        $c = $this->lookAhead;
        $this->lookAhead = null;

        if ($c === null) {
            if ($this->inputIndex < $this->inputLength) {
                $c = mb_substr($this->input, $this->inputIndex, 1, 'ASCII');
                $this->inputIndex += 1;
            } else {
                $c = null;
            }
        }

        if ($c === "\r") {
            return "\n";
        }

        if ($c === null || $c === "\n" || ord($c) >= self::ORD_SPACE) {
            return $c;
        }

        return ' ';
    }

    protected function peek()
    {
        $this->lookAhead = $this->get();
        return $this->lookAhead;
    }

    protected function isAlphaNum($c)
    {
        return preg_match('/^[0-9a-zA-Z_\\$\\\\]$/', $c) ||
            ord($c) > 126;
    }
}

