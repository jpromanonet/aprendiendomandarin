<?php
$stages = [];
foreach ($levels as $level) {
    $stages[$level['stage_label']][] = $level;
}
$here = request_path();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pǔtōnghuà — <?= e($title) ?></title>
  <link rel="icon" href="<?= e(url('assets/favicon.svg')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@500;700&family=Ubuntu:ital,wght@0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body>
  <div class="app-shell">
    <header class="site-header">
      <a class="brand" href="<?= e(url()) ?>">
        <span class="brand-mark" aria-hidden="true">汉</span>
        <span class="brand-text">
          <strong>汉语</strong>
          <em>Aprendiendo mandarín</em>
        </span>
      </a>
      <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="site-nav">
        <span class="menu-toggle-bars" aria-hidden="true"><i></i><i></i><i></i></span>
        <span class="visually-hidden">Menú</span>
      </button>
      <button type="button" class="nav-backdrop" hidden></button>
      <nav class="site-nav" id="site-nav" aria-label="Principal">
        <div class="mobile-nav-top">
          <p class="mobile-nav-kicker">Navegación</p>
          <strong class="mobile-nav-title">¿Qué querés hacer?</strong>
        </div>
        <div class="mobile-nav-links">
          <div class="nav-cluster">
            <details class="nav-details<?= str_starts_with($here, 'hsk-') ? ' is-on' : '' ?>">
              <summary class="nav-pill nav-pill-clases">
                <span class="nav-pill-label"><strong>Clases</strong><em>Nueve niveles</em></span>
                <svg class="nav-chevron" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path d="M4 6l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </summary>
              <div class="nav-stages">
                <?php foreach ($stages as $label => $items): ?>
                  <?php $stage = (string) ($items[0]['stage'] ?? ''); ?>
                  <div class="nav-stage nav-stage-<?= e($stage) ?>">
                    <p><?= e($label) ?></p>
                    <?php foreach ($items as $item): ?>
                      <a class="<?= str_starts_with($here, (string) $item['slug']) ? 'is-on' : '' ?>" href="<?= e(url($item['slug'])) ?>"><?= e($item['title']) ?></a>
                    <?php endforeach; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </details>
            <a class="nav-pill nav-pill-dict<?= str_starts_with($here, 'diccionario') ? ' is-on' : '' ?>" href="<?= e(url('diccionario')) ?>">
              <span class="nav-pill-label"><strong>Diccionario</strong><em>Buscar palabras</em></span>
            </a>
            <a class="nav-pill nav-pill-libros<?= str_starts_with($here, 'libros') ? ' is-on' : '' ?>" href="<?= e(url('libros')) ?>">
              <span class="nav-pill-label"><strong>Libros</strong><em>Lecturas</em></span>
            </a>
          </div>
          <div class="nav-account-slot">
            <?php $navPhoto = $user ? avatar_src($user) : ''; ?>
            <a class="nav-pill nav-pill-account<?= $user ? ' has-avatar' : '' ?><?= str_starts_with($here, 'cuenta') || $here === 'certificados' ? ' is-on' : '' ?>" href="<?= e(url('cuenta')) ?>">
              <?php if ($navPhoto !== ''): ?>
                <img class="nav-avatar" src="<?= e($navPhoto) ?>" alt="">
              <?php elseif ($user): ?>
                <span class="nav-avatar nav-avatar-fallback" aria-hidden="true"><?= e(mb_substr($user['display_name'], 0, 1)) ?></span>
              <?php endif; ?>
              <span class="nav-pill-label"><strong><?= $user ? 'Perfil' : 'Cuenta' ?></strong><em><?= $user ? 'Tu cuenta' : 'Entrar' ?></em></span>
            </a>
          </div>
        </div>
        <div class="mobile-nav-foot">
          <p>Hecho con amor <span aria-hidden="true">♥</span></p>
          <a href="https://github.com/jpromanonet/aprendiendomandarin" target="_blank" rel="noreferrer">Ver el repo</a>
        </div>
      </nav>
    </header>
    <main>
      <?php if (!empty($flash)): ?>
        <p class="flash"><?= e($flash) ?></p>
      <?php endif; ?>
      <?= $content ?>
    </main>
    <footer class="site-footer">
      <div class="footer-inner">
        <div class="footer-meta">
          <p class="footer-love">Hecho con amor <span class="footer-heart" aria-hidden="true">♥</span></p>
          <a class="footer-repo" href="https://github.com/jpromanonet/aprendiendomandarin" target="_blank" rel="noreferrer">Ver el repo en GitHub</a>
          <div class="footer-icons" aria-label="Redes">
            <a href="https://x.com/jpromanonet" target="_blank" rel="noreferrer" aria-label="X" title="X">
              <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.727-8.835L1.254 2.25H8.08l4.253 5.622L18.244 2.25Zm-1.161 17.52h1.833L7.084 4.126H5.117l11.966 15.644Z"/></svg>
            </a>
            <a href="https://instagram.com/jpromanonet" target="_blank" rel="noreferrer" aria-label="Instagram" title="Instagram">
              <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5A4.25 4.25 0 0 0 7.75 20.5h8.5a4.25 4.25 0 0 0 4.25-4.25v-8.5A4.25 4.25 0 0 0 16.25 3.5h-8.5ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm5.25-.88a1.13 1.13 0 1 1 0 2.25 1.13 1.13 0 0 1 0-2.25Z"/></svg>
            </a>
            <a href="https://linkedin.com/in/jpromanonet" target="_blank" rel="noreferrer" aria-label="LinkedIn" title="LinkedIn">
              <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M6.94 6.5A1.94 1.94 0 1 1 6.94 2.6a1.94 1.94 0 0 1 0 3.88ZM4.75 8.75h4.4V21.4h-4.4V8.75Zm7.05 0h4.22v1.73h.06c.59-1.12 2.03-2.3 4.18-2.3 4.47 0 5.3 2.94 5.3 6.76V21.4h-4.4v-5.92c0-1.41-.03-3.23-1.97-3.23-1.97 0-2.27 1.54-2.27 3.13V21.4h-4.12V8.75Z"/></svg>
            </a>
            <a href="https://medium.com/@jpromanonet" target="_blank" rel="noreferrer" aria-label="Medium" title="Medium">
              <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M4.42 7.26c.05-.45-.13-.88-.5-1.13L1.5 4.2v-.45h6.73l5.2 11.4L17.9 3.75h6.42v.45l-2.05 1.96a.72.72 0 0 0-.27.67v10.5c.05.28.16.5.32.67l2 2.03v.45h-9.97v-.45l2.07-2.01c.2-.2.2-.26.2-.67V9.12l-5.76 14.63h-.78L4.2 9.12v9.83c-.06.46.1.93.45 1.26l2.7 3.28v.45H1.2v-.45l2.7-3.28c.34-.34.49-.82.42-1.26V7.26Z"/></svg>
            </a>
            <a href="https://jpromano.net" target="_blank" rel="noreferrer" aria-label="Sitio web" title="Sitio web">
              <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20Zm0 1.5a8.5 8.5 0 0 0-8.4 7.25h3.1c.2-2.45.95-4.55 2.05-5.95A8.47 8.47 0 0 0 12 3.5Zm0 17a8.47 8.47 0 0 0 3.25-.65c-1.1-1.4-1.85-3.5-2.05-5.95h-2.4c-.2 2.45-.95 4.55-2.05 5.95A8.47 8.47 0 0 0 12 20.5Zm-3.5-8.25H4.1A8.5 8.5 0 0 0 12 20.5a8.47 8.47 0 0 0 3.25-.65c-1.2-1.55-2-3.85-2.2-6.6H8.5Zm4.55 0c.2 2.75 1 5.05 2.2 6.6A8.5 8.5 0 0 0 19.9 12.25h-6.85Zm6.85-1.5A8.5 8.5 0 0 0 12 3.5a8.47 8.47 0 0 0-3.25.65c1.2 1.55 2 3.85 2.2 6.6h6.95Z"/></svg>
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div>
  <button type="button" class="scroll-top" aria-label="Volver arriba" title="Volver arriba">
    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M6 14l6-6 6 6"/></svg>
  </button>
  <script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
