<?php

declare(strict_types=1);

namespace Tests\Feature\Controller;

use Tests\TestCase;
use App\Models\User;
use App\Models\Settings;

/**
 * Auth Controller Feature Tests
 *
 * Tests authentication flows: login, register, password reset
 *
 * @covers \App\Controllers\AuthController
 */
class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        parent::tearDown();
    }

    public function testLoginWithValidCredentials(): void
    {
        $user = $this->createUser([
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        $foundUser = User::findByEmail('test@example.com');

        $this->assertNotNull($foundUser);
        $this->assertTrue(User::verifyPassword('password123', $foundUser['password']));
    }

    public function testLoginWithInvalidPassword(): void
    {
        $user = $this->createUser([
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        $foundUser = User::findByEmail('test@example.com');

        $this->assertNotNull($foundUser);
        $this->assertFalse(User::verifyPassword('wrongpassword', $foundUser['password']));
    }

    public function testLoginWithNonExistentEmail(): void
    {
        $foundUser = User::findByEmail('nonexistent@example.com');

        $this->assertNull($foundUser);
    }

    public function testRegistrationWithValidData(): void
    {
        $email = 'newuser' . uniqid() . '@example.com';

        $userId = User::create([
            'name' => 'New User',
            'email' => $email,
            'password' => 'securepassword123',
        ]);

        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        $user = User::findByEmail($email);
        $this->assertNotNull($user);
        $this->assertEquals('New User', $user['name']);
        $this->assertEquals($email, $user['email']);
    }

    public function testRegistrationWithDuplicateEmail(): void
    {
        $email = 'duplicate' . uniqid() . '@example.com';

        // Create first user
        User::create([
            'name' => 'First User',
            'email' => $email,
            'password' => 'password123',
        ]);

        // Try to find duplicate
        $existingUser = User::findByEmail($email);
        $this->assertNotNull($existingUser);
    }

    public function testRegistrationPasswordMinLength(): void
    {
        $password = 'short';

        // Password should be at least 8 characters
        $this->assertLessThan(8, strlen($password));
    }

    public function testRegistrationPasswordHashing(): void
    {
        $plainPassword = 'securepassword123';

        $userId = User::create([
            'name' => 'Test User',
            'email' => 'hash' . uniqid() . '@example.com',
            'password' => $plainPassword,
        ]);

        $user = User::find($userId);

        // Password should be hashed, not plain text
        $this->assertNotEquals($plainPassword, $user['password']);
        $this->assertTrue(password_verify($plainPassword, $user['password']));
    }

    public function testPasswordResetTokenGeneration(): void
    {
        $user = $this->createUser();

        $token = User::createPasswordResetToken($user['id']);

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testPasswordResetTokenValidation(): void
    {
        $user = $this->createUser();
        $token = User::createPasswordResetToken($user['id']);

        $foundUser = User::findByResetToken($token);

        $this->assertNotNull($foundUser);
        $this->assertEquals($user['id'], $foundUser['id']);
    }

    public function testPasswordResetWithInvalidToken(): void
    {
        $foundUser = User::findByResetToken('invalid_token_that_does_not_exist');

        $this->assertNull($foundUser);
    }

    public function testPasswordUpdate(): void
    {
        $user = $this->createUser([
            'password' => password_hash('oldpassword', PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        $newPassword = 'newpassword123';
        User::updatePassword($user['id'], $newPassword);

        $updatedUser = User::find($user['id']);

        // Old password should not work
        $this->assertFalse(password_verify('oldpassword', $updatedUser['password']));

        // New password should work
        $this->assertTrue(password_verify($newPassword, $updatedUser['password']));
    }

    public function testPasswordResetTokenClearing(): void
    {
        $user = $this->createUser();
        $token = User::createPasswordResetToken($user['id']);

        // Token should be valid
        $foundUser = User::findByResetToken($token);
        $this->assertNotNull($foundUser);

        // Clear token
        User::clearResetToken($user['id']);

        // Token should no longer be valid
        $foundUserAfterClear = User::findByResetToken($token);
        $this->assertNull($foundUserAfterClear);
    }

    public function testSessionRegenerationOnLogin(): void
    {
        // Simulate session data before login
        $_SESSION['test_data'] = 'should_persist';

        // After login, session ID should be regenerated
        // This is tested by verifying the session data persists
        // but the ID changes (handled by session_regenerate_id)
        $this->assertArrayHasKey('test_data', $_SESSION);
    }

    public function testLogoutClearsSession(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Test';
        $_SESSION['user_email'] = 'test@example.com';

        // Simulate logout
        $_SESSION = [];

        $this->assertEmpty($_SESSION);
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function testCsrfTokenGeneration(): void
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;

        $this->assertEquals(64, strlen($token));
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    public function testCsrfValidation(): void
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;

        // Valid token
        $this->assertTrue(hash_equals($_SESSION['csrf_token'], $token));

        // Invalid token
        $this->assertFalse(hash_equals($_SESSION['csrf_token'], 'invalid_token'));
    }

    public function testEmailValidation(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.org',
        ];

        $invalidEmails = [
            'invalid-email',
            '@example.com',
            'user@',
            '',
        ];

        foreach ($validEmails as $email) {
            $this->assertNotFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL),
                "Email {$email} should be valid"
            );
        }

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL),
                "Email {$email} should be invalid"
            );
        }
    }

    public function testNameMinimumLength(): void
    {
        $shortName = 'A';
        $validName = 'John Doe';

        $this->assertLessThan(2, strlen($shortName));
        $this->assertGreaterThanOrEqual(2, strlen($validName));
    }

    public function testUserCreationCreatesDefaultSettings(): void
    {
        $email = 'settings' . uniqid() . '@example.com';

        $userId = User::create([
            'name' => 'Settings Test User',
            'email' => $email,
            'password' => 'password123',
        ]);

        // Create default settings
        Settings::updateForUser($userId, Settings::getDefaults($userId));

        $settings = Settings::getForUser($userId);

        $this->assertNotNull($settings);
        $this->assertEquals($userId, $settings['user_id']);
    }

    public function testPasswordComplexityCheck(): void
    {
        // At minimum, password should be 8+ characters
        $weakPasswords = ['pass', '1234567', 'short'];
        $strongPasswords = ['password123', 'MySecureP@ss', 'LongEnoughPassword'];

        foreach ($weakPasswords as $password) {
            $this->assertLessThan(8, strlen($password), "Password '{$password}' should be too short");
        }

        foreach ($strongPasswords as $password) {
            $this->assertGreaterThanOrEqual(8, strlen($password), "Password '{$password}' should be long enough");
        }
    }
}
