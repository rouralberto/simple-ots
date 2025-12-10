<?php

global $pdo;
require_once 'common.php';

if (!check_rate_limit()) {
    show_rate_limit_error();
}

if (isset($_GET['read'])) {
    $uuid = $_GET['read'];

    if (!is_valid_uuidv4($uuid)) {
        echo get_template('not-found');
        echo get_template('footer');
        exit;
    }

    $secret      = get_secret($uuid, false);
    $secret_url  = get_url("?uuid=$uuid");
    $sharing_url = get_url("?read=$uuid");

    if ($secret) { ?>
        <div class="row">
            <div class="col-md-8 offset-md-2 text-center">
                <h1><?php echo htmlspecialchars($uuid); ?></h1>
                <p>Click to see the secret: <a id="secret-link" href="<?php echo htmlspecialchars($secret_url); ?>"><?php echo htmlspecialchars($secret_url); ?></a></p>

                <button id="copy-button" class="btn btn-sm btn-success" onclick="copyUrl()">
                    <span id="copy-link"><i class="bi bi-copy me-2"></i>Copy Secret Link</span>
                    <span id="link-copied" class="d-none"><i class="bi bi-check me-2"></i>Secret Link Copied</span>
                </button>
                <script>
                  (function() {
                    const baseSecretUrl = <?php echo json_encode($secret_url); ?>;
                    const baseSharingUrl = <?php echo json_encode($sharing_url); ?>;
                    const keyFragment = window.location.hash;
                    const finalSecretUrl = baseSecretUrl + keyFragment;
                    const finalSharingUrl = baseSharingUrl + keyFragment;
                    const secretLink = document.getElementById('secret-link');
                    secretLink.href = finalSecretUrl;
                    secretLink.textContent = finalSecretUrl;

                    window.copyUrl = function() {
                      navigator.clipboard.writeText(finalSharingUrl)
                        .then(() => {
                          document.getElementById('copy-link').classList.add('d-none');
                          document.getElementById('link-copied').classList.remove('d-none');
                          document.getElementById('copy-button').disabled = true;
                        });
                    };
                  })();
                </script>

                <hr>

                <p class="small text-muted mb-1">(Remember: If you open the secret, it will instantly disappear)</p>

                <p class="small text-muted">
                    Remaining time: <code><?php echo get_remaining_time($secret['createdAt'], $secret['expiry']); ?></code>
                </p>

            </div>
        </div>
        <?php
    } else {
        echo get_template('not-found');
    }
    echo get_template('footer');
    exit;
}

if (isset($_GET['uuid'])) {
    $uuid = $_GET['uuid'];

    if (!is_valid_uuidv4($uuid)) {
        echo get_template('not-found');
        echo get_template('footer');
        exit;
    }

    $secret = get_secret($uuid, true);

    if ($secret) { ?>
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h1 class="text-center"><?php echo htmlspecialchars($uuid); ?></h1>
                <h2 class="h5 small text-muted text-center">(This will only be shown once)</h2>

                <label style="display: none" for="theSecret">Secret Contents:</label>
                <div class="position-relative">
                    <textarea id="theSecret" class="form-control mt-4" rows="3" style="padding-right: 45px;"></textarea>
                    <button id="copy-secret-button" class="btn btn-sm btn-success position-absolute top-0 end-0 mt-4 me-2" onclick="copySecret()">
                        <span id="copy-secret"><i class="bi bi-copy"></i></span>
                        <span id="secret-copied" class="d-none"><i class="bi bi-check"></i></span>
                    </button>
                </div>

                <p class="small text-muted mt-2">
                    <i class="bi bi-shield-lock me-1"></i>
                    This secret was decrypted in your browser. The server never saw the plaintext.
                </p>
            </div>
        </div>
        <script type="application/javascript">
          (async function() {
            const jsConfetti = new JSConfetti();
            const secretTextarea = document.getElementById('theSecret');
            const encryptedData = <?php echo json_encode($secret['value']); ?>;
            const keyFragment = window.location.hash.slice(1);
            const key = await OTSCrypto.importKey(keyFragment);
            const plaintext = await OTSCrypto.decrypt(encryptedData, key);

            secretTextarea.value = plaintext;

            if (history.replaceState) {
              history.replaceState(null, null, window.location.pathname + window.location.search);
            }

            jsConfetti.addConfetti({emojis: ['💥', '🦵', '💣', '🤯', '🔥']});

            window.copySecret = function() {
              navigator.clipboard.writeText(secretTextarea.value)
                .then(() => {
                  document.getElementById('copy-secret').classList.add('d-none');
                  document.getElementById('secret-copied').classList.remove('d-none');
                  document.getElementById('copy-secret-button').disabled = true;
                });
            };
          })();
        </script>
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

$csrf_token = generate_csrf_token();
$new_secret_template = get_template('new-secret');
$new_secret_template = str_replace('{{CSRF_TOKEN}}', htmlspecialchars($csrf_token), $new_secret_template);
echo $new_secret_template;

echo get_template('footer');
