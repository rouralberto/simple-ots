<?php

declare(strict_types=1);

// Simplified Chinese, served for every zh-* browser locale.
return [
    'lang'   => 'zh-Hans',
    'footer' => '— 开源、可自托管、端到端加密。',

    'duration.days_hours'    => '{{D}}天{{H}}小时',
    'duration.days'          => '{{D}}天',
    'duration.hours_minutes' => '{{H}}小时{{M}}分钟',
    'duration.hours'         => '{{H}}小时',
    'duration.minutes'       => '{{M}}分钟',

    'error.rate_limit.title'   => '请求过多',
    'error.rate_limit.message' => '您的请求过于频繁，请等待几分钟后再试。',
    'error.back'               => '返回首页',

    'api.method_not_allowed' => '不允许的请求方法。',
    'api.rate_limit'         => '请求过多，请等待几分钟后再试。',
    'api.secret_gone'        => '此秘密已被查看或已过期。',
    'api.not_authorised'     => '您无权创建秘密。',
    'api.session_expired'    => '您的会话已过期，请刷新页面后重试。',
    'api.invalid_secret'     => '秘密内容缺失、过大或未加密。',
    'api.invalid_expiry'     => '无效的有效期。',

    'secret.label' => '秘密',

    'create.noscript'           => '需要启用 JavaScript：秘密会在发送前于您的浏览器中加密。',
    'create.title'              => '分享秘密',
    'create.intro'              => '创建一个只能打开一次的链接。一旦被查看或过期，秘密将被永久删除。',
    'create.secret_placeholder' => '密码、API 密钥、私密笔记…',
    'create.secret_required'    => '请输入要分享的秘密。',
    'create.expiry_label'       => '有效期',
    'create.expiry.hour'        => '1 小时',
    'create.expiry.day'         => '1 天',
    'create.expiry.week'        => '1 周',
    'create.submit'             => '创建秘密链接',
    'create.zero_knowledge'     => '您的秘密在发送前已在浏览器中加密，服务器永远无法看到明文。',

    'created.title'      => '秘密链接已创建',
    'created.intro'      => '将此链接发送给接收者。它只能使用<strong>一次</strong>，并将在<span id="created-expiry"></span>后过期。',
    'created.link_label' => '秘密链接',
    'created.copy'       => '复制链接',
    'created.key_hint'   => '<code>#</code> 之后的部分是解密密钥，它永远不会发送到服务器，因此请分享完整链接。请勿自己打开，否则秘密将被用掉。',
    'created.another'    => '再创建一个秘密',

    'not_authorised.title'   => '未授权',
    'not_authorised.message' => '此服务器限制了秘密的创建。如果有人与您分享了秘密链接，请直接打开该链接。',

    'not_found.title'   => '秘密不可用',
    'not_found.message' => '此秘密已被查看、已过期或从未存在。秘密只能打开一次——请让发送者重新分享一个。',
    'not_found.create'  => '创建秘密',

    'reveal.title'       => '有人与您分享了一个秘密',
    'reveal.intro'       => '此秘密<strong>只能查看一次</strong>。一旦显示，它就会从服务器上永久删除，请确保您已准备好复制或保存。',
    'reveal.expires'     => '若未打开，将在{{REMAINING}}后过期。',
    'reveal.key_missing' => '此链接不完整：解密密钥（<code>#</code> 之后的部分）缺失或已损坏。秘密<strong>尚未</strong>被打开。请向发送者索取完整链接。',
    'reveal.button'      => '显示秘密',
    'reveal.deleted'     => '此秘密已从服务器删除。请立即复制——它不会再次显示。',
    'reveal.decrypted'   => '已在您的浏览器中解密，服务器从未见过明文。',
    'reveal.copy'        => '复制秘密',

    'js.insecure_context'        => '加密功能不可用，因为此页面未通过 HTTPS 提供。浏览器仅在安全来源上提供 Web Crypto API。',
    'js.insecure_context_reveal' => '加密功能不可用，因为此页面未通过 HTTPS 提供。浏览器仅在安全来源上提供 Web Crypto API。秘密尚未被打开。',
    'js.copied'                  => '已复制！',
    'js.copy_manually'           => '按 Ctrl+C 复制',
    'js.encrypting'              => '正在加密…',
    'js.decrypting'              => '正在解密…',
    'js.request_failed'          => '请求失败（{{STATUS}}），请重试。',
    'js.generic_error'           => '出了点问题，请重试。',
    'js.network_error'           => '无法连接到服务器。秘密尚未被打开，请重试。',
    'js.decrypt_failed'          => '无法解密此秘密：此链接中的密钥不匹配。秘密已被删除，请让发送者重新创建一个。',
];
