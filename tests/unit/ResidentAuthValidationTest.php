<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Validator.php';

final class ResidentAuthValidationTest extends TestCase
{
    public function testPasswordRuleAcceptsStrongPassword(): void
    {
        $validator = new Validator([
            'password' => 'Güvenli123',
            'password_confirmation' => 'Güvenli123',
        ]);

        $validator
            ->password('password', ['min' => 8, 'require_symbol' => false])
            ->confirmed('password', 'password_confirmation');

        $this->assertFalse($validator->fails(), 'Strong password should pass validation');
    }

    public function testPasswordRuleRejectsWeakPassword(): void
    {
        $validator = new Validator([
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ]);

        $validator
            ->password('password', ['min' => 8, 'require_symbol' => false])
            ->confirmed('password', 'password_confirmation');

        $this->assertTrue($validator->fails(), 'Weak password should fail validation');
        $errors = $validator->errors();
        $this->assertArrayHasKey('password', $errors);
    }

    public function testPasswordRuleRejectsMissingUppercase(): void
    {
        $validator = new Validator([
            'password' => 'güvenli123',
        ]);

        $validator->password('password');

        $this->assertTrue($validator->fails());
        $this->assertSame('Şifre en az bir büyük harf içermelidir', $validator->errors()['password']);
    }

    public function testPasswordRuleRejectsMissingLowercase(): void
    {
        $validator = new Validator([
            'password' => 'GUVENLI123',
        ]);

        $validator->password('password');

        $this->assertTrue($validator->fails());
        $this->assertSame('Şifre en az bir küçük harf içermelidir', $validator->errors()['password']);
    }

    public function testPasswordRuleRejectsMissingNumber(): void
    {
        $validator = new Validator([
            'password' => 'GüvenliŞifre',
        ]);

        $validator->password('password');

        $this->assertTrue($validator->fails());
        $this->assertSame('Şifre en az bir rakam içermelidir', $validator->errors()['password']);
    }

    public function testPasswordRuleEnforcesSymbolWhenRequested(): void
    {
        $validator = new Validator([
            'password' => 'Guvenli123',
        ]);

        $validator->password('password', ['require_symbol' => true]);

        $this->assertTrue($validator->fails());
        $this->assertSame('Şifre en az bir özel karakter içermelidir', $validator->errors()['password']);
    }
}


