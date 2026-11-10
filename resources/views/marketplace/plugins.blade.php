<x-layouts::app title="Your marketplace">
    <div class="mx-auto max-w-3xl space-y-8">
        <div>
            <flux:heading size="xl">Your plugin marketplace</flux:heading>
            <flux:text class="mt-2">Artisan PoC · One skill, your own private access URL.</flux:text>
        </div>
        @if (session('status'))
            <flux:callout>{{ session('status') }}</flux:callout>
        @endif
        <section class="space-y-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">Personal Git URL</flux:heading>
            <flux:text>Treat this URL like a password. Anyone with it can fetch the plugin as you.</flux:text>
            <div x-data="{ copied: false }" class="space-y-3">
                <label for="marketplace-url" class="sr-only">Personal marketplace URL</label>
                <input id="marketplace-url" readonly value="{{ $url }}" class="w-full rounded-lg border border-zinc-300 bg-zinc-50 p-3 font-mono text-sm dark:border-zinc-600 dark:bg-zinc-900" />
                <flux:button x-on:click="navigator.clipboard.writeText(document.getElementById('marketplace-url').value).then(() => copied = true)"><span x-text="copied ? 'Copied' : 'Copy URL'">Copy URL</span></flux:button>
            </div>
        </section>
        <section class="space-y-3">
            <flux:heading size="lg">Claude Code</flux:heading>
            <flux:text>Run these commands in Claude Code, then say <strong>hello marketplace</strong>.</flux:text>
            <pre class="overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>/plugin marketplace add {{ $url }}
/plugin install hello-skill@artisan-poc</code></pre>
            <flux:text>To check for a new version: <code>/plugin marketplace update artisan-poc</code>. Enable auto-update in the marketplace settings and restart to test automatic updates.</flux:text>
        </section>
        <section class="space-y-3">
            <flux:heading size="lg">Claude Desktop · Mac &amp; Windows</flux:heading>
            <flux:text>Open <strong>Customize → Plugins → Add → Add marketplace → Add from a repository</strong>. Paste your URL, install <strong>hello-skill</strong>, then say <strong>hello marketplace</strong> in chat and in Cowork.</flux:text>
            <flux:text>This is an experiment. Record your Desktop version and the exact error if the URL is rejected. For updates, use the refresh/update control if available; otherwise record what happens over 24 hours.</flux:text>
        </section>
        <section class="space-y-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <flux:heading size="lg">Replace your URL</flux:heading>
            <flux:text>Regenerating immediately revokes the old URL and stops its future downloads. It cannot remove a skill already cached on a device. Re-add the marketplace with your new URL.</flux:text>
            <form method="POST" action="{{ route('plugins.regenerate') }}">
                @csrf
                <flux:button type="submit" variant="danger">Regenerate URL</flux:button>
            </form>
        </section>
    </div>
</x-layouts::app>
