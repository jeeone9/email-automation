<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contracts;
use Carbon\Carbon;
use App\User;
use Log;
use Illuminate\Support\Arr;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
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
        $this->validate(
            $request,
            ['csv_file'   => 'required|mimes:csv,txt']
        );
        $backendMap = config('constant.csv_headers');
        $intCols = config('constant.int_cols');

        $path = $request->file('csv_file')->getRealPath();
        $ret = $this->csvValidation($path);
        if (!$ret[0]) {
            return response()->json(['msg' => $ret[1]], 422);
        }
        $contracts = $ret[1];

        $errorContracts = [];
        foreach ($contracts as $contract) {
            try {
                $expiryDate = Carbon::createFromFormat('d/m/y', $contract['expiry_date'])->toDateTimeString();
                $reminderDate = Carbon::createFromFormat('d/m/y', $contract['expiry_date'])->subDays(!empty($contract['reminder']) && is_numeric($contract['reminder']) ? (int) $contract['reminder'] : (int) env('REMINDER_DAYS'))->toDateTimeString();
                $reminderTwoDate = Carbon::createFromFormat('d/m/y', $contract['expiry_date'])->subDays(!empty($contract['reminder_two']) && is_numeric($contract['reminder_two']) ? (int) $contract['reminder_two'] : (int) env('REMINDER_TWO_DAYS'))->toDateTimeString();

                $contract['expiry_date'] = $expiryDate;
                $contract['reminder_date'] = $reminderDate;
                $contract['reminder_two_date'] = $reminderTwoDate;
                $contract = Arr::only($contract, ['contract_id','sales_person','sales_person_email','email_resposible','details','subject','customer_name','customer_number','address','city','postal_code','telephone','expiry_date','reminder_date','reminder_status','reminder_two_date','reminder_two_status']);

                if (!empty(Contracts::where('contract_id', $contract['contract_id'])->first())) {
                    Contracts::where(['contract_id' => $contract['contract_id']])->update($contract);
                } else {
                    Contracts::insert($contract);
                }
            } catch (\Exception $e) {
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

    protected function csvValidation($path)
    {
        $file = fopen($path, 'r');
        $header = fgetcsv($file);
        $backendMap = config('constant.csv_headers');
        $mandatoryFields = config('constant.mandatory_fields');
        $intCols = config('constant.int_cols');
        $header = array_map(function ($v) use ($backendMap) {
            return isset($backendMap[strtolower($v)]) ? $backendMap[strtolower($v)] : $v;
        }, $header);

        if (!empty(array_diff(array_values($backendMap), $header))) {
            return [false, 'Invalid CSV columns. Please provide all the columns as specified in sample CSV.'];
        }

        $output = [];
        while ($row = fgetcsv($file)) {
            $temp = array_combine($header, $row);
            foreach ($mandatoryFields as $mandatoryField) {
                if (empty($temp[$mandatoryField])) {
                    fclose($file);
                    return [false, 'Invalid CSV rows. Please provide valid information in each contract.'];
                }
            }
            foreach ($intCols as $intCol) {
                $temp[$intCol] = !empty($temp[$intCol]) && is_numeric($temp[$intCol]) ? (int) $temp[$intCol] : $temp[$intCol];
            }
            $output[] = $temp;
        }
        fclose($file);
        return [true, $output];
    }
}
