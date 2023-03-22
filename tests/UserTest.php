<?php

    declare(strict_types=1);

    use Core\Models\User as TestClass;
    use PHPUnit\Framework\TestCase;

    final class UserTest extends TestCase
    {
        public function testGetAllUserData(): void
        {
            $this->assertTrue(method_exists(TestClass::class, 'getAllUserData'), TestClass::class . '\getAllUserData() - метод не найден');
        }

    }