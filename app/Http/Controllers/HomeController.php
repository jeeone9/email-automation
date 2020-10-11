<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contracts;
use Carbon\Carbon;
use App\User;
use Log;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        return view('home');
    }

    public function importCsv(Request $request)
    {
        $backendMap = [
            "﻿Contract Number" => "contract_id",
            "Expiration Date" => "expiry_date",
            "Salesperson" => "sales_person",
            "Email of Salesperson" => "sales_person_email",
            "email responsible" => "email_resposible",
            "Customer" => "customer_name",
            "Details" => "details",
            "Customer number" => "customer_number",
            "Address" => "address",
            "City" => "city",
            "Postal Code" => "postal_Code",
            "Telephone" => "telephone",
            "Reminder" => "reminder",
            "Reminder Days" => "reminder"
        ];

        $intCols = ['contract_id', 'customer_number', 'reminder'];

        $this->validate($request, 
            ['csv_file'   => 'required|mimes:csv,txt']
        );

        $path = $request->file('csv_file')->getRealPath();
        $data = array_map('str_getcsv', file($path));

        $contracts = [];

        if (count($data) > 1) {
            $array_keys = array_values($data[0]);
            $array_keys = array_map(function($key) use ($backendMap) {
                return $backendMap[$key];
            }, $array_keys);

            foreach (array_slice($data, 1) as $key => $value) {
                $tmp = [];
                if (empty($value) || empty(array_filter($value))) {
                    continue;
                }
                foreach ($array_keys as $k1 => $csvKey) {
                    $tmp[$csvKey] = $value[$k1];
                }
                if (empty($keyName)) {
                    $contracts[] = $tmp;
                } else {
                    if (in_array($tmp[$keyName], $intCols)) {
                        $contracts[$tmp[$keyName]] = (int) $tmp;
                    } else {
                        $contracts[$tmp[$keyName]] = $tmp;
                    }
                }
            }
        }

        $errorContracts = [];
        foreach ($contracts as $contract) {
            try {
                $expiryDate = Carbon::createFromFormat('d/m/y',$contract['expiry_date'])->toDateTimeString();
                $reminderDate = Carbon::createFromFormat('d/m/y',$contract['expiry_date'])->subDays(!empty($contract['reminder']) && is_numeric($contract['reminder']) ? (int) $contract['reminder'] : (int) env('REMINDER_DAYS'))->toDateTimeString();

                $contract['expiry_date'] = $expiryDate;
                $contract['reminder_date'] = $reminderDate;
                unset($contract['reminder']);
                Contracts::updateOrCreate($contract, ['contract_id' => $contract['contract_id']]);
            } catch (\Exception $e){
                $errorContracts[] = $contract['contract_id'];
                Log::error($e->getMessage());
            }    
        }

        return response()->json(['msg' => !empty($errorContracts) ? "Error in importing contract Ids ".implode(',', $errorContracts) : 'CSV Import Succesful']);
    }

    public function getContracts()
    {
        return response()->json(['data' => Contracts::all()->toArray()]);
    }
}
