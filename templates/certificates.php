<header class="page-hero">
  <div class="page-hero-inner">
    <p class="eyebrow light">Niveles y cierre del curso</p>
    <h1>Certificados</h1>
    <p class="lede">Cada nivel tiene el suyo. El del curso completo se emite cuando están todos.</p>
    <a class="text-link on-dark" href="<?= e(url('cuenta')) ?>">← Volver al perfil</a>
  </div>
</header>

<section class="cert-page">
  <?php if (!$user): ?>
    <section class="profile-panel cert-panel">
      <p class="lede"><a href="<?= e(url('cuenta')) ?>">Ingresá</a> para ver tus certificados.</p>
    </section>
  <?php else: ?>
    <?php $earned = count($certs); ?>
    <section class="profile-panel cert-panel" aria-label="Certificados">
      <div class="cert-grid">
        <?php foreach ($navLevels as $level): ?>
          <?php
          $issued = $certs[(int) $level['id']] ?? null;
          $stat = $levelStats[(int) $level['id']] ?? ['done' => 0, 'total' => 0];
          ?>
          <?php $cardTag = $issued ? 'a' : 'article'; ?>
          <<?= $cardTag ?> class="cert-card<?= $issued ? ' earned' : '' ?>"<?= $issued ? ' href="' . e(url('certificados/nivel/' . (int) $level['id'])) . '"' : '' ?>>
            <span class="cert-kicker"><?= $issued ? 'Emitido' : 'Pendiente' ?></span>
            <strong><?= e($level['title']) ?></strong>
            <?php if ($issued): ?>
              <em><?= e(fecha_es((string) $issued['issued_at'])) ?></em>
              <span class="cert-code"><?= e($issued['code']) ?></span>
              <span class="cert-open">Abrir PDF</span>
            <?php elseif ((int) $stat['total'] > 0): ?>
              <em><?= (int) $stat['done'] ?> de <?= (int) $stat['total'] ?> clases</em>
            <?php else: ?>
              <em>Cuando se publique este nivel</em>
            <?php endif; ?>
          </<?= $cardTag ?>>
        <?php endforeach; ?>
        <?php $finalTag = $finalCert ? 'a' : 'article'; ?>
        <<?= $finalTag ?> class="cert-card cert-final<?= $finalCert ? ' earned' : '' ?>"<?= $finalCert ? ' href="' . e(url('certificados/final')) . '"' : '' ?>>
          <span class="cert-kicker"><?= $finalCert ? 'Emitido' : 'Pendiente' ?></span>
          <strong>Curso completo</strong>
          <?php if ($finalCert): ?>
            <em><?= e(fecha_es((string) $finalCert['issued_at'])) ?></em>
            <span class="cert-code"><?= e($finalCert['code']) ?></span>
            <span class="cert-open">Abrir PDF</span>
          <?php else: ?>
            <em>Se emite al tener los certificados de todos los niveles. <?= $earned ?> de <?= count($navLevels) ?>.</em>
          <?php endif; ?>
        </<?= $finalTag ?>>
      </div>
    </section>
  <?php endif; ?>
</section>
