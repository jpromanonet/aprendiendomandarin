<?php

declare(strict_types=1);

function page_home(): void
{
    $levels = db()->query('SELECT * FROM levels ORDER BY sort_order, id')->fetchAll();
    $counts = db()->query('SELECT level_id, COUNT(*) AS total FROM lessons GROUP BY level_id')->fetchAll();
    $byLevel = [];
    foreach ($counts as $row) {
        $byLevel[(int) $row['level_id']] = (int) $row['total'];
    }
    render('home', [
        'title' => 'Aprendiendo mandarín',
        'navLevels' => $levels,
        'lessonCounts' => $byLevel,
    ]);
}

function page_level(int $levelId): void
{
    $stmt = db()->prepare('SELECT * FROM levels WHERE id = ?');
    $stmt->execute([$levelId]);
    $level = $stmt->fetch();
    if (!$level) {
        render('not-found', ['title' => 'Nivel'], 404);
    }
    $lessons = db()->prepare('SELECT id, slug, number, title, summary FROM lessons WHERE level_id = ? ORDER BY number');
    $lessons->execute([$levelId]);
    $progress = [];
    $user = current_user();
    if ($user) {
        $rows = db()->prepare(
            'SELECT p.lesson_id, p.completed FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id WHERE p.user_id = ? AND l.level_id = ?'
        );
        $rows->execute([(int) $user['id'], $levelId]);
        foreach ($rows as $row) {
            $progress[(int) $row['lesson_id']] = (int) $row['completed'] === 1;
        }
    }
    render('level', [
        'title' => $level['title'],
        'level' => $level,
        'lessons' => $lessons->fetchAll(),
        'progress' => $progress,
    ]);
}

function page_class(int $levelId, string $slug): void
{
    $levelStmt = db()->prepare('SELECT * FROM levels WHERE id = ?');
    $levelStmt->execute([$levelId]);
    $level = $levelStmt->fetch();
    $lessonStmt = db()->prepare('SELECT * FROM lessons WHERE level_id = ? AND slug = ?');
    $lessonStmt->execute([$levelId, $slug]);
    $lesson = $lessonStmt->fetch();
    if (!$level || !$lesson) {
        render('not-found', ['title' => 'Clase'], 404);
    }
    $user = current_user();
    if ($user) {
        $seen = db()->prepare(
            'INSERT INTO lesson_progress (user_id, lesson_id, percent, last_seen_at) VALUES (?, ?, 10, NOW())
             ON DUPLICATE KEY UPDATE last_seen_at = NOW(), percent = IF(completed = 1, percent, GREATEST(percent, 10))'
        );
        $seen->execute([(int) $user['id'], (int) $lesson['id']]);
    }
    $siblings = db()->prepare('SELECT id, slug, number, title FROM lessons WHERE level_id = ? ORDER BY number');
    $siblings->execute([$levelId]);
    $list = $siblings->fetchAll();
    $prev = null;
    $next = null;
    $position = 1;
    foreach ($list as $index => $item) {
        if ((int) $item['id'] === (int) $lesson['id']) {
            $position = $index + 1;
            $prev = $list[$index - 1] ?? null;
            $next = $list[$index + 1] ?? null;
            break;
        }
    }
    $quizStmt = db()->prepare('SELECT * FROM quizzes WHERE lesson_id = ?');
    $quizStmt->execute([(int) $lesson['id']]);
    $quiz = $quizStmt->fetch() ?: null;
    $attempts = [];
    if ($user && $quiz) {
        $attemptStmt = db()->prepare('SELECT * FROM self_assessments WHERE user_id = ? AND lesson_id = ? ORDER BY attempt');
        $attemptStmt->execute([(int) $user['id'], (int) $lesson['id']]);
        $attempts = $attemptStmt->fetchAll();
    }
    $progress = null;
    if ($user) {
        $progressStmt = db()->prepare('SELECT * FROM lesson_progress WHERE user_id = ? AND lesson_id = ?');
        $progressStmt->execute([(int) $user['id'], (int) $lesson['id']]);
        $progress = $progressStmt->fetch() ?: null;
    }
    render('class', [
        'title' => $lesson['title'],
        'level' => $level,
        'lesson' => $lesson,
        'content' => json_decode((string) $lesson['content_json'], true) ?: ['sections' => []],
        'quiz' => $quiz,
        'questions' => $quiz ? (json_decode((string) $quiz['questions_json'], true) ?: []) : [],
        'attempts' => $attempts,
        'progress' => $progress,
        'prev' => $prev,
        'next' => $next,
        'position' => $position,
        'lessonTotal' => count($list),
    ]);
}

function post_class(int $levelId, string $slug): void
{
    csrf_check();
    $user = current_user();
    if (!$user) {
        flash('Iniciá sesión para guardar el progreso.');
        redirect('cuenta');
    }
    $lessonStmt = db()->prepare('SELECT * FROM lessons WHERE level_id = ? AND slug = ?');
    $lessonStmt->execute([$levelId, $slug]);
    $lesson = $lessonStmt->fetch();
    if (!$lesson) {
        render('not-found', ['title' => 'Clase'], 404);
    }
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'complete') {
        $save = db()->prepare(
            'INSERT INTO lesson_progress (user_id, lesson_id, completed, percent, last_seen_at, completed_at)
             VALUES (?, ?, 1, 100, NOW(), NOW())
             ON DUPLICATE KEY UPDATE completed = 1, percent = 100, last_seen_at = NOW(), completed_at = COALESCE(completed_at, NOW())'
        );
        $save->execute([(int) $user['id'], (int) $lesson['id']]);
        maybe_certificate((int) $user['id'], $levelId);
        flash('Clase marcada como hecha.');
    } elseif ($action === 'quiz') {
        grade_quiz((int) $user['id'], $lesson);
    }
    redirect('hsk-' . $levelId . '/clase/' . $slug);
}

function grade_quiz(int $userId, array $lesson): void
{
    $quizStmt = db()->prepare('SELECT * FROM quizzes WHERE lesson_id = ?');
    $quizStmt->execute([(int) $lesson['id']]);
    $quiz = $quizStmt->fetch();
    if (!$quiz) {
        return;
    }
    $questions = json_decode((string) $quiz['questions_json'], true) ?: [];
    $attemptStmt = db()->prepare('SELECT * FROM self_assessments WHERE user_id = ? AND lesson_id = ? ORDER BY attempt');
    $attemptStmt->execute([$userId, (int) $lesson['id']]);
    $attempts = $attemptStmt->fetchAll();
    if (count($attempts) >= (int) $quiz['max_attempts']) {
        flash('Ya usaste los 5 intentos de esta autoevaluación.');
        return;
    }
    foreach ($attempts as $attempt) {
        if ((int) $attempt['passed'] === 1) {
            flash('Esta autoevaluación ya está aprobada.');
            return;
        }
    }
    $posted = $_POST['answers'] ?? [];
    if (!is_array($posted)) {
        $posted = [];
    }
    $correct = 0;
    $stored = [];
    foreach ($questions as $question) {
        $chosen = (string) ($posted[$question['id']] ?? '');
        $ok = $chosen !== '' && $chosen === $question['correct'];
        if ($ok) {
            $correct++;
        }
        $stored[$question['id']] = ['chosen' => $chosen, 'ok' => $ok];
    }
    $total = max(1, count($questions));
    $score = (int) round(($correct / $total) * 100);
    $passed = $score >= (int) $quiz['pass_score'];
    $insert = db()->prepare(
        'INSERT INTO self_assessments (user_id, lesson_id, attempt, score, passed, answers_json) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insert->execute([
        $userId,
        (int) $lesson['id'],
        count($attempts) + 1,
        $score,
        $passed ? 1 : 0,
        json_encode($stored, JSON_UNESCAPED_UNICODE),
    ]);
    flash($passed
        ? 'Aprobaste con ' . $score . '%. '
        : 'Sacaste ' . $score . '%. Para aprobar hacen falta 70%.');
}

function maybe_certificate(int $userId, int $levelId): void
{
    $totalStmt = db()->prepare('SELECT COUNT(*) FROM lessons WHERE level_id = ?');
    $totalStmt->execute([$levelId]);
    $total = (int) $totalStmt->fetchColumn();
    $doneStmt = db()->prepare(
        'SELECT COUNT(*) FROM lesson_progress p JOIN lessons l ON l.id = p.lesson_id WHERE p.user_id = ? AND l.level_id = ? AND p.completed = 1'
    );
    $doneStmt->execute([$userId, $levelId]);
    $done = (int) $doneStmt->fetchColumn();
    if ($total === 0 || $done < $total) {
        return;
    }
    $exists = db()->prepare('SELECT id FROM certificates WHERE user_id = ? AND level_id = ?');
    $exists->execute([$userId, $levelId]);
    if ($exists->fetchColumn()) {
        return;
    }
    $code = 'HSK' . $levelId . '-' . strtoupper(bin2hex(random_bytes(4)));
    $insert = db()->prepare('INSERT INTO certificates (user_id, level_id, code) VALUES (?, ?, ?)');
    $insert->execute([$userId, $levelId, $code]);
    maybe_final_certificate($userId);
}

function maybe_final_certificate(int $userId): void
{
    $count = db()->prepare('SELECT COUNT(*) FROM certificates WHERE user_id = ?');
    $count->execute([$userId]);
    if ((int) $count->fetchColumn() < 9) {
        return;
    }
    $exists = db()->prepare('SELECT id FROM course_certificates WHERE user_id = ?');
    $exists->execute([$userId]);
    if ($exists->fetchColumn()) {
        return;
    }
    $code = 'FINAL-' . strtoupper(bin2hex(random_bytes(4)));
    $insert = db()->prepare('INSERT INTO course_certificates (user_id, code) VALUES (?, ?)');
    $insert->execute([$userId, $code]);
}

function page_dictionary(): void
{
    $q = trim((string) ($_GET['q'] ?? ''));
    $letter = strtoupper(substr((string) ($_GET['letra'] ?? 'A'), 0, 1));
    if (!preg_match('/^[A-Z]$/', $letter)) {
        $letter = 'A';
    }
    $level = (int) ($_GET['nivel'] ?? 0);
    if ($level < 0 || $level > 9) {
        $level = 0;
    }
    $mode = (string) ($_GET['modo'] ?? 'ambos');
    if (!in_array($mode, ['ambos', 'es', 'zh'], true)) {
        $mode = 'ambos';
    }
    $page = max(1, (int) ($_GET['pagina'] ?? 1));
    $limit = 60;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];
    if ($level > 0) {
        $where[] = 'hsk_level = ?';
        $params[] = $level;
    }
    if ($q !== '') {
        $folded = like_escape(plain_es($q));
        $py = like_escape(plain_pinyin($q));
        $qLike = like_escape($q);
        if ($mode === 'es') {
            $where[] = 'espanol_plain LIKE ?';
            $params[] = '%' . $folded . '%';
        } elseif ($mode === 'zh') {
            $where[] = '(hanzi LIKE ? OR pinyin_plain LIKE ?)';
            $params[] = '%' . $qLike . '%';
            $params[] = '%' . $py . '%';
        } else {
            $where[] = '(hanzi LIKE ? OR pinyin_plain LIKE ? OR espanol_plain LIKE ?)';
            $params[] = '%' . $qLike . '%';
            $params[] = '%' . $py . '%';
            $params[] = '%' . $folded . '%';
        }
    } else {
        $needle = mb_strtolower($letter);
        if ($mode === 'es') {
            $where[] = 'espanol_plain LIKE ?';
            $params[] = $needle . '%';
        } else {
            $where[] = 'pinyin_plain LIKE ?';
            $params[] = $needle . '%';
        }
    }
    $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $order = $mode === 'es' ? 'espanol_plain, hanzi' : 'pinyin_plain, hanzi';
    $countStmt = db()->prepare('SELECT COUNT(*) FROM dictionary_entries ' . $sqlWhere);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $stmt = db()->prepare(
        'SELECT hanzi, pinyin, espanol, hsk_level, example_zh, example_pinyin, example_es
         FROM dictionary_entries ' . $sqlWhere . ' ORDER BY ' . $order . ' LIMIT ' . $limit . ' OFFSET ' . $offset
    );
    $stmt->execute($params);
    render('dictionary', [
        'title' => 'Diccionario',
        'entries' => $stmt->fetchAll(),
        'total' => $total,
        'q' => $q,
        'letter' => $letter,
        'level' => $level,
        'mode' => $mode,
        'page' => $page,
        'pages' => max(1, (int) ceil($total / $limit)),
    ]);
}

function page_books(): void
{
    $books = db()->query('SELECT * FROM books ORDER BY created_at DESC')->fetchAll();
    foreach ($books as &$book) {
        $book['has_cover'] = is_file(book_cover_path($book));
    }
    unset($book);
    render('books', [
        'title' => 'Libros',
        'books' => $books,
    ]);
}

function book_cover_path(array $book): string
{
    $base = pathinfo(basename((string) $book['file_name']), PATHINFO_FILENAME);
    return app_root() . '/storage/libros/' . $base . '.jpg';
}

function book_cover(int $id): void
{
    $stmt = db()->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->execute([$id]);
    $book = $stmt->fetch();
    $path = $book ? book_cover_path($book) : '';
    if (!$book || !is_file($path)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: public, max-age=86400');
    readfile($path);
    exit;
}

function download_book(int $id): void
{
    $stmt = db()->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->execute([$id]);
    $book = $stmt->fetch();
    if (!$book) {
        render('not-found', ['title' => 'Libro'], 404);
    }
    $path = app_root() . '/storage/libros/' . basename((string) $book['file_name']);
    if (!is_file($path)) {
        render('not-found', ['title' => 'Libro'], 404);
    }
    header('Content-Type: application/pdf');
    header('Content-Length: ' . (string) filesize($path));
    header('Content-Disposition: attachment; filename="' . rawurlencode((string) $book['title']) . '.pdf"');
    readfile($path);
    exit;
}

function page_certificates(): void
{
    $user = current_user();
    $levels = db()->query('SELECT * FROM levels ORDER BY sort_order, id')->fetchAll();
    $certs = [];
    $finalCert = null;
    $stats = [];
    if ($user) {
        foreach ($levels as $level) {
            maybe_certificate((int) $user['id'], (int) $level['id']);
        }
        maybe_final_certificate((int) $user['id']);
        $certStmt = db()->prepare('SELECT * FROM certificates WHERE user_id = ?');
        $certStmt->execute([(int) $user['id']]);
        foreach ($certStmt as $row) {
            $certs[(int) $row['level_id']] = $row;
        }
        $finalStmt = db()->prepare('SELECT * FROM course_certificates WHERE user_id = ?');
        $finalStmt->execute([(int) $user['id']]);
        $finalCert = $finalStmt->fetch() ?: null;
        $countStmt = db()->prepare(
            'SELECT l.level_id, COUNT(*) AS total, SUM(CASE WHEN p.completed = 1 THEN 1 ELSE 0 END) AS done
             FROM lessons l
             LEFT JOIN lesson_progress p ON p.lesson_id = l.id AND p.user_id = ?
             GROUP BY l.level_id'
        );
        $countStmt->execute([(int) $user['id']]);
        foreach ($countStmt as $row) {
            $stats[(int) $row['level_id']] = ['done' => (int) $row['done'], 'total' => (int) $row['total']];
        }
    }
    render('certificates', [
        'title' => 'Certificados',
        'navLevels' => $levels,
        'certs' => $certs,
        'finalCert' => $finalCert,
        'levelStats' => $stats,
    ]);
}

function page_certificate_level(int $levelId): void
{
    $user = current_user();
    if (!$user) {
        redirect('cuenta');
    }
    maybe_certificate((int) $user['id'], $levelId);
    $stmt = db()->prepare('SELECT c.*, l.title FROM certificates c JOIN levels l ON l.id = c.level_id WHERE c.user_id = ? AND c.level_id = ?');
    $stmt->execute([(int) $user['id'], $levelId]);
    $cert = $stmt->fetch();
    if (!$cert) {
        redirect('certificados');
    }
    $pdf = certificate_pdf([
        'kicker' => 'APRENDIENDO MANDARÍN',
        'title' => 'Certificado',
        'subtitle' => 'Nivel ' . $cert['title'],
        'lead' => 'Se deja constancia de que',
        'name' => (string) $user['display_name'],
        'body' => 'completó todas las clases del nivel ' . $cert['title'] . ' en este curso.',
        'code' => (string) $cert['code'],
        'date' => fecha_es((string) $cert['issued_at']),
    ]);
    send_certificate_pdf('Certificado-' . $cert['title'] . '.pdf', $pdf);
}

function page_certificate_final(): void
{
    $user = current_user();
    if (!$user) {
        redirect('cuenta');
    }
    maybe_final_certificate((int) $user['id']);
    $stmt = db()->prepare('SELECT * FROM course_certificates WHERE user_id = ?');
    $stmt->execute([(int) $user['id']]);
    $cert = $stmt->fetch();
    if (!$cert) {
        redirect('certificados');
    }
    $pdf = certificate_pdf([
        'kicker' => 'APRENDIENDO MANDARÍN',
        'title' => 'Certificado final',
        'subtitle' => 'Curso completo',
        'lead' => 'Se deja constancia de que',
        'name' => (string) $user['display_name'],
        'body' => 'completó los nueve niveles del HSK en este curso.',
        'code' => (string) $cert['code'],
        'date' => fecha_es((string) $cert['issued_at']),
    ]);
    send_certificate_pdf('Certificado-curso.pdf', $pdf);
}

function page_account(): void
{
    $user = current_user();
    $levels = db()->query('SELECT * FROM levels ORDER BY sort_order, id')->fetchAll();
    $lessons = db()->query('SELECT id, level_id, slug, number, title FROM lessons ORDER BY level_id, number')->fetchAll();
    $progress = [];
    $assess = [];
    $certs = [];
    if ($user) {
        $progressStmt = db()->prepare('SELECT * FROM lesson_progress WHERE user_id = ?');
        $progressStmt->execute([(int) $user['id']]);
        foreach ($progressStmt as $row) {
            $progress[(int) $row['lesson_id']] = $row;
        }
        $assessStmt = db()->prepare(
            'SELECT lesson_id, MAX(score) AS best, MAX(passed) AS passed, COUNT(*) AS attempts FROM self_assessments WHERE user_id = ? GROUP BY lesson_id'
        );
        $assessStmt->execute([(int) $user['id']]);
        foreach ($assessStmt as $row) {
            $assess[(int) $row['lesson_id']] = $row;
        }
        $certStmt = db()->prepare('SELECT * FROM certificates WHERE user_id = ?');
        $certStmt->execute([(int) $user['id']]);
        foreach ($certStmt as $row) {
            $certs[(int) $row['level_id']] = $row;
        }
    }
    $learnedWords = 0;
    $wordsByLesson = [];
    $learnedIds = [];
    foreach ($progress as $lessonId => $row) {
        if (!empty($row['completed'])) {
            $learnedIds[(int) $lessonId] = (int) $lessonId;
        }
    }
    foreach ($assess as $lessonId => $row) {
        if (!empty($row['passed'])) {
            $learnedIds[(int) $lessonId] = (int) $lessonId;
        }
    }
    if ($learnedIds) {
        $ids = array_values($learnedIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $wordStmt = db()->prepare("SELECT id, content_json FROM lessons WHERE id IN ($placeholders)");
        $wordStmt->execute($ids);
        foreach ($wordStmt as $row) {
            $content = json_decode((string) $row['content_json'], true);
            $count = 0;
            foreach (($content['sections'] ?? []) as $section) {
                if (($section['type'] ?? '') === 'vocab') {
                    $count += count($section['items'] ?? []);
                }
            }
            $wordsByLesson[(int) $row['id']] = $count;
            $learnedWords += $count;
        }
    }
    render('account', [
        'title' => 'Cuenta',
        'navLevels' => $levels,
        'lessons' => $lessons,
        'progressMap' => $progress,
        'assessMap' => $assess,
        'certs' => $certs,
        'learnedWords' => $learnedWords,
        'wordsByLesson' => $wordsByLesson,
    ]);
}

function post_account(): void
{
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'logout') {
        csrf_check();
        $_SESSION = [];
        session_destroy();
        start_session();
        redirect('cuenta');
    }
    csrf_check();
    if ($action === 'login') {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $user = find_user_by_email($email);
        if (!$user || empty($user['password_hash']) || !password_verify($password, (string) $user['password_hash'])) {
            flash($user && empty($user['password_hash'])
                ? 'Esta cuenta entra con Google.'
                : 'Correo o contraseña incorrectos.');
            redirect('cuenta');
        }
        login_session((int) $user['id']);
        redirect('cuenta');
    }
    if ($action === 'register') {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $name = trim((string) ($_POST['display_name'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $name === '') {
            flash('Revisá el nombre, el correo y que la contraseña tenga al menos 8 caracteres.');
            redirect('cuenta');
        }
        if (find_user_by_email($email)) {
            flash('Ese correo ya tiene cuenta.');
            redirect('cuenta');
        }
        $id = create_user($email, $password, $name);
        login_session($id);
        redirect('cuenta');
    }
    $user = current_user();
    if (!$user) {
        redirect('cuenta');
    }
    if ($action === 'profile') {
        $name = trim((string) ($_POST['display_name'] ?? ''));
        if ($name === '') {
            flash('El nombre no puede quedar vacío.');
            redirect('cuenta');
        }
        $stmt = db()->prepare('UPDATE users SET display_name = ? WHERE id = ?');
        $stmt->execute([$name, (int) $user['id']]);
        flash('Nombre actualizado.');
        redirect('cuenta');
    }
    if ($action === 'avatar') {
        $file = $_FILES['avatar'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('No se pudo leer la imagen.');
            redirect('cuenta');
        }
        $info = @getimagesize((string) $file['tmp_name']);
        $mime = $info['mime'] ?? '';
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if (!$ext || (int) $file['size'] > 2 * 1024 * 1024) {
            flash('La foto tiene que ser JPG, PNG o WebP de hasta 2 MB.');
            redirect('cuenta');
        }
        $dir = app_root() . '/storage/avatars';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        foreach (glob($dir . '/' . (int) $user['id'] . '.*') ?: [] as $old) {
            unlink($old);
        }
        $dest = $dir . '/' . (int) $user['id'] . '.' . $ext;
        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            flash('No se pudo guardar la foto.');
            redirect('cuenta');
        }
        $stmt = db()->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
        $stmt->execute(['local:' . $ext, (int) $user['id']]);
        flash('Foto actualizada.');
        redirect('cuenta');
    }
    redirect('cuenta');
}

function serve_avatar(int $userId): void
{
    $dir = app_root() . '/storage/avatars';
    $matches = glob($dir . '/' . $userId . '.*') ?: [];
    if (!$matches) {
        http_response_code(404);
        exit;
    }
    $path = $matches[0];
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=3600');
    readfile($path);
    exit;
}

function avatar_src(?array $user): string
{
    if (!$user || empty($user['avatar_url'])) {
        return '';
    }
    $url = (string) $user['avatar_url'];
    if (str_starts_with($url, 'local:')) {
        return url('media/avatar/' . (int) $user['id']);
    }
    return $url;
}
