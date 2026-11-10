<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceToken;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MarketplaceController extends Controller
{
    public function show(Request $request): Response
    {
        $token = MarketplaceToken::forUser($request->user());

        return response()->view('marketplace.plugins', [
            'url' => url('/m/'.$token->token.'/marketplace.git'),
        ])->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function regenerate(Request $request): RedirectResponse
    {
        MarketplaceToken::forUser($request->user(), regenerate: true);

        return to_route('plugins')->with('status', 'New URL issued. The old URL now returns 404; update your client to use this one.');
    }

    public function admin(Request $request): Response
    {
        abort_unless($request->user()->is_admin, 403);
        $repo = realpath(config('marketplace.repository'));
        $sha = $repo && is_file($repo.'/marketplace.sha') ? trim(File::get($repo.'/marketplace.sha')) : null;
        $users = User::orderBy('email')->get()->map(function (User $user) use ($sha) {
            $requests = DB::table('marketplace_requests')->where('user_id', $user->id)->where('status', 200);
            $lastFetch = (clone $requests)->where('kind', 'fetch')->orderByDesc('id')->first();
            $token = MarketplaceToken::where('user_id', $user->id)->latest('id')->first();

            return [
                'user' => $user,
                'token_status' => ! $token ? 'Not issued' : ($token->revoked_at ? 'Revoked' : 'Active'),
                'first_fetch' => (clone $requests)->where('kind', 'fetch')->min('created_at'),
                'last_check' => (clone $requests)->where('kind', 'check')->max('created_at'),
                'last_fetch' => $lastFetch,
                'status' => ! $lastFetch ? 'Never' : ($sha && $lastFetch->served_sha === $sha ? 'Up to date' : 'Behind'),
                'ips' => (clone $requests)->distinct()->count('ip'),
                'agents' => (clone $requests)->distinct()->pluck('user_agent'),
            ];
        });

        return response()->view('marketplace.admin', [
            'sha' => $sha,
            'users' => $users,
            'requests' => DB::table('marketplace_requests')->leftJoin('users', 'users.id', '=', 'marketplace_requests.user_id')
                ->select('marketplace_requests.*', 'users.email')->orderByDesc('marketplace_requests.id')->limit(200)->get(),
        ])->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }
}
