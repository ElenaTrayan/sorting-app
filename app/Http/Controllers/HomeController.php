<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Точка входа после логина (RouteServiceProvider::HOME): админа отправляем в админку,
     * всех остальных — в их кабинет.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        return redirect()->route($request->user()->isAdmin() ? 'admin.dashboard' : 'cabinet.home');
    }
}
