<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateContractstable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->bigInteger('contract_id')->unique();
            $table->string('sales_person', 100);
            $table->string('sales_person_email', 100);
            $table->string('email_resposible', 200);
            $table->string('details', 1000);
            $table->string('customer_name', 100);
            $table->bigInteger('customer_number');
            $table->string('address', 500);
            $table->string('city', 100);
            $table->string('postal_code', 50);
            $table->string('telephone', 50);
            $table->timestamp('expiry_date');
            $table->timestamp('reminder_date');
            $table->enum('reminder_status', ['not_triggered', 'sending', 'sent', 'retry' ,'failed'])->default('not_triggered');
            $table->timestamps();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index('reminder_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop('contracts');
    }
}
