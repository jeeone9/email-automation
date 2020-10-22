<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use App\User;
use Carbon\Carbon;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function updatePassword() 
    {
    	User::truncate();
    	User::insert(
    		[
    			'email' => env('ADMIN_USER'),
            	'name' => 'Automation User',
            	'password' => password_hash(env('ADMIN_PASSWORD'), PASSWORD_BCRYPT),
            	'created_at' => Carbon::now(),
            	'updated_at' => Carbon::now()
        	]
        );
    	return redirect('login');
    }
}
