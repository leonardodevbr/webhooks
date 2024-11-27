<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    /**
     * Tela de registro de conta.
     */
    public function showRegisterForm()
    {
        return view('account.register');
    }

    /**
     * Tela de login.
     */
    public function showLoginForm()
    {
        return view('account.login');
    }

    /**
     * Realizar registro de conta.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:accounts,slug',
            'email' => 'required|email|unique:accounts,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $account = Account::create([
            'name' => $request->input('name'),
            'slug' => $request->input('slug'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        Auth::login($account);

        return redirect()->route('webhook.create-url')->with('success', 'Conta criada com sucesso!');
    }

    /**
     * Realizar login.
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            return redirect()->route('webhook.create-url');
        }

        return back()->withErrors(['email' => 'Credenciais inválidas']);
    }

    /**
     * Realizar logout.
     */
    public function logout()
    {
        Auth::logout();
        return redirect()->route('webhook.create-url');
    }
}
