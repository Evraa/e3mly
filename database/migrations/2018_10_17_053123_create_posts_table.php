<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->mediumtext('body');
            $table->integer('min_cost');
            $table->integer('max_cost');
            $table->string('description_file')->nullable();
            $table->integer('period')->unsigned();
            $table->integer('reports_num')->default(0);
            $table->integer('user_id')->unsigned();
            $table->integer('proposals_num')->default(0);
            $table->string('category');                        
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
