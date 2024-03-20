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
        Schema::table('articles', static function (Blueprint $table) {
			$table->string('title');
			$table->text('excerpt');
			$table->integer('user_id')->unsigned();
			$table->foreign('user_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', static function (Blueprint $table) {
			$table->dropColumn('title');
			$table->dropColumn('excerpt');
			$table->dropColumn('user_id');
			$table->dropForeign(['user_id']);

        });
    }
};
