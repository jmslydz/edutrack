# Encoding bugs are recurring — need the actual generation method, not another guess-fix

Two things came back / turned up in the raw verification:

1. `database/edutrack.sql` still has a UTF-8 BOM at the start — this is
   the SAME bug as before. It was manually stripped once as a
   workaround, but it returned on the next export, which means the fix
   didn't address the actual cause.
2. There's a mojibake byte sequence (`C2 A2 E2 82 AC E2 80 9D`) in the
   comment on line 97 — this is the classic signature of text being
   read in one encoding (likely Windows-1252/Latin-1) and written out
   claiming to be UTF-8 without actually being converted. This looks
   like an em-dash character that got corrupted somewhere in the
   pipeline.

## Before attempting another fix, answer this first:

**What exact process/tool generates `database/edutrack.sql`?** For example:
- A PHP script that queries the live DB and writes the file?
- A Python script?
- `mysqldump` run via command line or through a GUI tool?
- phpMyAdmin's "Export" feature?
- Something else?

Paste the actual code/command that produces this file (or the relevant
few lines around where it opens/writes the file) so the real cause can
be identified instead of guessing again.

## If it turns out to be a Python script:

Check whether the file is being opened with `encoding='utf-8-sig'`
instead of `encoding='utf-8'`. `utf-8-sig` **deliberately writes a
BOM** — it's an easy mistake to make because the name sounds like the
"correct" UTF-8 option. If you find this, changing `utf-8-sig` to
`utf-8` should fix the BOM issue at the source, not just as a one-time
strip.

## For the mojibake character (regardless of tool):

Simplest fix: **avoid non-ASCII characters like em-dashes (—) in SQL
comments entirely.** Replace them with a plain hyphen (`-`) or double
hyphen (`--`). This sidesteps the whole encoding-mismatch problem
rather than trying to get every step of the pipeline to agree on the
same encoding.

## After fixing, verify with a byte-level check (not just `file`,
since `file` isn't available on your Windows box):

In PowerShell:
```powershell
$bytes = [System.IO.File]::ReadAllBytes("database\edutrack.sql")
"{0:X2} {1:X2} {2:X2}" -f $bytes[0], $bytes[1], $bytes[2]
```

If the first 3 bytes are `EF BB BF`, the BOM is still there — fix
didn't work. If they're something else entirely (e.g. `2D 2D 20` for
`-- `, the start of the first comment line), the BOM is gone and
that's the correct result.

Also scan the whole file for non-ASCII bytes to catch the mojibake issue:
```powershell
$bytes = [System.IO.File]::ReadAllBytes("database\edutrack.sql")
$nonAscii = @()
for ($i = 0; $i -lt $bytes.Length; $i++) {
    if ($bytes[$i] -gt 127) { $nonAscii += $i }
}
"Non-ASCII byte count: $($nonAscii.Count)"
if ($nonAscii.Count -gt 0) { "First few offsets: $($nonAscii[0..4] -join ', ')" }
```

Paste both outputs after your fix.
