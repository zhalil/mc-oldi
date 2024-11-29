<?php

namespace App\Jobs;

use App\Src\Amo\Account\AmoAccountService;
use App\Src\Amo\Contact\ContactService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MergeContactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $id,protected string $domain)
    {
    }

    public function handle(): void
    {
        /** @var AmoAccountService $amo */
        $amo = app()->get(AmoAccountService::class);
        /** @var ContactService $contactService */
        $contactService = app()->get(ContactService::class);
        $amo->oauthByDomain($this->domain);
        $amo->getAccount();
        $mergeResult = $contactService->findAndMergeByPhone($this->id);
    }
}
