<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->nullable();
            $table->string('site_tagline')->nullable();
            $table->text('hero_notice')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('whatsapp_phone')->nullable();
            $table->text('address_line')->nullable();
            $table->text('map_embed_url')->nullable();
            $table->string('map_title')->nullable();
            $table->text('office_hours')->nullable();
            $table->text('footer_note')->nullable();
            $table->boolean('under_construction_enabled')->default(false);
            $table->string('under_construction_title')->nullable();
            $table->text('under_construction_message')->nullable();
            $table->json('social_links')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
