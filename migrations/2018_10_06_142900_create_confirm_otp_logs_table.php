<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfirmOtpLogsTable extends Migration
{
    public function up()
    {
        Schema::create('confirm_otp_logs', function(Blueprint $table) {
            $table->increments('id');
            $table->string('msisdn', 35);
            $table->tinyInteger('type');
            $table->string('version_name', 191)->nullable();
            $table->string('driver', 85);
            $table->ipAddress('ip')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('confirm_otp_logs');
    }
}
