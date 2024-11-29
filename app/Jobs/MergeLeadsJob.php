<?php

namespace App\Jobs;

use App\Models\User;
use App\Src\Amo\Account\AmoAccountService;
use App\Src\Amo\Contact\ContactService;
use App\Src\Amo\Lead\LeadService;
use App\Src\Amo\Lead\Query\FindLeadsByText;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MergeLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private string|int $userId,private $leadId)
    {
    }

    public function handle()
    {
        $userId = $this->userId;
        /** @var AmoAccountService $amo */
        $amo = app()->get(AmoAccountService::class);
        /** @var LeadService $leadService */
        $leadService = app()->get(LeadService::class);
        $contactService = app()->get(ContactService::class);
        $user = User::where('id', $userId)->firstOrFail();
        if ($user->is_active && $user->paid_days > 0) {
            $amo->oauthByDomain($user->domain);
            $amo->getAccount();
            $leadId = $this->leadId;
            $lead = $leadService->getLead($leadId);
            if (!isset($lead->contacts['id'])) {
                return response('no find contacts', 404);
            }

            $phones = [];
            $leads = [];
            foreach ($lead->contacts['id'] as $id) {
                $contact = $contactService->getContactById($id);
                $phones = $contact->getPhones();
            }
            $findedPhone = null;
            foreach ($phones as $phone) {

                $pipeline = $lead->pipeline['id'];
                $text = $phone->withoutCode;

                try {
                    $leads = $leadService->find(new FindLeadsByText($pipeline, $text));
                    if (count($leads) === 1 && $leads[0]->id === $lead->id) {
                        $findedPhone = $phone;
                        continue;
                    }
                    break;
                } catch (\Exception $e) {

                }
            }
            foreach ($leads as $key => $l) {
                if ($l->id === $lead->id) {
                    unset($leads[$key]);
                }
            }
            $merge = $leadService->merge($lead->id, $leads, $lead->pipeline['id']);
        }
        return;
    }
}
