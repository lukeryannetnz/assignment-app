<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_audit_logs', function (Blueprint $table): void {
            $table->index(['tenant_id', 'created_at'], 'tenant_audit_logs_tenant_created_at_index');
            $table->index(['tenant_id', 'action', 'created_at'], 'tenant_audit_logs_tenant_action_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_audit_logs', function (Blueprint $table): void {
            $table->dropIndex('tenant_audit_logs_tenant_created_at_index');
            $table->dropIndex('tenant_audit_logs_tenant_action_created_at_index');
        });
    }
};
