<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddressableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addressables', function ( Blueprint $table )
        {
            $table->increments('id');
            $table->integer('address_id', false, true);
            $table->integer('addressable_id', false, true);
            $table->string('addressable_type');
            $table->timestamps();

            $table->foreign('address_id')
                  ->references('id')
                  ->on('addresses')
                  ->onDelete('cascade');

            $table->index('addressable_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('addressables');
    }
}
