<?php

use App\Models\MarketplaceToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/marketplace-test-'.bin2hex(random_bytes(8));
    config(['marketplace.repository' => $this->directory.'/marketplace.git']);
    $this->artisan('marketplace:build')->assertSuccessful();
    $this->user = User::factory()->create();
    $this->token = MarketplaceToken::forUser($this->user);
    $this->base = '/m/'.$this->token->token.'/marketplace.git/';
    $this->sha = trim(File::get($this->directory.'/marketplace.sha'));
});

afterEach(function () {
    File::deleteDirectory($this->directory);
});

it('serves dumb HTTP refs without cookies and attributes checks to the owner', function () {
    $this->withHeader('User-Agent', 'git/poc-test')->get($this->base.'info/refs?service=git-upload-pack')
        ->assertOk()->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertHeaderMissing('Set-Cookie')->assertHeader('Cache-Control', 'no-cache, private');

    $this->assertDatabaseHas('marketplace_requests', [
        'user_id' => $this->user->id, 'token_id' => $this->token->id,
        'path' => 'info/refs', 'query' => 'service=git-upload-pack',
        'kind' => 'check', 'status' => 200, 'served_sha' => $this->sha,
        'user_agent' => 'git/poc-test', 'ip' => '127.0.0.1',
    ]);
    expect(DB::table('marketplace_tokens')->value('token'))->not->toBe($this->token->token);
});

it('rejects revoked and unknown credentials while preserving known ownership', function () {
    $this->token->update(['revoked_at' => now()]);
    $this->get($this->base.'info/refs')->assertNotFound();
    $this->get('/m/unknown/marketplace.git/info/refs')->assertNotFound();
    $this->assertDatabaseHas('marketplace_requests', ['token_id' => $this->token->id, 'user_id' => $this->user->id, 'kind' => 'rejected', 'status' => 404]);
    $this->assertDatabaseHas('marketplace_requests', ['token_id' => null, 'user_id' => null, 'kind' => 'rejected', 'status' => 404]);
});

it('rejects traversal and non-allowlisted files', function (string $path) {
    $this->get($this->base.$path)->assertNotFound();
})->with(['../config', '%2e%2e/config', '%252e%252e/config', 'objects/../../config', 'objects%2f..%2f..%2fconfig', 'config', 'hooks/pre-receive', 'marketplace.sha', 'info/refs/extra']);

it('classifies pack downloads and serves pack and index MIME types', function () {
    $pack = basename(File::glob(config('marketplace.repository').'/objects/pack/*.pack')[0]);
    $this->get($this->base.'objects/pack/'.$pack)->assertOk()->assertHeader('Content-Type', 'application/x-git-packed-objects');
    $this->get($this->base.'objects/pack/'.str_replace('.pack', '.idx', $pack))->assertOk()->assertHeader('Content-Type', 'application/x-git-packed-objects-toc');
    $this->get($this->base.'HEAD')->assertOk();
    $this->assertDatabaseHas('marketplace_requests', ['path' => 'objects/pack/'.$pack, 'kind' => 'fetch']);
    $this->assertDatabaseHas('marketplace_requests', ['path' => 'HEAD', 'kind' => 'other']);
    $this->post($this->base.'git-receive-pack')->assertNotFound();
});

it('builds deterministically and produces a cloneable repository', function () {
    $this->artisan('marketplace:build')->assertSuccessful();
    expect(trim(File::get($this->directory.'/marketplace.sha')))->toBe($this->sha);
    (new Process(['git', 'clone', 'file://'.config('marketplace.repository'), $this->directory.'/clone']))->mustRun();
    $manifest = json_decode(File::get($this->directory.'/clone/.claude-plugin/marketplace.json'), true);
    expect($manifest['name'])->toBe('artisan-poc');
    expect(File::get($this->directory.'/clone/plugins/hello-skill/skills/hello-skill/SKILL.md'))->toContain('skill version 0.1.0.');
});

it('requires login and disables public registration', function () {
    $this->get('/plugins')->assertRedirect('/login');
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});

it('keeps the URL stable until regeneration then revokes only that users token', function () {
    $other = MarketplaceToken::forUser(User::factory()->create());
    $this->actingAs($this->user)->get('/plugins')->assertOk()->assertSee($this->token->token);
    $this->post('/plugins/regenerate')->assertRedirect('/plugins');
    $new = MarketplaceToken::forUser($this->user);
    expect($new->token)->not->toBe($this->token->token);
    $this->get($this->base.'HEAD')->assertNotFound();
    $this->get('/m/'.$new->token.'/marketplace.git/HEAD')->assertOk();
    $this->get('/m/'.$other->token.'/marketplace.git/HEAD')->assertOk();
    $this->get('/plugins')->assertSee($new->token);
});

it('gates the admin report and distinguishes current behind and never fetched users', function () {
    $this->actingAs($this->user)->get('/admin/marketplace')->assertForbidden();
    $this->user->forceFill(['is_admin' => true])->save();
    $this->get($this->base.'info/refs')->assertOk();
    $pack = basename(File::glob(config('marketplace.repository').'/objects/pack/*.pack')[0]);
    $this->get($this->base.'objects/pack/'.$pack)->assertOk();
    $behind = User::factory()->create();
    DB::table('marketplace_requests')->insert([
        'user_id' => $behind->id, 'path' => 'objects/pack/old.pack', 'kind' => 'fetch', 'status' => 200,
        'served_sha' => str_repeat('a', 40), 'created_at' => now(),
    ]);
    User::factory()->create();
    $this->get('/admin/marketplace')->assertOk()->assertSee('Up to date')->assertSee('Behind')->assertSee('Never')->assertSee($this->sha)->assertDontSee($this->token->token);
});
