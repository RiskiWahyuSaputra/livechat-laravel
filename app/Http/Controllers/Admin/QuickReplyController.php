<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuickReply;
use Illuminate\Http\Request;
use RuntimeException;

class QuickReplyController extends Controller
{
    public function index()
    {
        $replies = QuickReply::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.quick-replies.index', compact('replies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $commandInput = $request->input('command', '');

        if (trim($commandInput) !== '') {
            $request->validate([
                'command' => [
                    'required',
                    'regex:/^\/[^\s]+$/',
                    'max:50',
                    'unique:quick_replies,command',
                ],
            ], [
                'command.regex'  => 'Command harus diawali dengan karakter `/` dan tidak boleh mengandung spasi.',
                'command.max'    => 'Command maksimal 50 karakter.',
                'command.unique' => 'Command sudah digunakan oleh balasan cepat lain.',
            ]);

            $command = $commandInput;
        } else {
            $command = $this->resolveCommand($request->input('title'));
        }

        QuickReply::create([
            'title'   => $request->input('title'),
            'command' => $command,
            'content' => $request->input('content'),
        ]);

        return redirect()->route('admin.quick-replies.index')->with('success', 'Balasan cepat berhasil ditambahkan.');
    }

    public function update(Request $request, QuickReply $quickReply)
    {
        $rules = [
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ];

        $messages = [];

        // Only validate command format if it is provided and not blank
        if (filled($request->input('command'))) {
            $rules['command'] = [
                'string',
                'regex:/^\/[^\s]+$/',
                'max:50',
                'unique:quick_replies,command,' . $quickReply->id,
            ];

            $messages = [
                'command.regex'  => 'Command harus diawali dengan karakter `/` dan tidak boleh mengandung spasi.',
                'command.max'    => 'Command maksimal 50 karakter.',
                'command.unique' => 'Command sudah digunakan oleh balasan cepat lain.',
            ];
        }

        $request->validate($rules, $messages);

        // Resolve command: use provided value or auto-generate from title
        $command = filled($request->input('command'))
            ? $request->input('command')
            : $this->resolveCommand($request->input('title'), $quickReply->id);

        $quickReply->update([
            'title'   => $request->input('title'),
            'command' => $command,
            'content' => $request->input('content'),
        ]);

        return redirect()->route('admin.quick-replies.index')->with('success', 'Balasan cepat berhasil diperbarui.');
    }

    public function destroy(QuickReply $quickReply)
    {
        $quickReply->delete();
        return redirect()->route('admin.quick-replies.index')->with('success', 'Balasan cepat berhasil dihapus.');
    }

    /**
     * Resolve a unique command string from the given title.
     *
     * Applies the following transformations:
     *   1. Lowercase
     *   2. Replace spaces with underscores
     *   3. Remove characters other than a-z, 0-9, _
     *   4. Truncate to 49 characters (leaving room for the '/' prefix)
     *   5. Prepend '/'
     *
     * If the resulting candidate is already taken (excluding $excludeId),
     * appends suffix _1, _2, … up to a maximum of 100 attempts.
     * The total length of the returned command never exceeds 50 characters.
     *
     * @param  string   $title     The title to derive the command from.
     * @param  int|null $excludeId An existing record ID to exclude from the uniqueness check (for updates).
     * @return string              A unique command string starting with '/'.
     *
     * @throws RuntimeException If no unique command can be found within 100 attempts.
     */
    private function resolveCommand(string $title, ?int $excludeId = null): string
    {
        // Step 1-4: build the base slug (max 49 chars, no '/' yet)
        $base = strtolower($title);
        $base = str_replace(' ', '_', $base);
        $base = preg_replace('/[^a-z0-9_]/', '', $base);
        $base = substr($base, 0, 49);

        // Step 5: initial candidate
        $candidate = '/' . $base;

        $maxAttempts = 100;

        for ($i = 1; $i <= $maxAttempts; $i++) {
            // Check uniqueness
            $query = QuickReply::where('command', $candidate);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                return $candidate;
            }

            // Build suffix and ensure total length ≤ 50
            $suffix = '_' . $i;                    // e.g. "_1", "_10"
            $suffixLen = strlen($suffix);           // e.g. 2, 3
            $maxBaseLen = 49 - $suffixLen;          // 49 = 50 - 1 (for '/')
            $trimmedBase = substr($base, 0, $maxBaseLen);
            $candidate = '/' . $trimmedBase . $suffix;
        }

        throw new RuntimeException(
            "Tidak dapat menemukan command unik untuk title \"{$title}\" setelah {$maxAttempts} percobaan. " .
            "Terlalu banyak command serupa sudah terdaftar."
        );
    }
}
