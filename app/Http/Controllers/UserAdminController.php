<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Src\Amo\Account\AmoAccountService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Ramsey\Uuid\Uuid;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;

class UserAdminController extends Controller
{
    #[Get('/dashboard', middleware: ['auth', 'verified'])]
    public function index(Request $request,AmoAccountService $amo)
    {

        $isAuth = false;
        $account = [];
        $domain = '';
        $isActive = true;
        $days = 0;
        if (Auth::user()->domain) {
            try {
                $amo->oauthByDomain(Auth::user()->domain);
                $account = $amo->getAccount();
                $isAuth = true;
                $domain = Auth::user()->domain;
                $isActive = Auth::user()->is_active;
                $days = Auth::user()->paid_days;
            } catch (\Exception $e) {
                $isAuth = false;
            }
        }

        return view('oauth.amo',
            [
                'secretUrl'=>'https://'. $request->getHost() . '/secret' ,
                'codeUrl'=>'https://'. $request->getHost() . '/code' ,
                'is_auth' => $isAuth,
                'domain' => $domain,
                'is_active' => $isActive,
                'paid_days' => $days,
            ]);
        return Inertia::render('Dashboard',
            [
                'is_auth' => $isAuth,
                'domain' => $domain,
                'is_active' => $isActive,
                'paid_days' => $days,
            ]);
    }

    #[Get('/dashboard2', middleware: ['web'])]
    public function index2(Request $request, AmoAccountService $amo)
    {
        $domain = $request->query('domain');
        if (!$domain || $domain == '') {
            return redirect('/');
        }
        try {
            $user = User::where('domain', $domain)->firstOrFail();
        } catch (ModelNotFoundException) {
            try {
                $user = User::where('email',$domain . '@365a.kz')->firstOrFail();
            }catch (ModelNotFoundException) {
                $user = new User();
            }
            $user->email = $domain . '@365a.kz';
            $user->name = $domain;
            $user->password = Hash::make($domain);
            $user->domain = $domain;
            $user->save();
        }
        Auth::login($user);
        $domain = '';
        $isActive = true;
        $days = 0;
        if (Auth::user()->domain) {
            try {
                $amo->oauthByDomain(Auth::user()->domain);
                $account = $amo->getAccount();
                $isAuth = true;
                $domain = Auth::user()->domain;
                $isActive = Auth::user()->is_active;
                $days = Auth::user()->paid_days;

            } catch (\Exception $e) {
                $isAuth = false;
            }
        }
        return Inertia::render('Dashboard2',
            [
                'id'=> $user->id,
                'is_auth' => $isAuth,
                'domain' => $domain,
                'is_active' => $isActive,
                'paid_days' => $days,
                'secretUrl'=>'https://'. $request->getHost() . '/secret' ,
                'codeUrl'=>'https://'. $request->getHost() . '/code' ,
            ]);
    }

    #[Get('/change-active-mode/{id}', name: 'change.active.mode', middleware: ['web'])]
    public function changeActiveMode($id)
    {
        try {
            $user = User::where('id',$id)->firstOrFail();
            $user->is_active = !$user->is_active;
            $user->save();
            return back();
        }catch (ModelNotFoundException $e){
            return back();
        }
    }


    public function manual()
    {
        return Inertia::render('Manual');
    }
}
