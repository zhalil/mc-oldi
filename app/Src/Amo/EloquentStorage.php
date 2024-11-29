<?php

namespace App\Src\Amo;
use AmoCRM\TokenStorage\TokenStorageInterface;
use App\Models\AmoTokenStorage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webmozart\Assert\Assert;
class EloquentStorage implements TokenStorageInterface
{
    public function save(array $tokens, string $domain)
    {
        Assert::notNull($tokens);
        Assert::notNull($domain);
        try {
           $tokenStorage = AmoTokenStorage::where('domain',$domain)->firstOrFail();
        }catch (ModelNotFoundException $e){
            $tokenStorage = new AmoTokenStorage();
        }
        $tokenStorage->domain = $domain;
        $tokenStorage->tokens = $tokens;
        $tokenStorage->save();
    }

    public function load(string $domain)
    {
        try {
            $storage = AmoTokenStorage::where('domain',$domain)->latest()->firstOrFail();
            return  $storage->tokens;
        }catch (ModelNotFoundException $e) {
            return null;
        }
    }

    public function hasTokens(string $domain) :bool
    {
        $storage = AmoTokenStorage::where('domain',$domain)->first();
        Assert::notNull($storage);
        return true;
    }
}
