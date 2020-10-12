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
		// QUES: exact match for date can be there, and retry for failed mails
		$contracts = Contracts::where("reminder_status", $status)
						->where("reminder_time", '<', Carbon::now()->toDateTimeString())
						->where('expiry_time', '>', Carbon::now()->toDateTimeString())
						->limit(10)
						->get();

		if (!empty($contracts)) {
			$contracts = $contracts->toArray();
		} else {
			return;
		}

		$holdContractIds = array_column($contracts, 'contract_id');
		Contracts::whereIn('contract_id', $holdContractIds)->update(["reminder_status" => 'sending']);

		foreach ($contracts as $contract) {
			$contract['cc_emails'] = array_map('trim', explode(',', $contract['email_resposible']));
			try {
				self::send($contract);
				Contracts::where('contract_id', $contract['contract_id'])->update(["reminder_status" => 'sent']);
			} catch (\Exception $e) {
				Contracts::where('contract_id', $contract['contract_id'])->update(["reminder_status" => $failed]);
				Log::error($e->getMessage());
			}
		}	

		return;
	}

	private static function send($mailData) 
	{
		$toEmail = $mailData['seller_email'];
		$toName = $mailData['seller_name'];
		$ccEmails = $mailData['cc_emails'];
		$details = $mailData['details'];
		$subject = "Test Subject";

		Mail::send('email', ['toName' => $toName, 'details' => $details], function ($message) use ($toEmail, $toName, $subject, $ccEmails, $details){
		    $message->from(env('MAIL_FROM_EMAIL'), env('MAIL_FROM_NAME'));
		    $message->to($toEmails, $toName);
		    foreach ($ccEmails as $ccMail) {
		    	$message->cc($ccMail);
		    }
		    $message->subject($subject);
		});
	}
}