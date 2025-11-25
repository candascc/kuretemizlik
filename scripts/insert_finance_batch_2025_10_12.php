<?php
declare(strict_types=1);

function d(string $d): string {
	// convert dd.mm.yy to yyyy-mm-dd (assume 20yy)
	[$dd,$mm,$yy] = explode('.', $d);
	$yy = strlen($yy) === 2 ? ('20' . $yy) : $yy;
	return sprintf('%04d-%02d-%02d', (int)$yy, (int)$mm, (int)$dd);
}

function ins(PDO $pdo, string $kind, string $category, float $amount, string $date, string $note): void {
	$sql = "INSERT INTO money_entries (kind, category, amount, date, note, job_id, recurring_job_id, created_by, company_id, created_at, updated_at)
	        VALUES (:kind, :category, :amount, :date, :note, NULL, NULL, 1, 1, datetime('now'), datetime('now'))";
	$pdo->prepare($sql)->execute([
		':kind' => $kind,
		':category' => $category,
		':amount' => $amount,
		':date' => $date,
		':note' => $note,
	]);
}

try {
	$dbPath = __DIR__ . '/../db/app.sqlite';
	$pdo = new PDO('sqlite:' . $dbPath);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$pdo->exec("PRAGMA foreign_keys = ON");
	$pdo->beginTransaction();

	// Ensure default company and system user exist
	$pdo->exec("
	INSERT INTO companies (id, name, is_active, created_at, updated_at)
	SELECT 1, 'Default Company', 1, datetime('now'), datetime('now')
	WHERE NOT EXISTS (SELECT 1 FROM companies WHERE id = 1);
	");
	$pwd = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
	$stmt = $pdo->prepare("
	INSERT INTO users (id, username, password_hash, role, is_active, created_at, updated_at)
	SELECT 1, 'system', :pwd, 'ADMIN', 1, datetime('now'), datetime('now')
	WHERE NOT EXISTS (SELECT 1 FROM users WHERE id = 1);
	");
	$stmt->execute([':pwd' => $pwd]);

	// Mapping rules learned from DB:
	// yakıt -> ulaşım, yemek/market/alışveriş -> yemek vb., yövmiye -> yövmiye, maaş -> maaş,
	// malzeme -> malzeme, taksi -> ulaşım, 'aldı' gelir -> Hizmet Geliri, avans -> yövmiye

	// 26.10.25
	ins($pdo,'EXPENSE','yemek vb.',670,d('26.10.25'),'necla alışveriş');
	ins($pdo,'EXPENSE','ulaşım',500,d('26.10.25'),'necla yakıt');

	// 27.10.25
	ins($pdo,'EXPENSE','ulaşım',269,d('27.10.25'),'necla yakıt kızılkum');
	ins($pdo,'EXPENSE','yövmiye',10500,d('27.10.25'),'candaş küçük haticeye yövmiye');
	ins($pdo,'EXPENSE','yövmiye',600,d('27.10.25'),'necla bahattine yövmiye ödedi');
	ins($pdo,'EXPENSE','ulaşım',400,d('27.10.25'),'necla yakıt');
	ins($pdo,'EXPENSE','ulaşım',1000,d('27.10.25'),'necla yakıt');

	// 28.10.25
	ins($pdo,'EXPENSE','yövmiye',7500,d('28.10.25'),'candaş sarı haticeye yövmiye ödedi');
	ins($pdo,'INCOME','Hizmet Geliri',7000,d('28.10.25'),'necla aldı');
	ins($pdo,'EXPENSE','yemek vb.',450,d('28.10.25'),'necla yemek');
	ins($pdo,'EXPENSE','yövmiye',2000,d('28.10.25'),'candaş bahattine yövmiye ödedi');
	ins($pdo,'EXPENSE','ulaşım',500,d('28.10.25'),'necla yakıt');

	// 29.10.25
	ins($pdo,'INCOME','Hizmet Geliri',2000,d('29.10.25'),'necla aldı, öğrenci kızlardan');
	ins($pdo,'INCOME','Hizmet Geliri',4000,d('29.10.25'),'candaş aldı, aktıp personel');
	ins($pdo,'EXPENSE','malzeme',18000,d('29.10.25'),'candaş makina ödedi');
	ins($pdo,'EXPENSE','ulaşım',1000,d('29.10.25'),'necla yakıt');
	ins($pdo,'EXPENSE','yemek vb.',200,d('29.10.25'),'necla yemek aldı');
	ins($pdo,'EXPENSE','yövmiye',1250,d('29.10.25'),'necla nuraya yövmiye verdi');

	// 30.10.25
	ins($pdo,'EXPENSE','yövmiye',2500,d('30.10.25'),'necla nurgüle yövmiye verdi');
	ins($pdo,'EXPENSE','yemek vb.',330,d('30.10.25'),'necla yemek aldı');

	// 31.10.25
	ins($pdo,'INCOME','Hizmet Geliri',10500,d('31.10.25'),'necla aldı, reyis');

	// 03.11.25
	ins($pdo,'EXPENSE','yemek vb.',300,d('03.11.25'),'necla yemek ödedi');
	ins($pdo,'EXPENSE','ulaşım',800,d('03.11.25'),'necla yakıt');
	ins($pdo,'EXPENSE','malzeme',140,d('03.11.25'),'necla kapı kolu');

	// 04.11.25
	ins($pdo,'INCOME','Hizmet Geliri',30000,d('04.11.25'),'candaş, sinemadan aldı');
	ins($pdo,'EXPENSE','yövmiye',12000,d('04.11.25'),'candaş bahattine yövmiye ödedi');
	ins($pdo,'EXPENSE','ulaşım',630,d('04.11.25'),'necla taksi verdi');
	ins($pdo,'EXPENSE','yemek vb.',279,d('04.11.25'),'necla market verdi');

	// 05.11.25
	ins($pdo,'EXPENSE','ulaşım',1000,d('05.11.25'),'candaş yakıt');
	ins($pdo,'EXPENSE','yemek vb.',380,d('05.11.25'),'necla yemek');
	ins($pdo,'INCOME','Hizmet Geliri',15900,d('05.11.25'),'necla aldı, reis');
	ins($pdo,'EXPENSE','yövmiye',7500,d('05.11.25'),'necla sarı haticeye yövmiye');

	// 06.11.25
	ins($pdo,'EXPENSE','yemek vb.',450,d('06.11.25'),'necla yemek');
	ins($pdo,'EXPENSE','maaş',26500,d('06.11.25'),'candaş filiz maaş ödedi');
	ins($pdo,'EXPENSE','ulaşım',1000,d('06.11.25'),'candaş k.k yakıt');

	// 07.11.25
	ins($pdo,'EXPENSE','yövmiye',3000,d('07.11.25'),'candaş şahin avans');
	ins($pdo,'EXPENSE','yövmiye',5000,d('07.11.25'),'candaş küçük haticeye yövmiye ödedi');
	ins($pdo,'EXPENSE','malzeme',1035,d('07.11.25'),'candaş İSG ödedi');

	// 08.11.25
	ins($pdo,'EXPENSE','yemek vb.',520,d('08.11.25'),'necla yemek');
	ins($pdo,'EXPENSE','ulaşım',1000,d('08.11.25'),'candaş yakıt');
	ins($pdo,'EXPENSE','yemek vb.',450,d('08.11.25'),'necla yemek');
	ins($pdo,'EXPENSE','yemek vb.',100,d('08.11.25'),'necla gider');
	ins($pdo,'EXPENSE','yemek vb.',326,d('08.11.25'),'necla market');

	// 09.11.25
	ins($pdo,'EXPENSE','malzeme',278,d('09.11.25'),'necla malzeme');
	ins($pdo,'EXPENSE','ulaşım',1000,d('09.11.25'),'necla yakıt');

	// 12.11.25
	ins($pdo,'EXPENSE','yövmiye',4500,d('12.11.25'),'şahin yövmiye, candaş ödedi');

	$pdo->commit();
	echo "OK\n";
} catch (Throwable $e) {
	if ($pdo && $pdo->inTransaction()) {
		$pdo->rollBack();
	}
	fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
	exit(1);
}


