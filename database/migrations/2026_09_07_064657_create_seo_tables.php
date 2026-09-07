<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_features', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('short_description', 500);
            $table->text('business_problem');
            $table->text('solution');
            $table->json('workflow')->nullable()->comment('Ordered flow steps supported by the app');
            $table->string('related_module', 100);
            $table->boolean('implemented')->default(true);
            $table->boolean('marketing_enabled')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('seo_industries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description');
            $table->json('workflow')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('seo_locations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->comment('country, region, province, city');
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('seo_locations')->nullOnDelete();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('seo_use_cases', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description');
            $table->json('feature_slugs')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('seo_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 200)->unique();
            $table->string('slug', 220)->unique();
            $table->string('intent', 30)->comment('BUY, PRICE, SOURCE_CODE, SOFTWARE, APPLICATION, FEATURE, INDUSTRY, LOCATION, COMPARISON, CUSTOM, INTEGRATION');
            $table->string('cluster', 30);
            $table->unsignedInteger('priority')->default(0);
            $table->unsignedTinyInteger('commercial_score')->default(0);
            $table->boolean('indexable')->default(true);
            $table->timestamps();
        });

        Schema::create('seo_pages', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('path', 255)->unique()->comment('URL path without leading slash');
            $table->string('intent', 30);
            $table->string('cluster', 30);
            $table->string('keyword', 200);
            $table->string('title', 200);
            $table->string('h1', 200);
            $table->string('description', 500);
            $table->string('canonical', 255)->nullable();
            $table->foreignId('industry_id')->nullable()->constrained('seo_industries')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('seo_locations')->nullOnDelete();
            $table->foreignId('feature_id')->nullable()->constrained('seo_features')->nullOnDelete();
            $table->foreignId('use_case_id')->nullable()->constrained('seo_use_cases')->nullOnDelete();
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, REVIEW, PUBLISHED, NOINDEX, ARCHIVED');
            $table->unsignedTinyInteger('quality_score')->default(0);
            $table->unsignedTinyInteger('uniqueness_score')->default(0);
            $table->unsignedTinyInteger('commercial_score')->default(0);
            $table->boolean('indexable')->default(false);
            $table->string('noindex_reason', 200)->nullable();
            $table->json('content')->nullable()->comment('Deterministic content blocks');
            $table->string('content_hash', 64)->nullable();
            $table->unsignedInteger('content_version')->default(1);
            $table->string('redirect_to', 255)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_quality_check_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'indexable']);
            $table->index('cluster');
        });

        Schema::create('seo_cta_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_page_id')->constrained()->cascadeOnDelete();
            $table->string('cta_position', 50);
            $table->string('cta_type', 50);
            $table->timestamps();
            $table->index('seo_page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_cta_clicks');
        Schema::dropIfExists('seo_pages');
        Schema::dropIfExists('seo_keywords');
        Schema::dropIfExists('seo_use_cases');
        Schema::dropIfExists('seo_locations');
        Schema::dropIfExists('seo_industries');
        Schema::dropIfExists('seo_features');
    }
};
