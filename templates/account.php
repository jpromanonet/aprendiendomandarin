<?php if ($user): ?>
<section class="page-hero">
  <div class="page-hero-inner">
    <p class="eyebrow light">Tu lugar</p>
    <h1>Cuenta</h1>
    <p class="lede">El progreso de los nueve niveles queda guardado en esta cuenta.</p>
  </div>
</section>
<?php endif; ?>
<section class="account<?= $user ? '' : ' login-view' ?>">
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
    <?php $photo = avatar_src($user); ?>
    <div class="profile">
      <?php if ($photo !== ''): ?><img class="avatar" src="<?= e($photo) ?>" alt=""><?php else: ?><span class="avatar avatar-fallback" aria-hidden="true"><?= e(mb_substr($user['display_name'], 0, 1)) ?></span><?php endif; ?>
      <div>
        <h2><?= e($user['display_name']) ?></h2>
        <p><?= e($user['email']) ?></p>
        <form method="post" class="inline-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="profile">
          <input name="display_name" value="<?= e($user['display_name']) ?>" required>
          <button type="submit">Guardar nombre</button>
        </form>
        <form method="post" enctype="multipart/form-data" class="inline-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="avatar">
          <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
          <button type="submit">Cambiar foto</button>
        </form>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="logout">
          <button type="submit">Salir</button>
        </form>
      </div>
    </div>

    <?php
    $byLevel = [];
    foreach ($lessons as $lesson) {
        $byLevel[(int) $lesson['level_id']][] = $lesson;
    }
    foreach ($navLevels as $level):
        $items = $byLevel[(int) $level['id']] ?? [];
        $done = 0;
        foreach ($items as $lesson) {
            if (!empty($progressMap[(int) $lesson['id']]['completed'])) {
                $done++;
            }
        }
        $total = count($items);
        $cert = $certs[(int) $level['id']] ?? null;
    ?>
      <details class="progress-level" <?= (int) $level['id'] === 1 ? 'open' : '' ?>>
        <summary>
          <strong><?= e($level['title']) ?></strong>
          <span><?= $done ?> / <?= $total ?></span>
        </summary>
        <div class="progress-bar" role="meter" aria-valuenow="<?= $done ?>" aria-valuemax="<?= $total ?>"><i style="width: <?= $total ? (int) round($done / $total * 100) : 0 ?>%"></i></div>
        <?php if ($cert): ?>
          <p class="cert">Certificado de pantalla: <strong><?= e($cert['code']) ?></strong></p>
        <?php elseif ($total > 0): ?>
          <p>Te faltan <?= $total - $done ?> clases para el certificado de este nivel.</p>
        <?php endif; ?>
        <ul class="progress-list">
          <?php foreach ($items as $lesson): ?>
            <?php
            $row = $progressMap[(int) $lesson['id']] ?? null;
            $score = $assessMap[(int) $lesson['id']] ?? null;
            ?>
            <li>
              <a href="<?= e(url($level['slug'] . '/clase/' . $lesson['slug'])) ?>"><?= e($lesson['title']) ?></a>
              <span><?= !empty($row['completed']) ? 'Hecha' : 'Pendiente' ?><?= $score ? ' · ' . (int) $score['best'] . '%' : '' ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </details>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
