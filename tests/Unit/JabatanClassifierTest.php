<?php

namespace Tests\Unit;

use App\Support\JabatanClassifier;
use Tests\TestCase;

class JabatanClassifierTest extends TestCase
{
    public function test_karyawan_is_employee_like(): void
    {
        $this->assertTrue(JabatanClassifier::isEmployeeLike('karyawan'));
    }

    public function test_dash_placeholder_is_employee_like(): void
    {
        $this->assertTrue(JabatanClassifier::isEmployeeLike('-'));
    }

    public function test_empty_string_is_employee_like(): void
    {
        $this->assertTrue(JabatanClassifier::isEmployeeLike(''));
    }

    public function test_null_is_employee_like(): void
    {
        $this->assertTrue(JabatanClassifier::isEmployeeLike(null));
    }

    public function test_admin_roles_are_not_employee_like(): void
    {
        $this->assertFalse(JabatanClassifier::isEmployeeLike('Owner'));
        $this->assertFalse(JabatanClassifier::isEmployeeLike('Finance'));
        $this->assertFalse(JabatanClassifier::isEmployeeLike('Finance Supervisor'));
        $this->assertFalse(JabatanClassifier::isEmployeeLike('Direktur Operasional'));
    }

    public function test_is_case_sensitive_to_match_existing_blade_comparisons(): void
    {
        $this->assertFalse(JabatanClassifier::isEmployeeLike('Karyawan'));
    }

    public function test_owner_and_finance_can_sync_accurate(): void
    {
        $this->assertTrue(JabatanClassifier::canSyncAccurate('Owner'));
        $this->assertTrue(JabatanClassifier::canSyncAccurate('Finance'));
    }

    public function test_other_roles_cannot_sync_accurate(): void
    {
        $this->assertFalse(JabatanClassifier::canSyncAccurate('Finance Supervisor'));
        $this->assertFalse(JabatanClassifier::canSyncAccurate('Direktur Operasional'));
        $this->assertFalse(JabatanClassifier::canSyncAccurate('karyawan'));
        $this->assertFalse(JabatanClassifier::canSyncAccurate(null));
    }
}
