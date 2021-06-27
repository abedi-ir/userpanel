<?php
namespace Jalno\Userpanel\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class M_20210627131503_LogsTags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('userpanel_logs_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('log_id');
            $table->foreign('log_id')
                    ->references('id')
                    ->on('userpanel_logs')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            $table->string("tag");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('userpanel_logs_tags');
    }
}
