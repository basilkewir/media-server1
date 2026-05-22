<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminApiTokenController extends Controller
{
    public function index(): View
    {
        $tokens = ApiToken::latest()->paginate(20);
        return view('admin.api-tokens.index', compact('tokens'));
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'name'       => 'required|string|max:255',
            'expires_in' => 'nullable|integer|min:1|max:3650',
        ]);

        $plain = Str::random(40);

        $token = ApiToken::create([
            'name'       => $v['name'],
            'token'      => hash('sha256', $plain),
            'abilities'  => ['*'],
            'expires_at' => isset($v['expires_in']) ? now()->addDays($v['expires_in']) : null,
            'is_active'  => true,
        ]);

        return redirect()->route('admin.api-tokens.index')
            ->with('success', "Token \"{$token->name}\" created.")
            ->with('new_token', $plain);
    }

    public function destroy(ApiToken $token): RedirectResponse
    {
        $token->update(['is_active' => false]);
        return redirect()->route('admin.api-tokens.index')->with('success', "Token \"{$token->name}\" revoked.");
    }
}
