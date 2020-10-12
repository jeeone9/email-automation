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

                $contract['expiry_date'] = $expiryDate;
                $contract['reminder_date'] = $reminderDate;
                unset($contract['reminder']);
                Contracts::updateOrCreate($contract, ['contract_id' => $contract['contract_id']]);
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
        $intCols = config('constant.int_cols');
        if (count($header) !== count($backendMap)) {
            return [false, 'Invalid CSV columns. Please provide all the columns as specified in sample CSV.'];
        }
        $header = array_map(function ($v) use ($backendMap) {
            return $backendMap[strtolower($v)];
        }, $header);
        $output = [];
        while ($row = fgetcsv($file)) {
            if (count(array_filter(array_map('trim', $row))) !== count($header)) {
                fclose($file);
                return [false, 'Invalid CSV rows. Please provide valid information in each contract.'];
            }
            $temp = array_combine($header, $row);
            foreach ($intCols as $intCol) {
                $temp[$intCol] = (int) $temp[$intCol];
            }
            $output[] = $temp;
        }
        fclose($file);
        return [true, $output];
    }
}
