<section class="home-hero">
  <div class="home-hero-media" aria-hidden="true">
    <img class="home-hero-img" src="<?= e(url('assets/flag.svg')) ?>" alt="">
  </div>
  <div class="home-hero-scrim" aria-hidden="true"></div>
  <div class="home-hero-inner">
    <p class="eyebrow light">汉语 · Hànyǔ</p>
    <h1 class="home-hero-brand"><span class="hero-es">Aprendiendo</span><span class="hero-lang">Pǔtōnghuà</span></h1>
    <p class="home-hero-line">Clases, audios y un diccionario con las palabras de los 9 niveles del HSK.</p>
    <div class="home-hero-actions">
      <a class="primary-btn" href="#clases">Ver clases</a>
    </div>
  </div>
</section>

<div class="home-direct">
  <section class="how-strip" aria-label="Cómo usar el sitio">
    <?php
    $steps = [
        ['01', 'Elegí un nivel', 'El HSK 1 es el punto de partida.'],
        ['02', 'Leé y escuchá', 'Cada palabra y cada frase tiene su audio.'],
        ['03', 'Practicá', 'Escribí los caracteres y hacé la autoevaluación.'],
        ['04', 'Buscá', 'El diccionario cubre los 9 niveles.'],
    ];
    foreach ($steps as $index => [$step, $stepTitle, $stepBody]): ?>
      <article style="--i: <?= $index ?>">
        <span><?= e($step) ?></span>
        <strong><?= e($stepTitle) ?></strong>
        <p><?= e($stepBody) ?></p>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="system" id="sistema">
    <p class="eyebrow">El sistema</p>
    <h2>Nueve niveles, tres etapas</h2>
    <p class="lede">HSK quiere decir Hànyǔ Shuǐpíng Kǎoshì: el examen de nivel de chino. Ordena el idioma en nueve niveles y tres etapas. Los anteriores eran seis. Del 1 al 6 cada nivel tiene examen propio. El 7, el 8 y el 9 comparten un solo examen y una sola lista: en el examen oficial, la nota decide el certificado. En este sitio esa lista está partida en tres bloques de estudio, de tamaño parecido, para cursarlos en orden.</p>
    <p class="cert-note">Al completar todas las clases de un nivel, el certificado queda en Certificados. No tiene validez oficial: solo deja constancia de que terminaste ese nivel acá.</p>
    <div class="stage-grid">
      <article>
        <p class="stage-kicker">01</p>
        <h3>Etapa inicial</h3>
        <p>HSK 1, 2 y 3. De 300 a 1000 palabras. Desde el 3, el oral entra en el examen.</p>
      </article>
      <article>
        <p class="stage-kicker">02</p>
        <h3>Etapa media</h3>
        <p>HSK 4, 5 y 6. De 2000 a 5400 palabras acumuladas.</p>
      </article>
      <article>
        <p class="stage-kicker">03</p>
        <h3>Etapa avanzada</h3>
        <p>HSK 7, 8 y 9. Un examen para los tres y 11000 palabras al final del recorrido.</p>
      </article>
    </div>
  </section>

  <section class="home-classes" id="clases">
    <div class="home-classes-head">
      <div>
        <p class="eyebrow">Recorrido</p>
        <h2>Los nueve HSK</h2>
      </div>
    </div>
    <?php
    $grouped = [];
    foreach ($levels as $level) {
        $grouped[$level['stage_label']][] = $level;
    }
    foreach ($grouped as $label => $items): ?>
      <div class="level-cut level-board">
        <div class="level-cut-head">
          <div>
            <p class="level-cut-label">Etapa</p>
            <h3><?= e($label) ?></h3>
          </div>
        </div>
        <div class="level-grid">
          <?php foreach ($items as $level): ?>
            <a class="level-card" href="<?= e(url($level['slug'])) ?>">
              <span class="level-card-kicker"><?= e($level['title']) ?></span>
              <strong><?= (int) ($lessonCounts[(int) $level['id']] ?? 0) ?> clases</strong>
              <p><?= e($level['description']) ?></p>
              <em><?= number_format((int) $level['words_cumulative'], 0, ',', '.') ?> palabras acumuladas</em>
              <span class="class-cta">Abrir nivel →</span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </section>
</div>
