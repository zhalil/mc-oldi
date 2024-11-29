<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AdminMiddleware;
use App\Models\User;
use Inertia\Inertia;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('admin')]
#[Middleware(['web','auth',AdminMiddleware::class])]
class AdminController extends Controller
{
    #[Get('/', name: 'index.admin')]
    public function index()
    {
        $users = User::all();
        $users = $users->filter(function ($user) {
           if($user->email != 'admin@365a.kz')  {
               return $user;
           }
        });
        return Inertia::render('Admin',[
            'users'=>$users
        ]);
    }

    public function disableUser(string $id)
    {
        $user = User::where('id', $id)->firstOrFail();
        $user->is_active = false;
        $user->save();
        return back();
    }
}
