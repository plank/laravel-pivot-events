<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->string('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->primary(['role_id', 'user_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('value')->nullable();
            $table->integer('value2')->nullable();
            $table->timestamps();
        });

        Schema::create('seller_user', function (Blueprint $table) {
            $table->string('seller_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->primary(['seller_id', 'user_id']);
            $table->foreign('seller_id')->references('id')->on('sellers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('value')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->unsignedBigInteger('tag_id');
            $table->unsignedBigInteger('taggable_id');
            $table->string('taggable_type');
            $table->integer('value')->nullable();
            $table->integer('value2')->nullable();
        });
    }

    public function down(): void
    {
        Schema::drop('taggables');
        Schema::drop('role_user');
        Schema::drop('seller_user');
        Schema::drop('roles');
        Schema::drop('sellers');
        Schema::drop('users');
        Schema::drop('posts');
        Schema::drop('videos');
        Schema::drop('tags');
    }
};
