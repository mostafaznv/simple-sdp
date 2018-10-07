<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMobileTerminatedTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_terminated', function (Blueprint $table) {
            $table->increments('id');
            $table->string('msisdn', 35);
            $table->string('delivery_status')->nullable();
            $table->boolean('subscription_status')->default(0);
            $table->tinyInteger('type')->default(0);
            $table->text('message');
            $table->string('message_id', 255);
            $table->string('transaction_id')->nullable();
            $table->ipAddress('creator_ip');
            $table->ipAddress('updater_ip')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_terminated');
    }
}
