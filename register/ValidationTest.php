<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../function/validation.php';

class ValidationTest extends TestCase
{
    public function testValidateLogin_Valid()
    {
        $result = validateLogin('test_user_123');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function testValidateLogin_InvalidChars()
    {
        $result = validateLogin('неверный-логин!');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Логин может содержать только английские буквы, цифры и подчеркивание', $result['error']);
    }

    public function testValidatePhone_Valid()
    {
        $result = validatePhone('79123456789');
        $this->assertTrue($result['valid']);
    }

    public function testFormatPhoneNumber_From8()
    {
        $formatted = formatPhoneNumber('89123456789');
        $this->assertEquals('79123456789', $formatted);
    }
}