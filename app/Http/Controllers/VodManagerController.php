<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\VodController;
use App\Models\AccessCode;
use App\Models\Channel;
use App\Models\VodFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VodManagerController extends Controller
{
    private const SESSION_KEY = 'vod_manager_channel';

    // ── Auth ──────────────────────────────────────────────────────────────────

    public function showLogin(Channel $channel): View
    {
        return view('vod-manager.login', compact('channel'));
    }

    public function login(Request $request, Channel $channel): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);

        $code = AccessCode::where('code', $request->input('code'))
            ->where('type', AccessCode::TYPE_VOD_MANAGER)
            ->where('channel_id', $channel->id)
            ->where('is_active', true)
            ->first();

        if (!$code || !$code->isValid()) {
            return back()->withErrors(['code' => 'Invalid or expired access code for this channel.']);
        }

        $request->session()->put(self::SESSION_KEY . '.' . $channel->id, [
            'code_id'    => $code->id,
            'expires_at' => now()->addDays($code->duration_days)->timestamp,
        ]);

        $code->incrementUsage();

        return redirect()->route('vod-manager.index', $channel);
    }

    public function logout(Request $request, Channel $channel): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY . '.' . $channel->id);

        return redirect()->route('vod-manager.login', $channel)->with('success', 'Logged out.');
    }

    // ── VOD Management ────────────────────────────────────────────────────────

    public function index(Request $request, Channel $channel): View|RedirectResponse
    {
        if (!$this->isAuthorised($request, $channel)) {
            return redirect()->route('vod-manager.login', $channel);
        }

        $files     = $channel->vodFiles()->orderBy('sort_order')->orderBy('created_at')->get();
        $usedBytes = $channel->vodFiles()->where('source_type', 'upload')->sum('size_bytes');

        return view('vod-manager.index', compact('channel', 'files', 'usedBytes'));
    }

    public function store(Request $request, Channel $channel): RedirectResponse
    {
        if (!$this->isAuthorised($request, $channel)) {
            return redirect()->route('vod-manager.login', $channel);
        }

        return app(VodController::class)->handleUpload($request, $channel);
    }

    public function storeYoutube(Request $request, Channel $channel): RedirectResponse
    {
        if (!$this->isAuthorised($request, $channel)) {
            return redirect()->route('vod-manager.login', $channel);
        }

        return app(VodController::class)->handleYoutube($request, $channel);
    }

    public function destroy(Request $request, Channel $channel, VodFile $vodFile): RedirectResponse
    {
        if (!$this->isAuthorised($request, $channel)) {
            return redirect()->route('vod-manager.login', $channel);
        }

        return app(VodController::class)->handleDestroy($channel, $vodFile);
    }

    public function reorder(Request $request, Channel $channel): RedirectResponse
    {
        if (!$this->isAuthorised($request, $channel)) {
            return redirect()->route('vod-manager.login', $channel);
        }

        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        foreach ($request->input('order') as $position => $id) {
            $channel->vodFiles()->where('id', $id)->update(['sort_order' => $position]);
        }

        app(VodController::class)->generatePlaylist($channel);

        return back()->with('success', 'Order saved.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isAuthorised(Request $request, Channel $channel): bool
    {
        $session = $request->session()->get(self::SESSION_KEY . '.' . $channel->id);

        if (!$session) {
            return false;
        }

        if (now()->timestamp > $session['expires_at']) {
            $request->session()->forget(self::SESSION_KEY . '.' . $channel->id);
            return false;
        }

        // Verify the code is still active in DB
        return AccessCode::where('id', $session['code_id'])
            ->where('is_active', true)
            ->exists();
    }
}
