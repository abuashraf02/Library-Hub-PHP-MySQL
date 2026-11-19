<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';
function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        $errors[] = 'Invalid form token. Refresh and try again.';
    } else {
        $type = (string)($_POST['type'] ?? '');
        try {
            if ($type === 'member') {
                $name = trim((string)($_POST['name'] ?? ''));
                $email = trim((string)($_POST['email'] ?? ''));
                if ($name === '' || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
                    $errors[] = 'Enter a name (up to 100 characters) and a valid email.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO members (full_name,email) VALUES (?,?)');
                    $stmt->execute([$name, $email]);
                }
            } elseif ($type === 'book') {
                $title = trim((string)($_POST['title'] ?? ''));
                $author = trim((string)($_POST['author'] ?? ''));
                $isbn = trim((string)($_POST['isbn'] ?? ''));
                if ($title === '' || $author === '' || mb_strlen($title) > 200 || mb_strlen($author) > 120 || mb_strlen($isbn) > 20) {
                    $errors[] = 'Enter a book title and author within the character limits.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO books (title,author,isbn) VALUES (?,?,?)');
                    $stmt->execute([$title, $author, $isbn === '' ? null : $isbn]);
                }
            } elseif ($type === 'loan') {
                $member = filter_var($_POST['member_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                $book = filter_var($_POST['book_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                $due = (string)($_POST['due_date'] ?? '');
                $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $due);
                if (!$member || !$book || !$dt || $dt->format('Y-m-d') !== $due || $due < date('Y-m-d')) {
                    $errors[] = 'Select a member and book, and a valid due date today or later.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO loans (member_id,book_id,due_date) VALUES (?,?,?)');
                    $stmt->execute([$member, $book, $due]);
                }
            } else { $errors[] = 'Unknown form submission.'; }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { $errors[] = 'Duplicate entry or invalid member/book selection. Check your information.'; }
            else { $errors[] = 'Could not save the record. Verify the database setup.'; }
        }
        if (!$errors) {
            $_SESSION['notice'] = 'Record saved successfully to MySQL!';
            header('Location: index.php'); exit;
        }
    }
}
$members = $pdo->query('SELECT member_id, full_name, email, created_at FROM members ORDER BY member_id DESC LIMIT 25')->fetchAll();
$books = $pdo->query('SELECT book_id, title, author, isbn FROM books ORDER BY book_id DESC LIMIT 25')->fetchAll();
$loans = $pdo->query('SELECT l.loan_id, m.full_name, b.title, l.loan_date, l.due_date FROM loans l JOIN members m ON l.member_id=m.member_id JOIN books b ON l.book_id=b.book_id ORDER BY l.loan_id DESC LIMIT 25')->fetchAll();
$notice = $_SESSION['notice'] ?? null; unset($_SESSION['notice']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Library Hub | PHP + MySQL</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header><div class="wrap"><span class="eyebrow">CIS PROJECT · PHP + MYSQL</span><h1>📚 Library Hub</h1><p>A simple library platform for registering members, cataloging books, and tracking loans.</p></div></header>
<main class="wrap">
<?php if ($notice): ?><div class="alert success" role="status"><?=h($notice)?></div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert error" role="alert"><?=h($error)?></div><?php endforeach; ?>
<section class="cards">
<article class="card"><h2>Register a member</h2><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="type" value="member"><label>Full name<input name="name" maxlength="100" required placeholder="e.g. Jordan Smith"></label><label>Email address<input type="email" name="email" maxlength="190" required placeholder="jordan@example.com"></label><button>Save member</button></form></article>
<article class="card"><h2>Add a book</h2><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="type" value="book"><label>Book title<input name="title" maxlength="200" required placeholder="e.g. Introduction to Databases"></label><label>Author<input name="author" maxlength="120" required placeholder="Author name"></label><label>ISBN (optional)<input name="isbn" maxlength="20" placeholder="ISBN"></label><button>Save book</button></form></article>
<article class="card"><h2>Create a loan</h2><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="type" value="loan"><label>Member<select name="member_id" required><option value="">Choose member</option><?php foreach ($members as $m): ?><option value="<?= (int)$m['member_id'] ?>"><?=h($m['full_name'])?></option><?php endforeach; ?></select></label><label>Book<select name="book_id" required><option value="">Choose book</option><?php foreach ($books as $b): ?><option value="<?= (int)$b['book_id'] ?>"><?=h($b['title'])?></option><?php endforeach; ?></select></label><label>Due date<input type="date" name="due_date" min="<?=date('Y-m-d')?>" required></label><button>Save loan</button></form></article>
</section>
<section class="card tablecard"><h2>Recent members</h2><div class="scroll"><table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Registered</th></tr></thead><tbody><?php foreach ($members as $m): ?><tr><td><?= (int)$m['member_id'] ?></td><td><?=h($m['full_name'])?></td><td><?=h($m['email'])?></td><td><?=h($m['created_at'])?></td></tr><?php endforeach; ?></tbody></table></div><?php if (!$members): ?><p class="empty">No members yet. Add one above.</p><?php endif; ?></section>
<section class="card tablecard"><h2>Recent books</h2><div class="scroll"><table><thead><tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th></tr></thead><tbody><?php foreach ($books as $b): ?><tr><td><?= (int)$b['book_id'] ?></td><td><?=h($b['title'])?></td><td><?=h($b['author'])?></td><td><?=h((string)($b['isbn'] ?? '—'))?></td></tr><?php endforeach; ?></tbody></table></div><?php if (!$books): ?><p class="empty">No books yet. Add one above.</p><?php endif; ?></section>
<section class="card tablecard"><h2>Recent loans</h2><div class="scroll"><table><thead><tr><th>ID</th><th>Member</th><th>Book</th><th>Loan date</th><th>Due date</th></tr></thead><tbody><?php foreach ($loans as $l): ?><tr><td><?= (int)$l['loan_id'] ?></td><td><?=h($l['full_name'])?></td><td><?=h($l['title'])?></td><td><?=h($l['loan_date'])?></td><td><?=h($l['due_date'])?></td></tr><?php endforeach; ?></tbody></table></div><?php if (!$loans): ?><p class="empty">No loans yet. Create one above.</p><?php endif; ?></section>
</main><footer>Library Hub · Educational PHP database project</footer></body></html>
