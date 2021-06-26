<?php
namespace Jalno\Userpanel\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class M_20210608111510_UserTypesNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('userpanel_usertypes_names', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usertype_id');
            $table->foreign('usertype_id')
                    ->references('id')
                    ->on('userpanel_usertypes')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            $table->string('lang', 2);
            $table->string('name', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('userpanel_usertypes_names');
    }
}
