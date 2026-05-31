<?php
/**
 * Générateur de page "fiche-vidéo" pour une vidéo YouTube.
 *
 * Objectif : générer un index.html statique, lisible par les humains,
 * les moteurs de recherche et les robots/LLM.
 *
 * Utilisation :
 *   php generate_video_page.php
 *
 * Le script fonctionne même si certains champs sont absents :
 * description, image, liens utiles, branding, canonicalUrl, etc.
 */

declare(strict_types=1);

$video = [
    'title' => '⚙️ VIBE CODING 🌊 : Workflow intégré du futur avec Github, VSCode, Claude 🔥🔥🔥',
    'youtubeUrl' => 'https://youtu.be/tV4RTDLb8kM',
    'imagePath' => __DIR__ . '/ChatGPT Image 7 mai 2026, 20_06_12.png',
    'keywords' => '#devops #github #GitHubCodeSpaces #developpementweb #programmation #visualstudiocode #webdevelopment #vscode #vibecoding #intelligenceartificielle #claudeai #Automation #vscode',
    'description' => '',
    'usefulLinks' => [
        [
            'label' => 'La vidéo tuto GitHub Code Spaces',
            'url' => 'https://youtu.be/M3TU8Lswzno',
        ],
        [
            'label' => 'Le tuto pour héberger une petite appli web sur Github Pages',
            'url' => 'https://youtu.be/ugj44kHM8HI',
        ],
        [
            'label' => 'Le site retro-templates auquel j\'ai ajouté une fonctionnalité dans la vidéo',
            'url' => 'https://agileanddevopstoolkit.github.io/retro-templates/',
        ],
        [
            'label' => 'Le dépôt GitHub du site retro-templates',
            'url' => 'https://github.com/AgileAndDevOpsToolkit/retro-templates',
        ],
        [
            'label' => 'Ma vidéo où je compare la reconnaissance vocale de toutes les IA',
            'url' => 'https://youtu.be/msbehMVDFeA',
        ],
    ],
    'transcriptPath' => __DIR__ . '/Transcript DevOps 04 Workflow Vibe Coding.txt',

    // À renseigner plus tard quand tu auras ces éléments.
    'brandName' => '',
    'brandLogoPath' => '',
    'brandDescription' => '',

    // À renseigner au moment où tu connaîtras l'URL publique finale.
    // Exemple : https://example.com/videos/vibe-coding-workflow-github-vscode-claude/
    'canonicalUrl' => '',

    // Optionnels, mais utiles pour le JSON-LD VideoObject si disponibles.
    'uploadDate' => '', // Format recommandé : YYYY-MM-DD
    'duration' => '',   // Format ISO 8601, exemple : PT28M05S
    'language' => 'fr',

    // Dossier de sortie. Le fichier généré sera outputDir/index.html.
    'outputDir' => __DIR__ . '/fiche-video-vibe-coding',
];

generateVideoPage($video);

function generateVideoPage(array $video): void
{
    $outputDir = $video['outputDir'] ?? (__DIR__ . '/generated-video-page');
    $assetsDir = $outputDir . '/assets';

    ensureDirectory($outputDir);
    ensureDirectory($assetsDir);

    $title = trim((string)($video['title'] ?? 'Vidéo'));
    $youtubeUrl = trim((string)($video['youtubeUrl'] ?? ''));
    $youtubeId = extractYouTubeId($youtubeUrl);
    $embedUrl = $youtubeId ? 'https://www.youtube.com/embed/' . $youtubeId : '';
    $watchUrl = $youtubeId ? 'https://www.youtube.com/watch?v=' . $youtubeId : $youtubeUrl;

    $keywords = normalizeKeywords($video['keywords'] ?? []);
    $description = trim((string)($video['description'] ?? ''));
    $metaDescription = buildMetaDescription($title, $description, $keywords);

    $imageRelativePath = copyImageIfAvailable($video['imagePath'] ?? '', $assetsDir);

    $transcriptRaw = readTextFileIfAvailable($video['transcriptPath'] ?? '');
    $parsedTranscript = parseTranscript($transcriptRaw);
    $chapters = $parsedTranscript['chapters'];

    $usefulLinks = normalizeUsefulLinks($video['usefulLinks'] ?? []);
    $canonicalUrl = trim((string)($video['canonicalUrl'] ?? ''));
    $language = trim((string)($video['language'] ?? 'fr')) ?: 'fr';

    $schema = buildVideoObjectSchema(
        $video,
        $title,
        $metaDescription,
        $watchUrl,
        $embedUrl,
        $canonicalUrl,
        $transcriptRaw,
        $imageRelativePath
    );

    $html = renderHtmlPage([
        'title' => $title,
        'metaDescription' => $metaDescription,
        'canonicalUrl' => $canonicalUrl,
        'language' => $language,
        'youtubeUrl' => $watchUrl,
        'embedUrl' => $embedUrl,
        'imageRelativePath' => $imageRelativePath,
        'keywords' => $keywords,
        'description' => $description,
        'usefulLinks' => $usefulLinks,
        'chapters' => $chapters,
        'schema' => $schema,
        'brandName' => trim((string)($video['brandName'] ?? '')),
        'brandDescription' => trim((string)($video['brandDescription'] ?? '')),
        'transcriptRaw' => $transcriptRaw,
    ]);

    file_put_contents($outputDir . '/index.html', $html);

    echo "Page générée : " . $outputDir . "/index.html\n";
}

function ensureDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function extractYouTubeId(string $url): string
{
    if ($url === '') {
        return '';
    }

    $patterns = [
        '~youtu\.be/([a-zA-Z0-9_-]{6,})~',
        '~youtube\.com/watch\?v=([a-zA-Z0-9_-]{6,})~',
        '~youtube\.com/embed/([a-zA-Z0-9_-]{6,})~',
        '~youtube\.com/shorts/([a-zA-Z0-9_-]{6,})~',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }

    return '';
}

function normalizeKeywords(mixed $keywords): array
{
    if (is_string($keywords)) {
        $keywords = preg_split('/\s+/u', $keywords, -1, PREG_SPLIT_NO_EMPTY);
    }

    if (!is_array($keywords)) {
        return [];
    }

    $result = [];
    $seen = [];

    foreach ($keywords as $keyword) {
        $keyword = trim((string)$keyword);
        $keyword = trim($keyword, " \t\n\r\0\x0B#,");
        if ($keyword === '') {
            continue;
        }
        $key = lowerUtf8($keyword);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $result[] = $keyword;
    }

    return $result;
}


function lowerUtf8(string $text): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($text, 'UTF-8');
    }
    return strtolower($text);
}

function utf8Length(string $text): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($text, 'UTF-8');
    }
    preg_match_all('/./us', $text, $matches);
    return count($matches[0]);
}

function utf8Substr(string $text, int $start, int $length): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($text, $start, $length, 'UTF-8');
    }
    preg_match_all('/./us', $text, $matches);
    return implode('', array_slice($matches[0], $start, $length));
}

function normalizeUsefulLinks(mixed $links): array
{
    if (!is_array($links)) {
        return [];
    }

    $result = [];

    foreach ($links as $link) {
        if (is_array($link)) {
            $label = trim((string)($link['label'] ?? ''));
            $url = trim((string)($link['url'] ?? ''));
        } else {
            $label = '';
            $url = trim((string)$link);
        }

        if ($url === '') {
            continue;
        }

        $result[] = [
            'label' => $label !== '' ? $label : $url,
            'url' => $url,
        ];
    }

    return $result;
}

function buildMetaDescription(string $title, string $description, array $keywords): string
{
    if ($description !== '') {
        return truncateForMeta($description, 160);
    }

    $suffix = $keywords ? ' Thèmes : ' . implode(', ', array_slice($keywords, 0, 6)) . '.' : '';
    return truncateForMeta('Transcript complet de la vidéo : ' . $title . '.' . $suffix, 160);
}

function truncateForMeta(string $text, int $maxLength): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    if (utf8Length($text) <= $maxLength) {
        return $text;
    }

    return rtrim(utf8Substr($text, 0, $maxLength - 1)) . '…';
}

function copyImageIfAvailable(mixed $imagePath, string $assetsDir): string
{
    $imagePath = trim((string)$imagePath);
    if ($imagePath === '' || !is_file($imagePath)) {
        return '';
    }

    $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'];
    if (!in_array($extension, $allowed, true)) {
        return '';
    }

    $targetName = 'illustration.' . $extension;
    copy($imagePath, $assetsDir . '/' . $targetName);

    return 'assets/' . $targetName;
}

function readTextFileIfAvailable(mixed $filePath): string
{
    $filePath = trim((string)$filePath);
    if ($filePath === '' || !is_file($filePath)) {
        return '';
    }

    $content = file_get_contents($filePath);
    return $content === false ? '' : $content;
}

function parseTranscript(string $transcript): array
{
    $lines = preg_split('/\R/u', $transcript) ?: [];
    $chapters = [];
    $currentIndex = -1;
    $pendingTimecode = '';

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        if (preg_match('/^Chapitre\s+\d+\s*:\s*(.+)$/ui', $line, $matches)) {
            $chapters[] = [
                'title' => trim($matches[1]),
                'timecode' => '',
                'segments' => [],
            ];
            $currentIndex = count($chapters) - 1;
            $pendingTimecode = '';
            continue;
        }

        if (isTimecode($line)) {
            $pendingTimecode = $line;
            if ($currentIndex === -1) {
                $chapters[] = [
                    'title' => 'Transcript',
                    'timecode' => $line,
                    'segments' => [],
                ];
                $currentIndex = 0;
            } elseif ($chapters[$currentIndex]['timecode'] === '') {
                $chapters[$currentIndex]['timecode'] = $line;
            }
            continue;
        }

        if (isHumanReadableDurationLine($line)) {
            continue;
        }

        if ($currentIndex === -1) {
            $chapters[] = [
                'title' => 'Transcript',
                'timecode' => $pendingTimecode,
                'segments' => [],
            ];
            $currentIndex = 0;
        }

        $chapters[$currentIndex]['segments'][] = [
            'timecode' => $pendingTimecode,
            'text' => $line,
        ];
        $pendingTimecode = '';
    }

    return ['chapters' => $chapters];
}

function isTimecode(string $line): bool
{
    return (bool)preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $line);
}

function isHumanReadableDurationLine(string $line): bool
{
    return (bool)preg_match('/^\d+\s+(seconde|secondes|minute|minutes|heure|heures)(\s+et\s+\d+\s+(seconde|secondes))?$/ui', $line);
}

function secondsFromTimecode(string $timecode): int
{
    $parts = array_map('intval', explode(':', $timecode));
    if (count($parts) === 2) {
        return $parts[0] * 60 + $parts[1];
    }
    if (count($parts) === 3) {
        return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
    }
    return 0;
}

function youtubeTimeUrl(string $youtubeUrl, string $timecode): string
{
    $youtubeId = extractYouTubeId($youtubeUrl);
    if ($youtubeId === '' || $timecode === '') {
        return $youtubeUrl;
    }

    return 'https://www.youtube.com/watch?v=' . $youtubeId . '&t=' . secondsFromTimecode($timecode) . 's';
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?: '';
    return trim($text, '-') ?: 'section';
}

function absoluteUrlFromCanonical(string $canonicalUrl, string $relativePath): string
{
    if ($canonicalUrl === '' || $relativePath === '') {
        return '';
    }

    return rtrim($canonicalUrl, '/') . '/' . ltrim($relativePath, '/');
}

function buildVideoObjectSchema(
    array $video,
    string $title,
    string $description,
    string $youtubeUrl,
    string $embedUrl,
    string $canonicalUrl,
    string $transcriptRaw,
    string $imageRelativePath
): array {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        'name' => $title,
        'description' => $description,
        'url' => $youtubeUrl,
        'inLanguage' => $video['language'] ?? 'fr',
    ];

    if ($embedUrl !== '') {
        $schema['embedUrl'] = $embedUrl;
    }

    $thumbnailUrl = absoluteUrlFromCanonical($canonicalUrl, $imageRelativePath);
    if ($thumbnailUrl !== '') {
        $schema['thumbnailUrl'] = [$thumbnailUrl];
    }

    $uploadDate = trim((string)($video['uploadDate'] ?? ''));
    if ($uploadDate !== '') {
        $schema['uploadDate'] = $uploadDate;
    }

    $duration = trim((string)($video['duration'] ?? ''));
    if ($duration !== '') {
        $schema['duration'] = $duration;
    }

    $brandName = trim((string)($video['brandName'] ?? ''));
    if ($brandName !== '') {
        $schema['publisher'] = [
            '@type' => 'Organization',
            'name' => $brandName,
        ];
    }

    if ($transcriptRaw !== '') {
        $schema['transcript'] = trim($transcriptRaw);
    }

    return $schema;
}

function renderHtmlPage(array $data): string
{
    $schemaJson = json_encode(
        $data['schema'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );

    ob_start();
    ?>
<!doctype html>
<html lang="<?= h($data['language']) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($data['title']) ?></title>
  <meta name="description" content="<?= h($data['metaDescription']) ?>">
  <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<?php if ($data['canonicalUrl'] !== ''): ?>
  <link rel="canonical" href="<?= h($data['canonicalUrl']) ?>">
  <meta property="og:url" content="<?= h($data['canonicalUrl']) ?>">
<?php endif; ?>
  <meta property="og:type" content="video.other">
  <meta property="og:title" content="<?= h($data['title']) ?>">
  <meta property="og:description" content="<?= h($data['metaDescription']) ?>">
<?php if ($data['imageRelativePath'] !== ''): ?>
  <meta property="og:image" content="<?= h($data['imageRelativePath']) ?>">
<?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <script type="application/ld+json"><?= $schemaJson ?></script>
  <style>
    :root {
      --text: #162033;
      --muted: #5f6b7a;
      --background: #f7f9fc;
      --surface: #ffffff;
      --border: #dfe5ee;
      --accent: #1d6fdc;
      --accent-soft: #e9f2ff;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      background: var(--background);
      color: var(--text);
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      line-height: 1.65;
    }

    .page {
      max-width: 960px;
      margin: 0 auto;
      padding: 32px 20px 64px;
    }

    article {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 32px;
      box-shadow: 0 16px 40px rgba(22, 32, 51, 0.06);
    }

    h1 {
      margin: 0 0 18px;
      font-size: clamp(2rem, 4vw, 3.2rem);
      line-height: 1.08;
      letter-spacing: -0.04em;
    }

    h2 {
      margin-top: 44px;
      padding-top: 28px;
      border-top: 1px solid var(--border);
      font-size: 1.55rem;
    }

    h3 {
      margin-top: 28px;
      font-size: 1.15rem;
    }

    a {
      color: var(--accent);
    }

    .intro,
    .metadata,
    .links,
    .brand-block {
      background: var(--accent-soft);
      border: 1px solid #cfe3ff;
      border-radius: 14px;
      padding: 18px 20px;
      margin: 24px 0;
    }

    .hero-image {
      margin: 28px 0;
      padding: 20px;
      text-align: center;
      background: #f1f5fb;
      border: 1px solid var(--border);
      border-radius: 18px;
    }

    .hero-image img {
      max-width: 100%;
      height: auto;
    }

    .video-wrapper {
      position: relative;
      width: 100%;
      aspect-ratio: 16 / 9;
      margin: 24px 0;
      overflow: hidden;
      border-radius: 16px;
      background: #000;
    }

    .video-wrapper iframe {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      border: 0;
    }

    .tags {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      padding: 0;
      margin: 12px 0 0;
      list-style: none;
    }

    .tag {
      display: inline-flex;
      align-items: center;
      border: 1px solid var(--border);
      border-radius: 999px;
      padding: 4px 10px;
      background: #fff;
      color: var(--muted);
      font-size: 0.92rem;
    }

    .chapter-list {
      padding-left: 1.4rem;
    }

    .transcript-segment {
      margin: 0 0 16px;
      padding: 14px 16px;
      border-left: 4px solid var(--border);
      background: #fbfcff;
      border-radius: 0 10px 10px 0;
    }

    .timecode {
      display: inline-block;
      min-width: 58px;
      margin-right: 8px;
      font-variant-numeric: tabular-nums;
      font-weight: 700;
      text-decoration: none;
    }

    .muted {
      color: var(--muted);
    }

    @media (max-width: 700px) {
      .page {
        padding: 16px 12px 40px;
      }

      article {
        padding: 22px 16px;
        border-radius: 12px;
      }
    }
  </style>
</head>
<body>
  <main class="page">
    <article>
      <h1><?= h($data['title']) ?></h1>

<?php if ($data['description'] !== ''): ?>
      <section class="intro" aria-labelledby="resume-video">
        <h2 id="resume-video">Résumé de la vidéo</h2>
        <p><?= nl2br(h($data['description'])) ?></p>
      </section>
<?php endif; ?>

<?php if ($data['imageRelativePath'] !== ''): ?>
      <figure class="hero-image">
        <img src="<?= h($data['imageRelativePath']) ?>" alt="Illustration de la vidéo : <?= h($data['title']) ?>">
      </figure>
<?php endif; ?>

<?php if ($data['embedUrl'] !== ''): ?>
      <section aria-labelledby="video-youtube">
        <h2 id="video-youtube">Vidéo YouTube</h2>
        <div class="video-wrapper">
          <iframe src="<?= h($data['embedUrl']) ?>" title="<?= h($data['title']) ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
        </div>
        <p><a href="<?= h($data['youtubeUrl']) ?>">Voir la vidéo directement sur YouTube</a></p>
      </section>
<?php elseif ($data['youtubeUrl'] !== ''): ?>
      <p><a href="<?= h($data['youtubeUrl']) ?>">Voir la vidéo sur YouTube</a></p>
<?php endif; ?>

<?php if (!empty($data['keywords'])): ?>
      <section class="metadata" aria-labelledby="themes-video">
        <h2 id="themes-video">Thèmes et mots-clés</h2>
        <ul class="tags">
<?php foreach ($data['keywords'] as $keyword): ?>
          <li class="tag">#<?= h($keyword) ?></li>
<?php endforeach; ?>
        </ul>
      </section>
<?php endif; ?>

<?php if (!empty($data['chapters'])): ?>
      <section aria-labelledby="chapitres-video">
        <h2 id="chapitres-video">Chapitres</h2>
        <ol class="chapter-list">
<?php foreach ($data['chapters'] as $index => $chapter): ?>
          <li>
            <a href="#<?= h('chapitre-' . ($index + 1) . '-' . slugify($chapter['title'])) ?>"><?= h($chapter['title']) ?></a>
<?php if ($chapter['timecode'] !== ''): ?>
            <span class="muted">— <?= h($chapter['timecode']) ?></span>
<?php endif; ?>
          </li>
<?php endforeach; ?>
        </ol>
      </section>
<?php endif; ?>

<?php if (!empty($data['usefulLinks'])): ?>
      <section class="links" aria-labelledby="liens-utiles-haut">
        <h2 id="liens-utiles-haut">Liens utiles</h2>
        <ul>
<?php foreach ($data['usefulLinks'] as $link): ?>
          <li><a href="<?= h($link['url']) ?>"><?= h($link['label']) ?></a></li>
<?php endforeach; ?>
        </ul>
      </section>
<?php endif; ?>

<?php if (!empty($data['chapters'])): ?>
      <section aria-labelledby="transcript-complet">
        <h2 id="transcript-complet">Transcript complet</h2>
<?php foreach ($data['chapters'] as $index => $chapter): ?>
        <section id="<?= h('chapitre-' . ($index + 1) . '-' . slugify($chapter['title'])) ?>">
          <h3><?= h($chapter['title']) ?></h3>
<?php foreach ($chapter['segments'] as $segment): ?>
          <p class="transcript-segment">
<?php if ($segment['timecode'] !== ''): ?>
            <a class="timecode" href="<?= h(youtubeTimeUrl($data['youtubeUrl'], $segment['timecode'])) ?>"><?= h($segment['timecode']) ?></a>
<?php endif; ?>
            <?= h($segment['text']) ?>
          </p>
<?php endforeach; ?>
        </section>
<?php endforeach; ?>
      </section>
<?php elseif ($data['transcriptRaw'] !== ''): ?>
      <section aria-labelledby="transcript-complet">
        <h2 id="transcript-complet">Transcript complet</h2>
        <p><?= nl2br(h($data['transcriptRaw'])) ?></p>
      </section>
<?php endif; ?>

<?php if (!empty($data['usefulLinks'])): ?>
      <section class="links" aria-labelledby="liens-utiles-bas">
        <h2 id="liens-utiles-bas">Liens complémentaires</h2>
        <ul>
<?php foreach ($data['usefulLinks'] as $link): ?>
          <li><a href="<?= h($link['url']) ?>"><?= h($link['label']) ?></a></li>
<?php endforeach; ?>
        </ul>
      </section>
<?php endif; ?>

<?php if ($data['brandName'] !== '' || $data['brandDescription'] !== ''): ?>
      <section class="brand-block" aria-labelledby="a-propos">
        <h2 id="a-propos">À propos<?= $data['brandName'] !== '' ? ' de ' . h($data['brandName']) : '' ?></h2>
<?php if ($data['brandDescription'] !== ''): ?>
        <p><?= nl2br(h($data['brandDescription'])) ?></p>
<?php endif; ?>
      </section>
<?php endif; ?>
    </article>
  </main>
</body>
</html>
    <?php
    return trim((string)ob_get_clean()) . "\n";
}
