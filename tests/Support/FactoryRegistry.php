<?php
namespace Tests\Support;

use Database;
use Tests\Support\Factories\AddressFactory;
use Tests\Support\Factories\BuildingFactory;
use Tests\Support\Factories\CompanyFactory;
use Tests\Support\Factories\ContractFactory;
use Tests\Support\Factories\CustomerFactory;
use Tests\Support\Factories\JobFactory;
use Tests\Support\Factories\PaymentFactory;
use Tests\Support\Factories\ResidentUserFactory;
use Tests\Support\Factories\ServiceFactory;
use Tests\Support\Factories\UnitFactory;
use Tests\Support\Factories\UserFactory;

class FactoryRegistry
{
    private static ?Database $db = null;
    private static array $factories = [];

    public static function setDatabase(Database $db): void
    {
        self::$db = $db;
    }

    private static function getDatabase(): Database
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    public static function user(): UserFactory
    {
        return self::$factories[UserFactory::class] ??= UserFactory::getInstance(self::getDatabase());
    }

    public static function customer(): CustomerFactory
    {
        return self::$factories[CustomerFactory::class] ??= CustomerFactory::getInstance(self::getDatabase());
    }

    public static function job(): JobFactory
    {
        return self::$factories[JobFactory::class] ??= JobFactory::getInstance(self::getDatabase());
    }

    public static function building(): BuildingFactory
    {
        return self::$factories[BuildingFactory::class] ??= BuildingFactory::getInstance(self::getDatabase());
    }

    public static function unit(): UnitFactory
    {
        return self::$factories[UnitFactory::class] ??= UnitFactory::getInstance(self::getDatabase());
    }

    public static function residentUser(): ResidentUserFactory
    {
        return self::$factories[ResidentUserFactory::class] ??= ResidentUserFactory::getInstance(self::getDatabase());
    }

    public static function company(): CompanyFactory
    {
        return self::$factories[CompanyFactory::class] ??= CompanyFactory::getInstance(self::getDatabase());
    }

    public static function payment(): PaymentFactory
    {
        return self::$factories[PaymentFactory::class] ??= PaymentFactory::getInstance(self::getDatabase());
    }

    public static function contract(): ContractFactory
    {
        return self::$factories[ContractFactory::class] ??= ContractFactory::getInstance(self::getDatabase());
    }

    public static function service(): ServiceFactory
    {
        return self::$factories[ServiceFactory::class] ??= ServiceFactory::getInstance(self::getDatabase());
    }

    public static function address(): AddressFactory
    {
        return self::$factories[AddressFactory::class] ??= AddressFactory::getInstance(self::getDatabase());
    }

    public static function reset(): void
    {
        self::$factories = [];
        self::$db = null;
    }
}
