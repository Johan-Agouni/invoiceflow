<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use App\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return self::findBy('email', $email);
    }

    public static function create(array $data): int
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        return parent::create($data);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function updatePassword(int $id, string $newPassword): int
    {
        return self::update($id, [
            'password' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);
    }

    public static function createPasswordResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        Database::query(
            'UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?',
            [$token, $expiresAt, $userId]
        );

        return $token;
    }

    public static function findByResetToken(string $token): ?array
    {
        return Database::fetch(
            'SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()',
            [$token]
        );
    }

    public static function clearResetToken(int $userId): void
    {
        Database::query(
            'UPDATE users SET reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?',
            [$userId]
        );
    }
}
