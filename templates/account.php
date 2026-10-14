<section class="account<?= $user ? ' profile-page' : ' login-view' ?>">
  <?php if (!$user): ?>
    <?php $register = ($_GET['modo'] ?? '') === 'registro'; ?>
    <div class="account-login-card">
      <p class="login-kicker">Pǔtōnghuà</p>
      <h1><?= $register ? 'Crear cuenta' : 'Login' ?></h1>
      <p class="lede">Entrá para guardar progreso, foto de perfil y autoevaluaciones.</p>
      <a class="google-btn" href="<?= e(url('auth/google')) ?>">
        <svg viewBox="0 0 48 48" width="18" height="18" aria-hidden="true">
          <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3.1l5.7-5.7C34.2 6.1 29.4 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.7-.4-3.5z"/>
          <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 12 24 12c3.1 0 5.8 1.2 8 3.1l5.7-5.7C34.2 6.1 29.4 4 24 4 16.3 4 9.6 8.3 6.3 14.7z"/>
          <path fill="#4CAF50" d="M24 44c5.2 0 10-2 13.6-5.2l-6.3-5.3C29.3 35.1 26.8 36 24 36c-5.3 0-9.7-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/>
          <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-1.1 3.2-3.5 5.7-6.7 7.1l6.3 5.3C37.4 38.3 44 33 44 24c0-1.3-.1-2.7-.4-3.5z"/>
        </svg>
        Continuar con Google
      </a>
      <div class="login-divider" aria-hidden="true"><span>o con email</span></div>
      <?php if ($register): ?>
        <form method="post" class="account-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="register">
          <label>Nombre <input name="display_name" required autocomplete="name"></label>
          <label>Email <input type="email" name="email" required autocomplete="email"></label>
          <label>Contraseña <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
          <button class="primary-btn" type="submit">Registrarme</button>
        </form>
        <p class="account-switch">¿Ya tenés cuenta? <a href="<?= e(url('cuenta')) ?>">Ingresá</a></p>
      <?php else: ?>
        <form method="post" class="account-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="login">
          <label>Email <input type="email" name="email" required autocomplete="username"></label>
          <label>Contraseña <input type="password" name="password" required autocomplete="current-password"></label>
          <button class="primary-btn" type="submit">Ingresar</button>
        </form>
        <p class="account-switch">¿No tenés cuenta? <a href="<?= e(url('cuenta')) ?>?modo=registro">Registrate</a></p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <?php
    $photo = avatar_src($user);
    $editing = ($_GET['editar'] ?? '') === 'nombre';
    $byLevel = [];
    foreach ($lessons as $lesson) {
        $byLevel[(int) $lesson['level_id']][] = $lesson;
    }
    $levelStats = [];
    foreach ($navLevels as $level) {
        $items = $byLevel[(int) $level['id']] ?? [];
        $done = 0;
        $started = 0;
        foreach ($items as $lesson) {
            $row = $progressMap[(int) $lesson['id']] ?? null;
            if (!empty($row['completed'])) {
                $done++;
            } elseif ($row) {
                $started++;
            }
        }
        $levelStats[(int) $level['id']] = ['done' => $done, 'total' => count($items), 'started' => $started];
    }
    $focus = null;
    foreach ($navLevels as $level) {
        $stat = $levelStats[(int) $level['id']];
        if ($stat['total'] > 0 && $stat['done'] < $stat['total']) {
            $focus = $level;
            break;
        }
    }
    if (!$focus && $navLevels) {
        $focus = $navLevels[array_key_last($navLevels)];
    }
    $focusStat = $focus ? $levelStats[(int) $focus['id']] : ['done' => 0, 'total' => 0, 'started' => 0];
    $focusPct = $focusStat['total'] ? (int) round($focusStat['done'] / $focusStat['total'] * 100) : 0;
    $allDone = 0;
    $allTotal = 0;
    foreach ($levelStats as $stat) {
        $allDone += (int) $stat['done'];
        $allTotal += (int) $stat['total'];
    }
    $allPct = $allTotal ? (int) round($allDone / $allTotal * 100) : 0;
    $quizzesPassed = 0;
    $quizAttempts = 0;
    $bestScore = null;
    foreach ($assessMap as $row) {
        $quizAttempts += (int) $row['attempts'];
        if (!empty($row['passed'])) {
            $quizzesPassed++;
        }
        $best = (int) $row['best'];
        $bestScore = $bestScore === null ? $best : max($bestScore, $best);
    }
    $months = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    $memberSince = '';
    if (!empty($user['created_at'])) {
        $ts = strtotime((string) $user['created_at']);
        if ($ts) {
            $memberSince = (int) date('j', $ts) . ' de ' . $months[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
        }
    }
    ?>
    <section class="profile-hero">
      <div class="profile-hero-glow" aria-hidden="true"></div>
      <div class="profile-hero-main">
        <div class="profile-identity">
          <div class="profile-avatar">
            <?php if ($photo !== ''): ?>
              <img src="<?= e($photo) ?>" alt="Tu foto de perfil">
            <?php else: ?>
              <span class="avatar-fallback" aria-hidden="true"><?= e(mb_substr($user['display_name'], 0, 1)) ?></span>
            <?php endif; ?>
          </div>
          <div class="profile-copy">
            <?php if ($editing): ?>
              <form class="profile-name-edit" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile">
                <input class="profile-name-input" name="display_name" value="<?= e($user['display_name']) ?>" maxlength="60" required aria-label="Nombre">
                <div class="profile-name-actions">
                  <button class="profile-text-action solid" type="submit">Guardar</button>
                  <a class="profile-text-action" href="<?= e(url('cuenta')) ?>">Cancelar</a>
                </div>
              </form>
            <?php else: ?>
              <h1><?= e($user['display_name']) ?></h1>
            <?php endif; ?>
            <p class="profile-email"><?= e($user['email']) ?></p>
            <p class="profile-meta">
              <?php if ($memberSince !== ''): ?>Miembro desde <?= e($memberSince) ?> · <?php endif; ?>
              Alumno<?php if ($quizzesPassed > 0): ?> · <?= $quizzesPassed ?> autoevals<?php endif; ?>
            </p>
            <div class="profile-actions">
              <?php if (!$editing): ?>
                <a class="profile-text-action" href="<?= e(url('cuenta')) ?>?editar=nombre">Editar nombre</a>
              <?php endif; ?>
              <form method="post" enctype="multipart/form-data" data-avatar-form>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="avatar">
                <label class="profile-text-action">
                  Subir foto
                  <input class="visually-hidden" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
                </label>
              </form>
              <a class="profile-text-action profile-certs" href="<?= e(url('certificados')) ?>">Certificados</a>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="logout">
                <button class="profile-text-action profile-logout" type="submit">Cerrar sesión</button>
              </form>
            </div>
          </div>
        </div>
        <div class="profile-ring-card">
          <div class="profile-ring" style="--pct: <?= $allPct ?>" aria-label="<?= $allPct ?>% de todas las clases">
            <strong><?= $allPct ?>%</strong>
            <span>completado</span>
          </div>
          <p><?= $allDone ?>/<?= $allTotal ?> clases</p>
          <?php if ($focus): ?>
            <a class="primary-btn" href="<?= e(url($focus['slug'])) ?>">Ver clases</a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="profile-metrics" aria-label="Métricas">
      <article>
        <span>Palabras</span>
        <strong><?= (int) $learnedWords ?></strong>
        <em>aprendidas</em>
      </article>
      <article>
        <span>Completadas</span>
        <strong><?= $allDone ?></strong>
        <em>de <?= $allTotal ?></em>
      </article>
      <article>
        <span>En curso</span>
        <strong><?= (int) $focusStat['started'] ?></strong>
        <em>iniciadas sin cerrar</em>
      </article>
      <article>
        <span>Autoevals</span>
        <strong><?= $quizzesPassed ?></strong>
        <em><?= $quizAttempts ?> intentos totales</em>
      </article>
      <article>
        <span>Mejor score</span>
        <strong><?= $bestScore === null ? '—' : $bestScore . '%' ?></strong>
        <em>en quizzes</em>
      </article>
    </section>

    <section class="profile-panel">
      <div class="profile-panel-head">
        <div>
          <p class="eyebrow">Camino completo</p>
          <h2>Niveles del curso</h2>
        </div>
      </div>
      <div class="level-accordions">
        <?php foreach ($navLevels as $level): ?>
          <?php
          $items = $byLevel[(int) $level['id']] ?? [];
          $stat = $levelStats[(int) $level['id']];
          $open = $focus && (int) $level['id'] === (int) $focus['id'];
          $levelPct = $stat['total'] ? (int) round($stat['done'] / $stat['total'] * 100) : 0;
          $remaining = (int) $stat['total'] - (int) $stat['done'];
          $cert = $certs[(int) $level['id']] ?? null;
          ?>
          <details class="level-accordion" <?= $open ? 'open' : '' ?>>
            <summary>
              <span class="level-accordion-copy"><strong><?= e($level['title']) ?></strong></span>
              <span class="level-accordion-meta">
                <span class="level-accordion-count"><?= (int) $stat['done'] ?>/<?= (int) $stat['total'] ?></span>
                <span class="level-accordion-chevron" aria-hidden="true"></span>
              </span>
            </summary>
            <div class="level-accordion-panel">
              <div class="progress-summary">
                <div class="progress-bar" role="meter" aria-valuenow="<?= (int) $stat['done'] ?>" aria-valuemax="<?= (int) $stat['total'] ?>"><i style="width: <?= $levelPct ?>%"></i></div>
                <p class="lede">
                  <?php if ($cert): ?>
                    Completaste este nivel. El certificado está en <a href="<?= e(url('certificados')) ?>">Certificados</a>.
                  <?php elseif ($remaining === 0): ?>
                    Completaste las clases de este nivel.
                  <?php else: ?>
                    Te faltan <?= $remaining ?> clases para el certificado.
                  <?php endif; ?>
                </p>
              </div>
              <p class="level-accordion-desc"><?= e($level['description']) ?></p>
              <ul class="progress-list rich">
                <?php foreach ($items as $lesson): ?>
                  <?php
                  $row = $progressMap[(int) $lesson['id']] ?? null;
                  $score = $assessMap[(int) $lesson['id']] ?? null;
                  $doneLesson = !empty($row['completed']);
                  $wordCount = (int) ($wordsByLesson[(int) $lesson['id']] ?? 0);
                  $status = $doneLesson ? 'Completada' : ($row ? 'En curso' : 'Sin empezar');
                  if ($wordCount > 0) {
                      $status .= ' · ' . $wordCount . ($wordCount === 1 ? ' palabra' : ' palabras');
                  }
                  if ($score && !empty($score['passed'])) {
                      $status .= ' · Autoeval OK';
                  } elseif ($score) {
                      $status .= ' · ' . (int) $score['best'] . '%';
                  }
                  ?>
                  <li class="<?= $doneLesson ? 'done' : ($row ? 'active' : '') ?>">
                    <div class="progress-item-main">
                      <span class="progress-num"><?= str_pad((string) $lesson['number'], 2, '0', STR_PAD_LEFT) ?></span>
                      <div>
                        <strong><?= e($lesson['title']) ?></strong>
                        <span><?= e($status) ?></span>
                      </div>
                    </div>
                    <a href="<?= e(url($level['slug'] . '/clase/' . $lesson['slug'])) ?>"><?= $doneLesson ? 'Repasar' : 'Entrar' ?> →</a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</section>
