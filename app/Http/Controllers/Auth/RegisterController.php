<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\StorageQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(
        protected StorageQuotaService $quotaService,
    ) {}

    public function showRegistrationForm(): View
    {
        $plans = Plan::where('is_active', true)
            ->where('tier', Plan::TIER_FREE)
            ->orderBy('sort_order')
            ->get();

        return view('auth.register', compact('plans'));
    }

    public function showPricing(): View
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price_cents')
            ->get();

        $freePlans  = $plans->where('tier', Plan::TIER_FREE);
        $paidPlans  = $plans->where('tier', '!=', Plan::TIER_FREE);

        return view('auth.pricing', compact('freePlans', 'paidPlans'));
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'plan_id'  => 'required|exists:plans,id',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'manager',
            'is_active' => true,
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        Subscription::create([
            'user_id'      => $user->id,
            'plan_id'      => $plan->id,
            'starts_at'    => now(),
            'ends_at'      => $plan->tier === Plan::TIER_FREE ? null : now()->addMonth(),
            'is_active'    => true,
            'payment_status' => $plan->price_cents === 0 ? 'none' : 'pending',
        ]);

        Auth::login($user);

        return redirect()->route('client.dashboard')
            ->with('success', 'Welcome! Your account has been created.');
    }

    public function profile(): View
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription();
        $plan = $subscription?->plan;
        $quotaInfo = $this->quotaService->getQuotaInfo($user);

        return view('auth.profile', compact('user', 'subscription', 'plan', 'quotaInfo'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profile updated.');
    }
}
