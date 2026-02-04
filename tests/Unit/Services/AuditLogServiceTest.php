<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuditLogService;

class AuditLogServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AuditLogService::setCurrentUser(null);
    }

    public function testLogCreatesEntryInDatabase(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        $logId = AuditLogService::log(
            AuditLogService::ACTION_CREATE,
            AuditLogService::ENTITY_CLIENT,
            123,
            null,
            ['company_name' => 'Test Company']
        );

        $this->assertGreaterThan(0, $logId);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $logId,
            'user_id' => $user['id'],
            'action' => 'create',
            'entity_type' => 'client',
            'entity_id' => 123,
        ]);
    }

    public function testLogCreateShortcut(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        $logId = AuditLogService::logCreate(
            AuditLogService::ENTITY_INVOICE,
            456,
            ['number' => 'FAC-2024-001', 'total' => 1200]
        );

        $this->assertGreaterThan(0, $logId);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $logId,
            'action' => 'create',
            'entity_type' => 'invoice',
            'entity_id' => 456,
        ]);
    }

    public function testLogUpdateShortcutOnlyLogsChanges(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        $oldData = ['status' => 'draft', 'total' => 1000, 'notes' => 'Test'];
        $newData = ['status' => 'pending', 'total' => 1000, 'notes' => 'Test'];

        $logId = AuditLogService::logUpdate(
            AuditLogService::ENTITY_INVOICE,
            789,
            $oldData,
            $newData
        );

        $this->assertGreaterThan(0, $logId);

        $logs = AuditLogService::getLogsForEntity(AuditLogService::ENTITY_INVOICE, 789, 1);
        $this->assertCount(1, $logs);

        $logEntry = $logs[0];
        $oldValues = json_decode($logEntry['old_values'], true);
        $newValues = json_decode($logEntry['new_values'], true);

        // Seul le statut a changé
        $this->assertArrayHasKey('status', $oldValues);
        $this->assertArrayNotHasKey('total', $oldValues);
        $this->assertEquals('draft', $oldValues['status']);
        $this->assertEquals('pending', $newValues['status']);
    }

    public function testLogUpdateReturnsZeroWhenNoChanges(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        $data = ['status' => 'draft', 'total' => 1000];

        $logId = AuditLogService::logUpdate(
            AuditLogService::ENTITY_INVOICE,
            789,
            $data,
            $data
        );

        $this->assertEquals(0, $logId);
    }

    public function testLogDeleteShortcut(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        $logId = AuditLogService::logDelete(
            AuditLogService::ENTITY_CLIENT,
            999,
            ['company_name' => 'Deleted Company']
        );

        $this->assertGreaterThan(0, $logId);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $logId,
            'action' => 'delete',
            'entity_type' => 'client',
            'entity_id' => 999,
        ]);
    }

    public function testLogStatusChangeShortcut(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        $logId = AuditLogService::logStatusChange(
            AuditLogService::ENTITY_INVOICE,
            123,
            'pending',
            'paid',
            ['payment_method' => 'stripe']
        );

        $this->assertGreaterThan(0, $logId);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $logId,
            'action' => 'status_change',
            'entity_type' => 'invoice',
            'entity_id' => 123,
        ]);
    }

    public function testLogLoginShortcut(): void
    {
        $user = $this->createUser();

        $logId = AuditLogService::logLogin($user['id'], true);

        $this->assertGreaterThan(0, $logId);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $logId,
            'action' => 'login',
            'entity_type' => 'user',
            'entity_id' => $user['id'],
        ]);
    }

    public function testLogLoginFailedShortcut(): void
    {
        $user = $this->createUser();

        $logId = AuditLogService::logLogin($user['id'], false, 'Invalid password');

        $this->assertGreaterThan(0, $logId);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $logId,
            'action' => 'login_failed',
            'entity_type' => 'user',
            'entity_id' => $user['id'],
        ]);
    }

    public function testGetLogsForUser(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        AuditLogService::logCreate(AuditLogService::ENTITY_CLIENT, 1, []);
        AuditLogService::logCreate(AuditLogService::ENTITY_CLIENT, 2, []);
        AuditLogService::logCreate(AuditLogService::ENTITY_INVOICE, 3, []);

        $logs = AuditLogService::getLogsForUser($user['id']);

        $this->assertCount(3, $logs);
    }

    public function testGetLogsForEntity(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        AuditLogService::logCreate(AuditLogService::ENTITY_INVOICE, 100, []);
        AuditLogService::logStatusChange(AuditLogService::ENTITY_INVOICE, 100, 'draft', 'pending');
        AuditLogService::logStatusChange(AuditLogService::ENTITY_INVOICE, 100, 'pending', 'paid');

        $logs = AuditLogService::getLogsForEntity(AuditLogService::ENTITY_INVOICE, 100);

        $this->assertCount(3, $logs);
    }

    public function testSearchWithFilters(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        AuditLogService::logCreate(AuditLogService::ENTITY_CLIENT, 1, []);
        AuditLogService::logCreate(AuditLogService::ENTITY_INVOICE, 2, []);
        AuditLogService::logDelete(AuditLogService::ENTITY_CLIENT, 3, []);

        $results = AuditLogService::search([
            'entity_type' => AuditLogService::ENTITY_CLIENT,
        ]);

        $this->assertCount(2, $results);
    }

    public function testSearchWithActionFilter(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        AuditLogService::logCreate(AuditLogService::ENTITY_CLIENT, 1, []);
        AuditLogService::logCreate(AuditLogService::ENTITY_CLIENT, 2, []);
        AuditLogService::logDelete(AuditLogService::ENTITY_CLIENT, 1, []);

        $results = AuditLogService::search([
            'action' => AuditLogService::ACTION_DELETE,
        ]);

        $this->assertCount(1, $results);
    }

    public function testCountLogs(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        AuditLogService::logCreate(AuditLogService::ENTITY_CLIENT, 1, []);
        AuditLogService::logCreate(AuditLogService::ENTITY_CLIENT, 2, []);
        AuditLogService::logCreate(AuditLogService::ENTITY_CLIENT, 3, []);

        $count = AuditLogService::count(['user_id' => $user['id']]);

        $this->assertEquals(3, $count);
    }

    public function testSensitiveDataIsFiltered(): void
    {
        $user = $this->createUser();
        AuditLogService::setCurrentUser($user['id']);

        $logId = AuditLogService::logCreate(
            AuditLogService::ENTITY_USER,
            $user['id'],
            [
                'email' => 'test@example.com',
                'password' => 'secret123',
                'two_factor_secret' => 'ABCD1234',
                'bank_iban' => 'FR76123456789',
            ]
        );

        $logs = AuditLogService::getLogsForEntity(AuditLogService::ENTITY_USER, $user['id'], 1);
        $newValues = json_decode($logs[0]['new_values'], true);

        $this->assertEquals('test@example.com', $newValues['email']);
        $this->assertEquals('[REDACTED]', $newValues['password']);
        $this->assertEquals('[REDACTED]', $newValues['two_factor_secret']);
        $this->assertEquals('[REDACTED]', $newValues['bank_iban']);
    }
}
