<?php

declare(strict_types=1);

// Strings are trusted markup: templates insert them unescaped, so they may contain HTML.
// {{NAME}} placeholders are filled in by t() or get_template(). Keys starting with "js." are sent to app.js.
return [
    'lang'   => 'en',
    'footer' => '— open-source, self-hosted, end-to-end encrypted.',
    'nav.language' => 'Language',

    'duration.days_hours'    => '{{D}}d {{H}}h',
    'duration.days'          => '{{D}}d',
    'duration.hours_minutes' => '{{H}}h {{M}}min',
    'duration.hours'         => '{{H}}h',
    'duration.minutes'       => '{{M}}min',

    'error.rate_limit.title'   => 'Too many requests',
    'error.rate_limit.message' => 'You have made too many requests. Please wait a few minutes before trying again.',
    'error.back'               => 'Back to start',

    'api.method_not_allowed' => 'Method not allowed.',
    'api.rate_limit'         => 'Too many requests. Please wait a few minutes before trying again.',
    'api.secret_gone'        => 'This secret has already been viewed or has expired.',
    'api.not_authorised'     => 'You are not authorised to create secrets.',
    'api.session_expired'    => 'Your session has expired. Reload the page and try again.',
    'api.invalid_secret'     => 'The secret is missing, too large, or not encrypted.',
    'api.invalid_expiry'     => 'Invalid expiry value.',

    'secret.label' => 'Secret',

    'create.noscript'           => 'JavaScript is required: secrets are encrypted in your browser before they are sent.',
    'create.title'              => 'Share a secret',
    'create.intro'              => 'Create a link that can be opened only once. After it has been viewed, or once it expires, the secret is gone for good.',
    'create.secret_placeholder' => 'Password, API key, private note…',
    'create.secret_required'    => 'Enter the secret you want to share.',
    'create.expiry_label'       => 'Expires after',
    'create.expiry.hour'        => '1 hour',
    'create.expiry.day'         => '1 day',
    'create.expiry.week'        => '1 week',
    'create.submit'             => 'Create secret link',
    'create.zero_knowledge'     => 'Your secret is encrypted in your browser before being sent. The server never sees the plaintext.',

    'created.title'      => 'Secret link created',
    'created.intro'      => 'Send this link to the recipient. It works <strong>once</strong> and expires in <span id="created-expiry"></span>.',
    'created.link_label' => 'Secret link',
    'created.copy'       => 'Copy link',
    'created.key_hint'   => 'The part after <code>#</code> is the decryption key. It is never sent to the server, so share the whole link. Don\'t open it yourself, or the secret will be used up.',
    'created.another'    => 'Create another secret',

    'not_authorised.title'   => 'Not authorised',
    'not_authorised.message' => 'Creating secrets is restricted on this server. If someone shared a secret link with you, open that link directly.',

    'not_found.title'   => 'Secret not available',
    'not_found.message' => 'This secret has already been viewed, has expired, or never existed. Secrets can only be opened once — ask the sender to share a new one.',
    'not_found.create'  => 'Create a secret',

    'reveal.title'       => 'Someone shared a secret with you',
    'reveal.intro'       => 'This secret can be viewed <strong>only once</strong>. As soon as you reveal it, it is permanently deleted from the server, so make sure you are ready to copy or save it.',
    'reveal.expires'     => 'Expires in {{REMAINING}} if not opened.',
    'reveal.key_missing' => 'This link is incomplete: the decryption key (the part after <code>#</code>) is missing or damaged. The secret has <strong>not</strong> been opened. Ask the sender for the full link.',
    'reveal.button'      => 'Reveal secret',
    'reveal.deleted'     => 'This secret has been deleted from the server. Copy it now — it will not be shown again.',
    'reveal.decrypted'   => 'Decrypted in your browser. The server never saw the plaintext.',
    'reveal.copy'        => 'Copy secret',

    'js.insecure_context'        => 'Encryption is unavailable because this page is not served over HTTPS. Browsers only provide the Web Crypto API on secure origins.',
    'js.insecure_context_reveal' => 'Encryption is unavailable because this page is not served over HTTPS. Browsers only provide the Web Crypto API on secure origins. The secret has not been opened.',
    'js.copied'                  => 'Copied!',
    'js.copy_manually'           => 'Press Ctrl+C to copy',
    'js.encrypting'              => 'Encrypting…',
    'js.decrypting'              => 'Decrypting…',
    'js.request_failed'          => 'Request failed ({{STATUS}}). Please try again.',
    'js.generic_error'           => 'Something went wrong. Please try again.',
    'js.network_error'           => 'Could not reach the server. The secret has not been opened; please try again.',
    'js.decrypt_failed'          => 'The secret could not be decrypted: the key in this link does not match. The secret has been deleted; ask the sender to create a new one.',
];
