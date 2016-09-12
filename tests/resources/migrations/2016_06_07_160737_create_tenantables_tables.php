<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTenantablesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tenantables', function ( Blueprint $table )
        {
            $table->increments('id');
            $table->integer('tenant_id', false, true);
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade');
            $table->integer('tenantable_id', false, true);
            $table->index('tenantable_id');
            $table->string('tenantable_type');
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
        Schema::drop('tenantables');
    }
}
