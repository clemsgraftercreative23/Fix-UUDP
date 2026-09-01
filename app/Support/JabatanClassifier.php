<?php

namespace App\Support;

class JabatanClassifier
{
    /**
     * Users end up with an empty/'-' jabatan after being demoted from an
     * admin-like role (see UseraplikasiController::remove_jabatan history).
     * Treat those the same as 'karyawan' so they still see the employee
     * sidebar/menu instead of being mislabeled "Admin" with no menu at all.
     */
    public static function isEmployeeLike(?string $jabatan): bool
    {
        return in_array($jabatan, ['karyawan', '-', '', null], true);
    }

    /**
     * Owner and Finance are both allowed to trigger "Sync ke Accurate"
     * (Finance Supervisor is a distinct jabatan and not included here).
     */
    public static function canSyncAccurate(?string $jabatan): bool
    {
        return in_array($jabatan, ['Owner', 'Finance', 'superadmin', 'admin'], true);
    }

    /**
     * Reversing a sync deletes the entry from Accurate's real books, so it's
     * scoped narrower than "Sync ke Accurate" itself -- same roles allowed
     * to reset a settlement (Finance is intentionally excluded here).
     */
    public static function canReverseAccurateSync(?string $jabatan): bool
    {
        return in_array($jabatan, ['Owner', 'superadmin', 'admin'], true);
    }
}
