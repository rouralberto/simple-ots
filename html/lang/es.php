<?php

declare(strict_types=1);

return [
    'lang'   => 'es',
    'footer' => '— código abierto, autoalojado y cifrado de extremo a extremo.',
    'nav.language' => 'Idioma',

    'duration.days_hours'    => '{{D}} d {{H}} h',
    'duration.days'          => '{{D}} d',
    'duration.hours_minutes' => '{{H}} h {{M}} min',
    'duration.hours'         => '{{H}} h',
    'duration.minutes'       => '{{M}} min',

    'error.rate_limit.title'   => 'Demasiadas solicitudes',
    'error.rate_limit.message' => 'Has realizado demasiadas solicitudes. Espera unos minutos antes de volver a intentarlo.',
    'error.back'               => 'Volver al inicio',

    'api.method_not_allowed' => 'Método no permitido.',
    'api.rate_limit'         => 'Demasiadas solicitudes. Espera unos minutos antes de volver a intentarlo.',
    'api.secret_gone'        => 'Este secreto ya se ha visto o ha caducado.',
    'api.not_authorised'     => 'No tienes autorización para crear secretos.',
    'api.session_expired'    => 'Tu sesión ha caducado. Recarga la página e inténtalo de nuevo.',
    'api.invalid_secret'     => 'El secreto falta, es demasiado grande o no está cifrado.',
    'api.invalid_expiry'     => 'Valor de caducidad no válido.',

    'secret.label' => 'Secreto',

    'create.noscript'           => 'Se necesita JavaScript: los secretos se cifran en tu navegador antes de enviarse.',
    'create.title'              => 'Comparte un secreto',
    'create.intro'              => 'Crea un enlace que solo se puede abrir una vez. Cuando se haya visto, o cuando caduque, el secreto desaparecerá para siempre.',
    'create.secret_placeholder' => 'Contraseña, clave de API, nota privada…',
    'create.secret_required'    => 'Introduce el secreto que quieres compartir.',
    'create.expiry_label'       => 'Caduca en',
    'create.expiry.hour'        => '1 hora',
    'create.expiry.day'         => '1 día',
    'create.expiry.week'        => '1 semana',
    'create.submit'             => 'Crear enlace secreto',
    'create.zero_knowledge'     => 'Tu secreto se cifra en tu navegador antes de enviarse. El servidor nunca ve el texto original.',

    'created.title'      => 'Enlace secreto creado',
    'created.intro'      => 'Envía este enlace al destinatario. Funciona <strong>una sola vez</strong> y caduca en <span id="created-expiry"></span>.',
    'created.link_label' => 'Enlace secreto',
    'created.copy'       => 'Copiar enlace',
    'created.key_hint'   => 'La parte después de <code>#</code> es la clave de descifrado. Nunca se envía al servidor, así que comparte el enlace completo. No lo abras tú, o el secreto se consumirá.',
    'created.another'    => 'Crear otro secreto',

    'not_authorised.title'   => 'No autorizado',
    'not_authorised.message' => 'La creación de secretos está restringida en este servidor. Si alguien te ha compartido un enlace secreto, ábrelo directamente.',

    'not_found.title'   => 'Secreto no disponible',
    'not_found.message' => 'Este secreto ya se ha visto, ha caducado o nunca existió. Los secretos solo se pueden abrir una vez: pide al remitente que comparta uno nuevo.',
    'not_found.create'  => 'Crear un secreto',

    'reveal.title'       => 'Alguien ha compartido un secreto contigo',
    'reveal.intro'       => 'Este secreto solo se puede ver <strong>una vez</strong>. En cuanto lo muestres, se eliminará del servidor de forma permanente, así que asegúrate de estar listo para copiarlo o guardarlo.',
    'reveal.expires'     => 'Caduca en {{REMAINING}} si no se abre.',
    'reveal.key_missing' => 'Este enlace está incompleto: la clave de descifrado (la parte después de <code>#</code>) falta o está dañada. El secreto <strong>no</strong> se ha abierto. Pide al remitente el enlace completo.',
    'reveal.button'      => 'Mostrar secreto',
    'reveal.deleted'     => 'Este secreto se ha eliminado del servidor. Cópialo ahora: no se volverá a mostrar.',
    'reveal.decrypted'   => 'Descifrado en tu navegador. El servidor nunca vio el texto original.',
    'reveal.copy'        => 'Copiar secreto',

    'js.insecure_context'        => 'El cifrado no está disponible porque esta página no se sirve por HTTPS. Los navegadores solo ofrecen la API Web Crypto en orígenes seguros.',
    'js.insecure_context_reveal' => 'El cifrado no está disponible porque esta página no se sirve por HTTPS. Los navegadores solo ofrecen la API Web Crypto en orígenes seguros. El secreto no se ha abierto.',
    'js.copied'                  => '¡Copiado!',
    'js.copy_manually'           => 'Pulsa Ctrl+C para copiar',
    'js.encrypting'              => 'Cifrando…',
    'js.decrypting'              => 'Descifrando…',
    'js.request_failed'          => 'La solicitud ha fallado ({{STATUS}}). Inténtalo de nuevo.',
    'js.generic_error'           => 'Algo ha salido mal. Inténtalo de nuevo.',
    'js.network_error'           => 'No se ha podido conectar con el servidor. El secreto no se ha abierto; inténtalo de nuevo.',
    'js.decrypt_failed'          => 'No se ha podido descifrar el secreto: la clave de este enlace no coincide. El secreto se ha eliminado; pide al remitente que cree uno nuevo.',
];
