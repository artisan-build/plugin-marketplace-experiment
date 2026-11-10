<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class MarketplaceGitController extends Controller
{
    public function __invoke(Request $request, string $token, string $path = ''): Response
    {
        $owner = MarketplaceToken::where('token_hash', hash('sha256', $token))->first();
        // Resolve the release once: a concurrent build cannot mix refs and metadata.
        $repo = realpath(config('marketplace.repository'));
        $sha = $repo && is_file($repo.'/marketplace.sha') ? trim(File::get($repo.'/marketplace.sha')) : null;
        $allowed = preg_match('~\A(?:HEAD|info/refs|objects/info/packs|objects/pack/pack-[a-f0-9]{40}\.(?:pack|idx)|objects/[a-f0-9]{2}/[a-f0-9]{38})\z~D', $path) === 1;
        $file = $repo ? realpath($repo.'/'.$path) : false;
        $valid = $owner && ! $owner->revoked_at;
        $serve = $valid && $request->isMethod('GET') && $allowed && $file && str_starts_with($file, $repo.'/') && is_file($file);
        $kind = ! $valid ? 'rejected' : match (true) {
            $path === 'info/refs' => 'check',
            str_ends_with($path, '.pack') => 'fetch',
            default => 'other',
        };

        DB::table('marketplace_requests')->insert([
            'token_id' => $owner?->id,
            'user_id' => $owner?->user_id,
            // Never record the credential-bearing URL, even for unknown tokens.
            'path' => ltrim(explode('/marketplace.git', $request->getPathInfo(), 2)[1] ?? '', '/'),
            'query' => $request->getQueryString(),
            'kind' => $kind,
            'status' => $serve ? 200 : 404,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'served_sha' => $sha,
            'created_at' => now(),
        ]);

        if (! $serve) {
            return response('Not found', 404)->header('Cache-Control', 'no-store');
        }

        $type = match (true) {
            str_ends_with($path, '.pack') => 'application/x-git-packed-objects',
            str_ends_with($path, '.idx') => 'application/x-git-packed-objects-toc',
            preg_match('~^objects/[a-f0-9]{2}/~', $path) === 1 => 'application/x-git-loose-object',
            default => 'text/plain',
        };

        return response()->file($file, [
            'Content-Type' => $type,
            'Cache-Control' => 'no-cache, private',
            'X-Content-Type-Options' => 'nosniff',
        ])->setPrivate();
    }
}
