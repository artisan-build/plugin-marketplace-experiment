<x-layouts::app title="Marketplace activity">
    <div class="space-y-6">
        <div>
            <flux:heading size="xl">Marketplace activity</flux:heading>
            <flux:text class="mt-2">Current repository: <code>{{ $sha ?? 'Not built' }}</code></flux:text>
            <flux:text>Fetch means a pack was requested, not proof of installation or skill execution. All times UTC. Tokens are never shown here.</flux:text>
        </div>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-900"><tr>
                    @foreach (['User', 'Token', 'First fetch', 'Last check', 'Last fetch', 'Last fetched SHA', 'Version status', 'IPs', 'User agents'] as $heading)
                        <th class="whitespace-nowrap p-3">{{ $heading }}</th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @foreach ($users as $row)
                        <tr class="border-t border-zinc-200 align-top dark:border-zinc-700">
                            <td class="p-3">{{ $row['user']->email }}</td>
                            <td class="p-3">{{ $row['token_status'] }}</td>
                            <td class="whitespace-nowrap p-3">{{ $row['first_fetch'] ?? '—' }}</td>
                            <td class="whitespace-nowrap p-3">{{ $row['last_check'] ?? '—' }}</td>
                            <td class="whitespace-nowrap p-3">{{ $row['last_fetch']?->created_at ?? '—' }}</td>
                            <td class="p-3 font-mono text-xs">{{ $row['last_fetch']?->served_sha ?? '—' }}</td>
                            <td class="p-3"><flux:badge :color="match ($row['status']) { 'Up to date' => 'green', 'Behind' => 'amber', default => 'zinc' }">{{ $row['status'] }}</flux:badge></td>
                            <td class="p-3">{{ $row['ips'] }}</td>
                            <td class="max-w-xs break-words p-3">{{ $row['agents']->implode('; ') ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <flux:heading size="lg">Latest 200 requests</flux:heading>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-900"><tr>
                    @foreach (['Time (UTC)', 'Owner', 'Token ID', 'Path', 'Query', 'Kind', 'HTTP', 'IP', 'User agent', 'Served SHA'] as $heading)
                        <th class="whitespace-nowrap p-3">{{ $heading }}</th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @forelse ($requests as $entry)
                        <tr class="border-t border-zinc-200 align-top dark:border-zinc-700">
                            @foreach ([$entry->created_at, $entry->email ?? 'Unknown', $entry->token_id ?? '—', $entry->path, $entry->query, $entry->kind, $entry->status, $entry->ip, $entry->user_agent, $entry->served_sha] as $value)
                                <td class="max-w-xs break-words p-3">{{ $value }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="10" class="p-6 text-zinc-500">No requests yet. Add your personal URL in a Git or Claude client to begin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
