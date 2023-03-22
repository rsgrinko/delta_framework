<?php

    declare(strict_types=1);

    use Core\Models\User as TestClass;
    use PHPUnit\Framework\TestCase;

    final class UserTest extends TestCase
    {
        public function testGetAllUserData(): void
        {
            $testClassMock = $this->getMockBuilder(TestClass::class)->setMethodsExcept(['getAllUserData'])->getMock();
                                          //->getMockBuilder(TestClass::class)
                                          //->setConstructorArgs([1])
                                          //->getMock();

            $this->assertTrue(method_exists($testClassMock, 'getAllUserData'), TestClass::class . '\getAllUserData() - метод не найден');
        }

    }