<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->index(['category_id', 'deleted_at'], 'ingredients_category_deleted_idx');
            $table->index('created_at', 'ingredients_created_at_idx');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->index(['category_id', 'deleted_at'], 'menus_category_deleted_idx');
            $table->index(['sort_order', 'created_at'], 'menus_sort_created_idx');
        });

        Schema::table('menu_variants', function (Blueprint $table) {
            $table->index(['menu_id', 'is_available', 'sort_order'], 'menu_variants_menu_available_sort_idx');
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->index('transaction_id', 'transaction_details_transaction_id_idx');
            $table->index('menu_id', 'transaction_details_menu_id_idx');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->index(['type', 'created_at'], 'stock_logs_type_created_idx');
            $table->index('reference_id', 'stock_logs_reference_id_idx');
        });

        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->index(['type', 'entry_date'], 'cashflow_entries_type_entry_date_idx');
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'api_tokens_user_created_idx');
            $table->index('expires_at', 'api_tokens_expires_at_idx');
        });

        Schema::table('password_otps', function (Blueprint $table) {
            $table->index(['user_id', 'used', 'created_at'], 'password_otps_user_used_created_idx');
            $table->index('expires_at', 'password_otps_expires_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('password_otps', function (Blueprint $table) {
            $table->dropIndex('password_otps_user_used_created_idx');
            $table->dropIndex('password_otps_expires_at_idx');
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropIndex('api_tokens_user_created_idx');
            $table->dropIndex('api_tokens_expires_at_idx');
        });

        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->dropIndex('cashflow_entries_type_entry_date_idx');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->dropIndex('stock_logs_type_created_idx');
            $table->dropIndex('stock_logs_reference_id_idx');
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropIndex('transaction_details_transaction_id_idx');
            $table->dropIndex('transaction_details_menu_id_idx');
        });

        Schema::table('menu_variants', function (Blueprint $table) {
            $table->dropIndex('menu_variants_menu_available_sort_idx');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropIndex('menus_category_deleted_idx');
            $table->dropIndex('menus_sort_created_idx');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropIndex('ingredients_category_deleted_idx');
            $table->dropIndex('ingredients_created_at_idx');
        });
    }
};
