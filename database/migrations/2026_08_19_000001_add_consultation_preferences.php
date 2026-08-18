<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('institution', 190)->nullable()->after('phone');
        });

        Schema::create('appointment_meeting_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();
        DB::table('appointment_meeting_methods')->insert([
            ['name' => 'Google Meet', 'description' => 'Çevrim içi görüntülü görüşme', 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Telefon Görüşmesi', 'description' => 'Kayıtlı telefon numaranız üzerinden görüşme', 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yüz Yüze Görüşme', 'description' => 'Uygunluk onayından sonra ofiste görüşme', 'is_active' => true, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('meeting_method_id')
                ->nullable()
                ->after('member_id')
                ->constrained('appointment_meeting_methods')
                ->restrictOnDelete();
            $table->text('notes_member')->nullable()->after('notes_internal');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('meeting_method_id');
            $table->dropColumn('notes_member');
        });

        Schema::dropIfExists('appointment_meeting_methods');

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('institution');
        });
    }
};
