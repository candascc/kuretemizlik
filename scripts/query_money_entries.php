<?php
declare(strict_types=1);

// Simple inspector for money_entries kinds and categories
try {
	$dbPath = __DIR__ . '/../db/app.sqlite';
	$pdo = new PDO('sqlite:' . $dbPath);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	echo "kind | category | count\n";
	echo "------------------------\n";
	$sql = "SELECT kind, category, COUNT(*) AS c
	        FROM money_entries
	        GROUP BY kind, category
	        ORDER BY kind, category";
	foreach ($pdo->query($sql, PDO::FETCH_ASSOC) as $row) {
		echo ($row['kind'] ?? ''), ' | ', ($row['category'] ?? ''), ' | ', ($row['c'] ?? 0), PHP_EOL;
	}

	echo PHP_EOL, "Sample notes by keyword (yakıt, yemek, alışveriş/market, yövmiye, maaş, avans, makina, isg, malzeme, taksi, aldı):", PHP_EOL;
	$keywords = [
		'yakıt','yemek','alışveriş','alisveris','market','yövmiye','yovmiye',
		'maaş','maas','avans','makina','makine','isg','i̇sg','malzeme','taksi','aldı','alindi'
	];
	foreach ($keywords as $kw) {
		$stmt = $pdo->prepare("SELECT id, date, kind, category, amount, note
		                       FROM money_entries
		                       WHERE LOWER(note) LIKE ?
		                       ORDER BY date DESC
		                       LIMIT 3");
		$stmt->execute(['%'.mb_strtolower($kw, 'UTF-8').'%']);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (!$rows) {
			continue;
		}
		echo PHP_EOL, '== keyword: ', $kw, ' ==', PHP_EOL;
		foreach ($rows as $r) {
			echo ($r['date'] ?? ''), ' | ', ($r['kind'] ?? ''), ' | ', ($r['category'] ?? ''), ' | ', ($r['amount'] ?? ''), ' | ', ($r['note'] ?? ''), PHP_EOL;
		}
	}
} catch (Throwable $e) {
	fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
	exit(1);
}


