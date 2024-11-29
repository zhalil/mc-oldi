<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Src\Amo\Account\AmoAccountService;
use App\Src\Amo\Oauth2Credential;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;

class AmoController extends Controller
{
    #[Post('/secret')]
    public function index(Request $request,Repository $cache)
    {
        $cache->remember($request->get('client_id'),60,function () use ($request){
            return $request->toArray();
        });
    }

    #[Get('/code', middleware: ['web'])]
    public function code(Request $request, Repository $cache, AmoAccountService $amo)
    {
        $domain = explode('.',$request->get('referer'))[0];
        $clientId = $request->get('client_id');
        $secret = $cache->get($clientId)['client_secret'];
        $redirectUri = url('/code');
        $user = User::where('domain',$domain)->firstOrFail();
        $amo->oauth2(new Oauth2Credential($domain,$clientId,$redirectUri,$secret,$request->get('code')));
        $url = url('/' . $user->id . '/webhook');
        $amo->addContactWebhook($url);
        $account = $amo->getAccount();
        $user->domain = $domain;
        $user->account_id = $account['id'];
        $user->save();

        return redirect("https://mc.365a.kz/dasboard2?domain=$domain");
    }

    #[Post('/authqwe', middleware: ['api'])]
    public function manualAuth(Request $request, AmoAccountService $amo)
    {
        $domain = $request['domain'];
        $clientId = $request['client_id'];
        $secret = $request['client_secret'];
        $redirectUri = $request['redirect_uri'];
        $code = $request['code'];
        $amo->oauth2(new Oauth2Credential($domain,$clientId,$redirectUri,$secret,$code));
//        $url = 'https://365a.kz';
        $amo->addContactWebhook(url('/' . Auth::user()->id . '/webhook'));
        $user = Auth::user();
        $user->domain = $domain;
        $user->save();
        return redirect(url('/dashboard'));
    }
}
