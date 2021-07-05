<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTypesPrioritiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('userpanel_usertypes_priorities', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id');
            $table->foreign('parent_id')
                    ->references('id')
                    ->on('userpanel_usertypes')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

            $table->unsignedBigInteger('child_id');
            $table->foreign('child_id')
                    ->references('id')
                    ->on('userpanel_usertypes')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

            $table->primary(["parent_id", "child_id"]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('userpanel_usertypes_priorities');
    }
}
