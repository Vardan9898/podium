<?php

declare(strict_types=1);

use App\Enums\ProposalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('status')->default(ProposalStatus::Pending->value)->index();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->timestamps();

            // The list is always ordered by created_at desc, id desc, optionally filtered by
            // status (everyone) or author (speakers). Postgres can scan these backwards.
            $table->index(['created_at', 'id']);
            $table->index(['status', 'created_at', 'id']);
            $table->index(['user_id', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
