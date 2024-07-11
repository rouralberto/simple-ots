<?php

global $pdo;
require_once '../common.php';

if (!is_allowed()) {
    echo get_template('not-authorised');
    echo get_template('footer');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { ?>
    <h1>Error</h1>
    <div class="alert alert-danger" role="alert">The method used is not allowed. <a href="/">Go back</a>.</div>
    <?php echo get_template('footer');
    exit;
}

$secret = $_POST['secret'] ?? false;
$expiry = $_POST['expiry'] ?? false;
$uuid   = uuidv4();

if (!$secret || !$expiry) { ?>
    <h1>Error</h1>
    <div class="alert alert-danger" role="alert">Data passed has bad format. <a href="/">Go back</a>.</div>
    <?php echo get_template('footer');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO secrets (uuid, expiry, value) VALUES (?, ?, ?)');
$stmt->execute([$uuid, $expiry, $secret]);

$sharing_url = get_url("?read=$uuid"); ?>
    <h1>Secret created!</h1>
    <div class="alert alert-success" role="alert">
        The secret will be available, once, at <a href="<?php echo $sharing_url; ?>"><?php echo $sharing_url; ?></a>.
    </div>
    <div class="text-center">
        <a href="/" class="btn btn-sm btn-warning me-2"><i class="bi bi-plus-circle me-2"></i>Create New Secret</a>
        <button id="copy-button" class="btn btn-sm btn-success" onclick="copyUrl()">
            <span id="copy-link"><i class="bi bi-copy me-2"></i>Copy Secret Link</span>
            <span id="link-copied" class="d-none"><i class="bi bi-check me-2"></i>Secret Link Copied</span>
        </button>
        <script>
          function copyUrl () {
            navigator.clipboard.writeText('<?php echo $sharing_url; ?>')
              .then(() => {
                document.getElementById('copy-link').classList.add('d-none');
                document.getElementById('link-copied').classList.remove('d-none');
                document.getElementById('copy-button').disabled = true;
              });
          }
        </script>
    </div>
<?php echo get_template('footer');
