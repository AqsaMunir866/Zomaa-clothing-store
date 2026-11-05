<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->string('path', 191)->primary();
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->longText('contents');
            $table->timestamps();
        });

        // JPEG/PNG bytes are not valid UTF-8, so a LONGTEXT column would corrupt
        // them. MySQL needs an explicit LONGBLOB; other drivers keep longText.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE media_files MODIFY contents LONGBLOB NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
