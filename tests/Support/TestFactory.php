<?php
namespace Tests\Support;

use Database;

abstract class TestFactory
{
    protected Database $db;
    protected static array $instances = [];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public static function getInstance(Database $db): static
    {
        $class = static::class;
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static($db);
        }
        return self::$instances[$class];
    }

    abstract public function create(array $attributes = []): int;

    /**
     * Create multiple records
     */
    public function createMany(int $count, array $attributes = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $this->create($attributes);
        }
        return $ids;
    }

    /**
     * Get Faker instance
     * 
     * @return \Faker\Generator|object Faker generator instance
     */
    protected function faker()
    {
        static $fakerInstance = null;
        
        if ($fakerInstance === null) {
            if (class_exists('\Faker\Factory')) {
                $fakerInstance = \Faker\Factory::create('tr_TR');
            } else {
                // Fallback if Faker is not available - create a mock object
                // We use object return type instead of \Faker\Generator to avoid type mismatch
                $fakerInstance = new class {
                    public function name() { return 'Test Name ' . uniqid(); }
                    public function userName() { return 'test_user_' . uniqid(); }
                    public function email() { return 'test' . uniqid() . '@example.com'; }
                    public function safeEmail() { return 'test' . uniqid() . '@example.com'; }
                    public function companyEmail() { return 'company' . uniqid() . '@example.com'; }
                    public function phoneNumber() { return '555' . rand(1000000, 9999999); }
                    public function address() { return 'Test Street ' . rand(1, 100); }
                    public function city() { return 'İstanbul'; }
                    public function postcode() { return rand(34000, 34999); }
                    public function word() { return 'test'; }
                    public function sentence($words = 5) { return 'Test sentence with words.'; }
                    public function paragraph() { return 'Test paragraph.'; }
                    public function numberBetween($min, $max) { return rand($min, $max); }
                    public function randomFloat($decimals, $min, $max) { return round($min + ($max - $min) * mt_rand() / mt_getrandmax(), $decimals); }
                    public function randomElement(array $array) { return $array[array_rand($array)]; }
                    public function date($format = 'Y-m-d', $max = 'now') { return date($format); }
                    public function dateTimeBetween($start, $end) { return new \DateTime(); }
                    public function bothify($string) { return str_replace(['#', '?'], [rand(0, 9), chr(rand(97, 122))], $string); }
                    public function unique() { return $this; }
                    public function taxpayerIdentificationNumber() { return rand(100000000, 999999999); }
                    public function domainWord() { return 'test' . uniqid(); }
                    public function optional($probability = 0.5) { return rand(0, 100) / 100 < $probability ? $this : null; }
                    public function numerify($string) { return preg_replace_callback('/#/', function() { return rand(0, 9); }, $string); }
                    public function boolean() { return (bool)rand(0, 1); }
                    public function streetAddress() { return 'Test Street ' . rand(1, 100); }
                    public function company() { return 'Test Company ' . uniqid(); }
                };
            }
        }
        
        return $fakerInstance;
    }

    /**
     * Merge attributes with defaults
     * 
     * @param array $defaults Default attributes
     * @param array $attributes Custom attributes
     * @return array Merged attributes
     */
    protected static function mergeAttributes(array $defaults, array $attributes): array
    {
        return array_merge($defaults, $attributes);
    }

    /**
     * Generate Turkish phone number
     */
    protected function turkishPhone(): string
    {
        $faker = $this->faker();
        return '+90 ' . $faker->numerify('5## ### ## ##');
    }

    /**
     * Generate Turkish tax number (11 digits)
     */
    protected function turkishTaxNumber(): string
    {
        $faker = $this->faker();
        return $faker->numerify('###########');
    }

    /**
     * Generate IBAN (Turkish format)
     */
    protected function turkishIban(): string
    {
        $faker = $this->faker();
        return 'TR' . $faker->numerify('## #### #### #### #### #### ##');
    }
}

