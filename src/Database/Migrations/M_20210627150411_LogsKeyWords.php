<?php
namespace Jalno\Userpanel\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class M_20210627150411_LogsKeyWords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('userpanel_logs_keywords', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('log_id');
            $table->foreign('log_id')
                    ->references('id')
                    ->on('userpanel_logs')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            $table->string("name");
            $table->text("value");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('userpanel_logs_keywords');
    }
}
