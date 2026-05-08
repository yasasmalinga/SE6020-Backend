<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'cognito_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('cognito_id')->nullable()->unique()->after('id');
            });
        }

        if (! Schema::hasColumn('users', 'profile_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('profile_type')->default('candidate')->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'api_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('api_token')->nullable()->after('remember_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'api_token')) {
                $table->dropColumn('api_token');
            }

            if (Schema::hasColumn('users', 'profile_type')) {
                $table->dropColumn('profile_type');
            }

            if (Schema::hasColumn('users', 'cognito_id')) {
                $table->dropUnique('users_cognito_id_unique');
                $table->dropColumn('cognito_id');
            }
        });
    }
};
