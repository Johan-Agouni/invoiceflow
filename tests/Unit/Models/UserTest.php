<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;

/**
 * User Model Tests
 *
 * @covers \App\Models\User
 */
class UserTest extends TestCase
{
    public function testCanCreateUser(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $userId = User::create($userData);

        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'email' => 'john@example.com',
        ]);
    }

    public function testPasswordIsHashedOnCreate(): void
    {
        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'plainpassword',
        ];

        $userId = User::create($userData);
        $user = User::find($userId);

        // Password should be hashed, not plain text
        $this->assertNotEquals('plainpassword', $user['password']);
        $this->assertTrue(password_verify('plainpassword', $user['password']));
    }

    public function testCanFindUserByEmail(): void
    {
        $user = $this->createUser(['email' => 'findme@example.com']);

        $found = User::findByEmail('findme@example.com');

        $this->assertNotNull($found);
        $this->assertEquals($user['id'], $found['id']);
    }

    public function testReturnsNullForNonExistentEmail(): void
    {
        $found = User::findByEmail('nonexistent@example.com');

        $this->assertNull($found);
    }

    public function testCanVerifyPassword(): void
    {
        $hash = password_hash('secret123', PASSWORD_BCRYPT);

        $this->assertTrue(User::verifyPassword('secret123', $hash));
        $this->assertFalse(User::verifyPassword('wrongpassword', $hash));
    }

    public function testCanUpdatePassword(): void
    {
        $user = $this->createUser();

        User::updatePassword($user['id'], 'newpassword456');

        $updated = User::find($user['id']);

        $this->assertTrue(password_verify('newpassword456', $updated['password']));
        $this->assertFalse(password_verify('password123', $updated['password']));
    }

    public function testCanCreatePasswordResetToken(): void
    {
        $user = $this->createUser();

        $token = User::createPasswordResetToken($user['id']);

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars

        $updated = User::find($user['id']);
        $this->assertNotNull($updated['reset_token']);
        $this->assertNotNull($updated['reset_token_expires_at']);
    }

    public function testCanFindUserByValidResetToken(): void
    {
        $user = $this->createUser();
        $token = User::createPasswordResetToken($user['id']);

        $found = User::findByResetToken($token);

        $this->assertNotNull($found);
        $this->assertEquals($user['id'], $found['id']);
    }

    public function testReturnsNullForInvalidResetToken(): void
    {
        $found = User::findByResetToken('invalid_token_that_does_not_exist');

        $this->assertNull($found);
    }

    public function testCanClearResetToken(): void
    {
        $user = $this->createUser();
        User::createPasswordResetToken($user['id']);

        User::clearResetToken($user['id']);

        $updated = User::find($user['id']);
        $this->assertNull($updated['reset_token']);
        $this->assertNull($updated['reset_token_expires_at']);
    }
}
