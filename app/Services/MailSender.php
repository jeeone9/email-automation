<?php

namespace App\Services;

use Log;
use Carbon\Carbon;
use App\Models\Contracts;
use Illuminate\Support\Facades\Mail;

class MailSender
{
    public static function triggerMails($status = 'not_triggered', $failed = 'retry')
    {
        $remindersArr = [
            'first' => [
                'date_column' => 'reminder_date', 
                'status_column' => 'reminder_status'
            ],
            'second' => [
                'date_column' => 'reminder_two_date', 
                'status_column' => 'reminder_two_status'
            ]
        ];

        foreach ($remindersArr as $type => $keysArr) {
            $contracts = Contracts::where($keysArr["status_column"], $status)
                            ->where($keysArr["date_column"], '<', Carbon::now()->toDateTimeString())
                            ->where('expiry_date', '>', Carbon::now()->toDateTimeString())
                            ->limit(10)
                            ->get();
            if (empty($contracts)) {
                continue;
            }
            $contracts = $contracts->toArray();

            $holdContractIds = array_column($contracts, 'contract_id');
            Contracts::whereIn('contract_id', $holdContractIds)->update([$keysArr["status_column"] => 'sending']);

            foreach ($contracts as $contract) {
                $contract['cc_emails'] = array_map('trim', explode(',', $contract['email_resposible']));
                try {
                    self::send($contract, $type === 'second');
                    Contracts::where('contract_id', $contract['contract_id'])->update([$keysArr["status_column"] => 'sent']);
                } catch (\Exception $e) {
                    Contracts::where('contract_id', $contract['contract_id'])->update([$keysArr["status_column"] => $failed]);
                    Log::error($e->getMessage());
                    if ($failed === 'failed') {
                        self::sendErrorMail($contract['contract_id'], $e->getMessage());
                    }
                }
            }
        }    

        return;
    }

    private static function sendErrorMail($contractId, $msg)
    {
        $toEmail = explode(';', env('ERROR_MAIL_TO'));

        Mail::send('error', ['contract_id' => $contractId, 'msg' => $msg], function ($message) use ($toEmail) {
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $message->to($toEmail);
            $message->subject("Automation Error Debugger");
        });
    }

    private static function send($mailData, $second = false)
    {
        $toEmail = $mailData['sales_person_email'];
        $toName = $mailData['sales_person'];
        $ccEmails = $mailData['cc_emails'];
        $details = $mailData['details'];
        $contractId = $mailData['contract_id'];
        $expiryDate = Carbon::parse($mailData['expiry_date'])->format('d M Y');
        $subject = "Contrato de {$contractId} vence el {$expiryDate}";
        if ($second) {
            $subject = "RECORDATORIO ".$subject;
        }
        $templateData = [
            'to_name' => $toName,
            'contract_id' => $contractId,
            'expiry_date' => $expiryDate,
            'details' => $details,
        ];
        Mail::send('email', $templateData, function ($message) use ($toEmail, $toName, $subject, $ccEmails) {
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $message->to($toEmail, $toName);
            $ccEmails = array_diff($ccEmails, [$toEmail]);
            foreach ($ccEmails as $ccMail) {
                $message->cc($ccMail);
            }
            $message->subject($subject);
        });
    }
}
