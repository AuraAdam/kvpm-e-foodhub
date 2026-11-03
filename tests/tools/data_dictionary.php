<?php
// Development tool: prints a Markdown data dictionary generated from database/schema.sql.
$sql = file_get_contents(__DIR__ . '/../../database/schema.sql');
preg_match_all('/CREATE TABLE (\w+) \((.*?)\n\) ENGINE/s', $sql, $tables, PREG_SET_ORDER);
foreach ($tables as [$all, $table, $body]) {
    $cols = []; $keys = []; $fks = []; $checks = [];
    foreach (preg_split('/,\s*\n/', trim($body)) as $line) {
        $line = trim(rtrim($line, ','));
        if (preg_match('/^(UNIQUE KEY|KEY) (\w+) \(([^)]+)\)/', $line, $m)) { $keys[] = ($m[1] === 'KEY' ? 'Index' : 'Unique') . " `{$m[3]}`"; continue; }
        if (preg_match('/^CONSTRAINT \w+ FOREIGN KEY \((\w+)\) REFERENCES (\w+) \((\w+)\) ON DELETE (\w+(?: NULL)?)/', $line, $m)) { $fks[$m[1]] = "FK → {$m[2]}.{$m[3]} (on delete {$m[4]})"; continue; }
        if (preg_match('/^CONSTRAINT \w+ CHECK \((.*)\)$/', $line, $m)) { $checks[] = $m[1]; continue; }
        if (preg_match('/^(\w+) (.+)$/', $line, $m)) {
            $def = $m[2];
            preg_match('/^([A-Z]+(?:\([0-9,]+\))?(?: UNSIGNED)?)/', $def, $t);
            $null = str_contains($def, 'NOT NULL') ? 'No' : 'Yes';
            $default = preg_match('/DEFAULT (\S+(?: ON UPDATE CURRENT_TIMESTAMP)?)/', $def, $d) ? $d[1] : '';
            $note = str_contains($def, 'PRIMARY KEY') ? 'PK' . (str_contains($def, 'AUTO_INCREMENT') ? ', auto increment' : '') : '';
            $cols[$m[1]] = [$t[1] ?? $def, $null, $default, $note];
        }
    }
    foreach ($fks as $c => $f) { $cols[$c][3] = trim($cols[$c][3] . ' ' . $f); }
    echo "### `$table`\n\n| Column | Type | Null | Default | Key / constraint |\n|---|---|---|---|---|\n";
    foreach ($cols as $c => [$t, $n, $d, $k]) { echo "| $c | $t | $n | " . str_replace('|', '\|', $d) . " | $k |\n"; }
    $extra = array_merge($keys, array_map(fn ($c) => "Check `$c`", $checks));
    if ($extra) { echo "\n" . implode(' · ', $extra) . "\n"; }
    echo "\n";
}
