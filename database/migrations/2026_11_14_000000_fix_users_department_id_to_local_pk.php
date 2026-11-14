<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixUsersDepartmentIdToLocalPk extends Migration
{
    /**
     * Sync karyawan dulu menyimpan ID Accurate (departemen.id_dep) di users.departmentId,
     * padahal aplikasi membacanya sebagai PK lokal (departemen.id). Ubah baris yang terlanjur salah.
     */
    public function up()
    {
        DB::statement(
            'UPDATE users u JOIN departemen d ON u.departmentId = d.id_dep '
            . 'SET u.departmentId = d.id WHERE u.departmentId <> d.id'
        );
    }

    public function down()
    {
        // Data fix satu arah.
    }
}
