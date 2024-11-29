<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Src\Amo\Account\AmoAccountService;
use App\Src\Amo\Oauth2Credential;
use Spatie\RouteAttributes\Attributes\Get;

class TestController extends Controller
{
    #[Get('/test')]
    public function index(AmoAccountService $accountService)
    {
        dd('test');
    }
    #[Get('/test/clear')]
    public function clear()
    {
        $user = User::where('domain','oldiagency')->firstOrFail();
        $user->domain = null;
        $user->save();
    }
}
