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
    <div class="alert alert-danger" role="alert">The method used is not allowed.</div>
    <?php echo get_template('footer');
    exit;
}

$secret = $_POST['secret'] ?? false;
$expiry = $_POST['expiry'] ?? false;
$uuid   = uuidv4();

if (!$secret || !$expiry) { ?>
    <h1>Error</h1>
    <div class="alert alert-danger" role="alert">Data passed has bad format.</div>
    <?php echo get_template('footer');
    exit;
}

$stmt = $pdo->prepare('INSERT INTO secrets (uuid, expiry, value) VALUES (?, ?, ?)');
$stmt->execute([$uuid, $expiry, $secret]);

$secret_url = get_url("?uuid=$uuid"); ?>
    <h1>Secret created!</h1>
    <div class="alert alert-success" role="alert">
        The secret will be available, only once, at <a href="<?php echo $secret_url; ?>"><?php echo $secret_url; ?></a>.
    </div>
<?php echo get_template('footer');
