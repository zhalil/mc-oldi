<?php

namespace App\Http\Controllers;

use App\Jobs\MergeContactsJob;
use App\Jobs\MergeLeadsJob;
use App\Models\User;
use App\Src\Amo\Account\AmoAccountService;
use App\Src\Amo\Contact\AmoContact;
use App\Src\Amo\Contact\ContactService;
use App\Src\Amo\Lead\LeadService;
use App\Src\Amo\Lead\Query\FindLeadsByText;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;

class WebhookController extends Controller
{
    private $leadId;
    private $domain;
    private $userId;

    #[Post('/{userId}/webhook')]
    #[Get('/{userId}/webhook')]
    public function index($userId, Request $request, AmoAccountService $amo, ContactService $contactService)
    {
        // die();
        // sleep(1);
        $user = User::where('id', $userId)->firstOrFail();
        if($user->paid_days > 0) {
            if ($user->is_active) {
                $id = $request['contacts']['add'][0]['id'];
                dispatch(new MergeContactsJob($id,$user->domain));
            }
        }
    }


    #[Post('/{userId}/merge')]
    #[Get('/{userId}/merge')]
    public function merge(
        $userId,
        Request $request,
        AmoAccountService $amo,
        LeadService $leadService,
        ContactService $contactService
    ){
        $leadId = $request['leads']['add'][0]['id'];
        $this->leadId = $leadId;
        $this->userId = $userId;
        //$this->handle();
        dispatch(new MergeLeadsJob($userId,$leadId))->delay(10);

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
        if ($user->is_active) {
            $amo->oauthByDomain($user->domain);

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
