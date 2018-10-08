<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMobileOriginatedTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_originated', function (Blueprint $table) {
            $table->increments('id');
            $table->string('msisdn', 35);
            $table->text('message');
            $table->timestamp('received_at');
            $table->string('transaction_id');
            $table->string('driver', 85);
            $table->ipAddress('creator_ip');


            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_originated');
    }
}
