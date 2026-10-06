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
        Schema::create('purchase_histories', function (Blueprint $table) {
            $table->id();
        $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
        $table->date('purchase_date')->nullable();
        $table->string('date_text')->nullable();          // original text from Excel
        $table->string('item_name');
        $table->string('normalized_name')->index();
        $table->decimal('qty', 14, 2)->nullable();
        $table->string('unit', 30)->nullable();
        $table->decimal('rate', 14, 2)->nullable();
        $table->decimal('amount', 16, 2)->nullable();
        $table->string('supplier')->nullable();
        $table->string('department')->nullable();
        $table->string('source')->nullable();             // raw "Department / Source" text
        $table->string('row_hash', 32)->unique();         // blocks duplicate imports
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_histories');
    }
};
