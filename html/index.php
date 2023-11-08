<?php

global $pdo;
require_once 'common.php';

if (isset($_GET['read'])) {
    $uuid       = $_GET['read'];
    $secret_url = get_url("?uuid=$uuid"); ?>
    <div class="row">
        <div class="col-md-8 offset-md-2 text-center">
            <h1><?php echo $uuid; ?></h1>
            <p>Click to see the secret: <a href="<?php echo $secret_url; ?>"><?php echo $secret_url; ?></a>.</p>
        </div>
    </div>
    <?php
    exit;
}

if (isset($_GET['uuid'])) {
    $uuid = $_GET['uuid'];

    if (!is_valid_uuidv4($uuid)) {
        echo get_template('not-found');
        echo get_template('footer');
        exit;
    }

    $stmt = $pdo->prepare('SELECT value, createdAt, expiry FROM secrets WHERE uuid = ?');
    $stmt->execute([$uuid]);
    $secret      = $stmt->fetch();
    $validSecret = false;

    if ($secret) {
        $createdAt   = strtotime($secret['createdAt']);
        $expiry      = $secret['expiry'];
        $remaining   = $createdAt + $expiry - date('U');
        $validSecret = $remaining >= 0;

        $stmt = $pdo->prepare('DELETE FROM secrets WHERE uuid = ?');
        $stmt->execute([$uuid]);
    }

    if ($validSecret) { ?>
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h1 class="text-center"><?php echo $uuid; ?></h1>
                <h2 class="h5 small text-muted text-center">(This will only be shown once)</h2>
                <textarea class="form-control mt-4" rows="3"><?php echo $secret['value']; ?></textarea>
            </div>
        </div>

        <?php
    } else {
        echo get_template('not-found');
    }
    echo get_template('footer');
    exit;
}

if (!is_allowed()) {
    echo get_template('not-authorised');
    echo get_template('footer');
    exit;
}

echo get_template('new-secret');
echo get_template('footer');
