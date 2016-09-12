<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function ( Blueprint $table )
        {
            $table->increments('id');
            $table->string('house_number');
            $table->string('street');
            $table->string('line_1');
            $table->string('line_2');
            $table->string('city');
            $table->string('county');
            $table->string('postcode');
            $table->string('country');
            $table->softDeletes();
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
        Schema::drop('addresses');
    }
}
