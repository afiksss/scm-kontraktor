<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $t) {
            $t->id();
            $t->string('permission')->unique();
            $t->json('roles');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
