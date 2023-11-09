<?php
/**
 * Plugin Name: Mi Plugin de Usuarios-videos-comentarios
 * Description: Un plugin para almacenar y recuperar Usuarios-Videos-Comentarios
 * Version: 1.0
 * Author: ---
 */

// Ruta del archivo JSON
$users_data_file = plugin_dir_path(__FILE__) . 'UsersData.json';

// Registra las rutas de la REST API
add_action('rest_api_init', function () {
    register_rest_route(
        'Users/v1',
        '/register',
        array(
            'methods' => 'POST',
            'callback' => 'crear_usuario',
        )
    );

    register_rest_route(
        'Users/v1',
        '/login',
        array(
            'methods' => 'POST',
            'callback' => 'iniciar_sesion',
        )
    );

    register_rest_route(
        'Users/v1',
        '/add-video',
        array(
            'methods' => 'POST',
            'callback' => 'agregar_video',
        )
    );

    register_rest_route(
        'Users/v1',
        '/add-subscriber',
        array(
            'methods' => 'POST',
            'callback' => 'agregar_suscriptor',
        )
    );

    register_rest_route(
        'Users/v1',
        '/get-users',
        array(
            'methods' => 'GET',
            'callback' => 'obtener_lista_usuarios',
        )
    );

    // Agregar un nuevo endpoint para obtener los datos de un usuario por su ID
    register_rest_route(
        'Users/v1',
        '/get-user/(?P<id>\d+)',
        array(
            'methods' => 'GET',
            'callback' => 'obtener_usuario_por_id',
        )
    );

    // Registra la ruta para eliminar un suscriptor
    register_rest_route(
        'Users/v1',
        '/eliminar-suscriptor/(?P<UserIdObjetivo>\d+)/(?P<UserIdSolicitante>\d+)',
        array(
            'methods' => 'DELETE',
            'callback' => 'eliminar_suscriptor',
        )
    );

    register_rest_route(
        'Users/v1',
        '/add-subscription',
        array(
            'methods' => 'POST',
            'callback' => 'agregar_suscripcion',
        )
    );


    register_rest_route(
        'Users/v1',
        '/delete-subscription/(?P<UserIdObjetivo>\d+)/(?P<UserIdSolicitante>\d+)/(?P<UserIdentifierSolicitante>[a-zA-Z0-9]+)',
        array(
            'methods' => 'DELETE',
            'callback' => 'eliminar_suscripcion',
        )
    );
    // Agregar un nuevo endpoint para eliminar video(s) de la lista de videoslist de un usuario
    register_rest_route(
        'Users/v1',
        '/eliminar-video',
        array(
            'methods' => 'DELETE',
            'callback' => 'eliminar_videolist',
        )
    );

    //videos

    register_rest_route(
        'videos/v1',
        '/create-video',
        array(
            'methods' => 'POST',
            'callback' => 'crear_video',
        )
    );

    register_rest_route(
        'videos/v1',
        '/delete-video/(?P<videoid>\d+)',
        array(
            'methods' => 'DELETE',
            'callback' => 'eliminar_videoitem',
        )
    );

    register_rest_route(
        'videos/v1',
        '/add-to-views/(?P<videoid>\d+)',
        array(
            'methods' => 'POST',
            'callback' => 'agregar_a_viewslist',
        )
    );

    register_rest_route(
        'videos/v1',
        '/add-to-likes/(?P<videoid>\d+)',
        array(
            'methods' => 'POST',
            'callback' => 'agregar_a_likeslist',
        )
    );

    register_rest_route(
        'videos/v1',
        '/get-video-list',
        array(
            'methods' => 'GET',
            'callback' => 'obtener_lista_videos',
        )
    );

    register_rest_route(
        'videos/v1',
        '/get-video/(?P<videoid>\d+)',
        array(
            'methods' => 'GET',
            'callback' => 'obtener_video_por_id',
        )
    );

    // Registro de la ruta para agregar comentarios a un video
    register_rest_route(
        'videos/v1',
        '/add-to-comentarios/(?P<videoid>\d+)',
        array(
            'methods' => 'POST',
            'callback' => 'agregar_a_comentarioslist',
        )
    );

    // Registro de la ruta para eliminar un comentario de un video
    register_rest_route(
        'videos/v1',
        '/delete-comentario/(?P<videoid>\d+)/(?P<comentarioid>[^/]+)',
        array(
            'methods' => 'DELETE',
            'callback' => 'eliminar_comentario',
        )
    );

    // Registro de la ruta para eliminar un like de un video por su ID y el ID del usuario que dio like
    register_rest_route(
        'videos/v1',
        '/delete-like/(?P<videoid>\d+)/(?P<IdUsuarioLiked>\d+)',
        array(
            'methods' => 'DELETE',
            'callback' => 'eliminar_like',
        )
    );



    //Comentarios



    register_rest_route(
        'comentarios/v1',
        '/create-comment',
        array(
            'methods' => 'POST',
            'callback' => 'crear_comentario',
        )
    );

    register_rest_route(
        'comentarios/v1',
        '/delete-comment/(?P<commentid>\d+)',
        array(
            'methods' => 'DELETE',
            'callback' => 'eliminar_comentario',
        )
    );

    register_rest_route(
        'comentarios/v1',
        '/get-comments',
        array(
            'methods' => 'GET',
            'callback' => 'obtener_lista_comentarios',
        )
    );




    //subir y crear video


    register_rest_route('videos/v1', '/upload-and-create', array(
        'methods' => 'POST',
        'callback' => 'subir_y_crear_video',
        'permission_callback' => function () {
            return current_user_can('upload_files');
        }
    )
    );
});


// Función para crear un nuevo usuario
function crear_usuario($request)
{
    global $users_data_file;

    // Obtener los parámetros del request
    $params = $request->get_params();
    $username = sanitize_text_field($params['username']);
    $password = $params['password'];
    $mail = sanitize_email($params['mail']);
    $nickname = sanitize_text_field($params['nickname']);
    $biografia = sanitize_text_field($params['biografia']);
    $urlfoto = esc_url_raw($params['urlfoto']);

    $videolist = [];
    $suscriptoreslist = []; // Inicializar lista de suscriptores vacía         ELLOS A MI
    $suscripcioneslist = []; // Inicializar lista de suscripciones vacía       YO A ELLO

    // Generar un UserIdentifier aleatorio de 20 caracteres
    $user_identifier = wp_generate_password(120, false);

    // Cargar datos existentes o inicializar un array vacío
    $users_data = file_exists($users_data_file) ? json_decode(file_get_contents($users_data_file), true) : array();

    // Obtener el último id de usuario utilizado
    $last_user_id = 0;

    foreach ($users_data as $user) {
        if ($user["id"] > $last_user_id) {
            $last_user_id = $user["id"];
        }
    }

    // Asignar el siguiente id secuencial
    $next_user_id = $last_user_id + 1;

    // Verificar si el usuario ya existe
    if (isset($users_data[$username])) {
        return "El usuario ya existe";
    }

    // Agregar el nuevo usuario a la colección con el ID asignado
    $user_data = array(
        "id" => $next_user_id,
        "UserIdentifier" => $user_identifier,
        // Agregar el UserIdentifier
        "username" => $username,
        "password" => $password,
        "mail" => $mail,
        "nickname" => $nickname,
        "biografia" => $biografia,
        "urlfoto" => $urlfoto,
        "Videolist" => $videolist,
        "SuscriptoresList" => $suscriptoreslist,
        // Campo de suscriptores inicializado como lista vacía
        "SuscripcionesList" => $suscripcioneslist,
    );

    $users_data[] = $user_data;

    // Guardar los datos en el archivo JSON
    file_put_contents($users_data_file, json_encode($users_data, JSON_PRETTY_PRINT));

    return $next_user_id;
}


// Función para eliminar un usuario por su ID y UserIdentifier
function eliminar_usuario($request)
{
    global $users_data_file;

    // Obtener los parámetros del request
    $id = (int) $request['id'];
    $user_identifier = sanitize_text_field($request['UserIdentifier']);
    $params = $request->get_params();
    $password = $params['password'];

    // Cargar datos existentes o inicializar un array vacío
    $users_data = file_exists($users_data_file) ? json_decode(file_get_contents($users_data_file), true) : array();

    // Buscar al usuario por su ID
    foreach ($users_data as $key => $user) {
        if ($user["id"] === $id) {
            // Verificar si el UserIdentifier proporcionado coincide con el almacenado
            if ($user["UserIdentifier"] === $user_identifier) {
                // Verificar si la contraseña proporcionada coincide con la contraseña del usuario
                if ($user["password"] === $password) {
                    // Eliminar el usuario encontrado
                    unset($users_data[$key]);

                    // Reindexar el array para evitar espacios vacíos
                    $users_data = array_values($users_data);

                    // Guardar los datos actualizados en el archivo JSON
                    file_put_contents($users_data_file, json_encode($users_data, JSON_PRETTY_PRINT));

                    return "Usuario borrado con éxito";
                } else {
                    return "Clave incorrecta para borrar a este usuario";
                }
            } else {
                return "UserIdentifier incorrecto para borrar a este usuario";
            }
        }
    }

    // Si no se encuentra el usuario, devuelve un mensaje de error
    return "Usuario no encontrado";
}


// Función para iniciar sesión
function iniciar_sesion($request)
{
    global $users_data_file;

    $params = $request->get_params();
    $username = sanitize_text_field($params['username']);
    $password = $params['password'];

    // Cargar datos existentes o inicializar un array vacío
    $users_data = file_exists($users_data_file) ? json_decode(file_get_contents($users_data_file), true) : array();

    // Verificar las credenciales
    foreach ($users_data as $user) {
        if ($user["username"] === $username && $user["password"] === $password) {
            // Usuario autenticado, devuelve los datos requeridos
            $user_info = array(
                "id" => $user["id"],
                "UserIdentifier" => $user["UserIdentifier"],
                "username" => $user["username"],
                "mail" => $user["mail"],
                "nickname" => $user["nickname"],
                "urlfoto" => $user["urlfoto"]
            );

            return $user_info;
        }
    }

    // Devuelve un mensaje de error si las credenciales no son válidas
    return "Credenciales inválidas";
}


// Función para obtener los datos de uno o varios usuarios por su ID
function obtener_usuario_por_id($request)
{
    global $users_data_file;

    // Obtiene los parámetros del request
    $ids = isset($request['ids']) ? explode(',', $request['ids']) : array();

    // Cargar datos existentes o inicializar un array vacío
    $users_data = file_exists($users_data_file) ? json_decode(file_get_contents($users_data_file), true) : array();

    // Inicializa un arreglo para almacenar los datos de los usuarios encontrados
    $usuarios_encontrados = array();

    // Itera a través de los IDs de usuario proporcionados
    foreach ($ids as $id) {
        $id = (int) trim($id);

        // Buscar al usuario por su ID
        foreach ($users_data as $user) {
            if ($user["id"] === $id) {
                // Obtener los valores actuales de "SuscriptoresList" del usuario
                $suscriptores = isset($user["SuscriptoresList"]) ? $user["SuscriptoresList"] : [];
                // Obtener los valores actuales de "SuscripcionesList" del usuario
                $suscripciones = isset($user["SuscripcionesList"]) ? $user["SuscripcionesList"] : [];

                // Obtener los valores actuales de "biografía" del usuario
                $biografia = isset($user["biografia"]) ? $user["biografia"] : "";

                // Obtener los valores actuales de "fecha" del usuario
                $fecha = isset($user["fecha"]) ? $user["fecha"] : "";

                // Agregar los datos del usuario encontrado al arreglo
                $usuarios_encontrados[] = array(
                    "id" => $user["id"],
                    "nickname" => $user["nickname"],
                    "urlfoto" => $user["urlfoto"],
                    "fecha" => $fecha,
                    "biografia" => $biografia,
                    "SuscriptoresList" => $suscriptores,
                    "SuscripcionesList" => $suscripciones

                );
            }
        }
    }

    // Devuelve la lista de usuarios encontrados
    return $usuarios_encontrados;
}


// Función para agregar un video al videolist de un usuario
function agregar_video($request)
{
    global $users_data_file;

    $params = $request->get_params();
    $user_identifier = sanitize_text_field($params['UserIdentifier']);
    $video = sanitize_text_field($params['video']);

    // Cargar datos existentes o inicializar un array vacío
    $users_data = file_exists($users_data_file) ? json_decode(file_get_contents($users_data_file), true) : array();

    // Buscar al usuario por su UserIdentifier
    foreach ($users_data as &$user) {
        if ($user["UserIdentifier"] === $user_identifier) {
            // Agregar el nuevo video al videolist del usuario
            $user["Videolist"][] = $video;

            // Guardar los datos actualizados en el archivo JSON
            file_put_contents($users_data_file, json_encode($users_data, JSON_PRETTY_PRINT));

            return "Video agregado con éxito al usuario con UserIdentifier: $user_identifier";
        }
    }

    return "Usuario con UserIdentifier no encontrado";
}


// Función para eliminar video(s) de la lista de videoslist de un usuario
function eliminar_videolist($request)
{
    global $users_data_file;

    $params = $request->get_params();
    $user_identifier = sanitize_text_field($params['UserIdentifier']);
    $videos_a_eliminar = explode(',', sanitize_text_field($params['VideoId']));

    // Cargar datos existentes o inicializar un array vacío
    $users_data = file_exists($users_data_file) ? json_decode(file_get_contents($users_data_file), true) : array();

    // Buscar al usuario por su UserIdentifier
    foreach ($users_data as &$user) {
        if ($user["UserIdentifier"] === $user_identifier) {
            // Verificar si la lista de videoslist existe y no está vacía
            if (isset($user["Videolist"]) && !empty($user["Videolist"])) {
                $videoslist_actualizada = $user["Videolist"];
                foreach ($videos_a_eliminar as $video_a_eliminar) {
                    // Buscar y eliminar el video de la lista
                    $video_key = array_search($video_a_eliminar, $videoslist_actualizada);
                    if ($video_key !== false) {
                        unset($videoslist_actualizada[$video_key]);
                    }
                }

                // Reindexar el array para evitar espacios vacíos
                $user["Videolist"] = array_values($videoslist_actualizada);

                // Guardar los datos actualizados en el archivo JSON
                file_put_contents($users_data_file, json_encode($users_data, JSON_PRETTY_PRINT));

                return "Video(s) eliminado(s) con éxito de la lista de videoslist del usuario con UserIdentifier: $user_identifier";
            } else {
                return "La lista de videoslist del usuario con UserIdentifier: $user_identifier está vacía o no existe";
            }
        }
    }

    return "Usuario con UserIdentifier no encontrado";
}


// Función para agregar un suscriptor a un usuario
function agregar_suscriptor($request)
{
    global $users_data_file;

    $params = $request->get_params();
    $id_suscriptor = (int) $params['IdSuscriptor'];
    $user_identifier_suscriptor = sanitize_text_field($params['UserIdentifier']);
    $id_objetivo = (int) $params['IdObjetivo'];

    // Cargar datos existentes o inicializar un array vacío
    $users_data = file_exists($users_data_file) ? json_decode(file_get_contents($users_data_file), true) : array();

    // Buscar al usuario objetivo por su ID
    foreach ($users_data as &$user) {
        if ($user["id"] === $id_objetivo) {
            // Buscar al usuario suscriptor por su ID
            foreach ($users_data as &$suscriptor) {
                if ($suscriptor["id"] === $id_suscriptor) {
                    // Verificar si el UserIdentifier proporcionado coincide con el almacenado para el suscriptor
                    if ($suscriptor["UserIdentifier"] === $user_identifier_suscriptor) {
                        // Agregar el ID del suscriptor a la lista de suscriptores del usuario objetivo
                        $user["SuscriptoresList"][] = $id_suscriptor;

                        // Guardar los datos actualizados en el archivo JSON
                        file_put_contents($users_data_file, json_encode($users_data, JSON_PRETTY_PRINT));

                        return "Suscriptor ($id_suscriptor) agregado con éxito al usuario ($id_objetivo)";
                    } else {
                        return "UserIdentifier incorrecto para el suscriptor ($id_suscriptor)";
                    }
                }
            }
            return "Suscriptor con ID ($id_suscriptor) no encontrado";
        }
    }

    return "Usuario objetivo no encontrado";
}



// Función para eliminar un suscriptor de SuscriptoresList de un usuario por su ID y el ID del solicitante
function eliminar_suscriptor($request)
{
    global $users_data_file;

    $params = $request->get_params();
    $UserIdObjetivo = (int) $params['UserIdObjetivo'];
    $UserIdSolicitante = (int) $params['UserIdSolicitante'];
    $UserIdentifierSolicitante = sanitize_text_field($params['UserIdentifier']);

    // Cargar datos existentes o inicializar un array vacío
    $users_data = file_exists($users_data_file) ? json_decode(file_get_contents($users_data_file), true) : array();

    // Buscar al usuario objetivo por su ID
    foreach ($users_data as &$user) {
        if ($user["id"] === $UserIdObjetivo) {
            // Verificar si SuscriptoresList existe
            if (isset($user["SuscriptoresList"])) {
                // Buscar el ID del solicitante en la lista de suscriptores
                $solicitante_key = array_search($UserIdSolicitante, $user["SuscriptoresList"]);
                if ($solicitante_key !== false) {
                    // Buscar al solicitante por su ID
                    foreach ($users_data as &$solicitante) {
                        if ($solicitante["id"] === $UserIdSolicitante) {
                            // Verificar si el UserIdentifier proporcionado coincide con el almacenado para el solicitante
                            if ($solicitante["UserIdentifier"] === $UserIdentifierSolicitante) {
                                // Eliminar al solicitante de la lista de suscriptores
                                unset($user["SuscriptoresList"][$solicitante_key]);

                                // Reindexar el array para evitar espacios vacíos
                                $user["SuscriptoresList"] = array_values($user["SuscriptoresList"]);

                                // Guardar los datos actualizados en el archivo JSON
                                file_put_contents($users_data_file, json_encode($users_data, JSON_PRETTY_PRINT));

                                return "Suscriptor ($UserIdSolicitante) eliminado con éxito del usuario ($UserIdObjetivo)";
                            } else {
                                return "UserIdentifier incorrecto para el solicitante ($UserIdSolicitante)";
                            }
                        }
                    }
                    return "Solicitante con ID ($UserIdSolicitante) no encontrado";
                }
            }
            return "Suscriptor ($UserIdSolicitante) no encontrado en la lista de suscriptores del usuario ($UserIdObjetivo)";
        }
    }

    return "Usuario objetivo ($UserIdObjetivo) no encontrado";
}


// Función para agregar una suscripción a un usuario
function agregar_suscripcion($request)
{
    global $users_data_file;

    $params = $request->get_params();
    $id_suscripcion = (int) $params['IdSuscripcion'];
    $user_identifier_suscripcion = sanitize_text_field($params['UserIdentifier']);
    $id_objetivo = (int) $params['IdObjetivo'];

    // Cargar datos existentes o inicializar un array vacío
    $users_data = file_exists($users_data_file) ? json_decode(file_get_contents($users_data_file), true) : array();

    // Buscar al usuario objetivo por su ID
    foreach ($users_data as &$user) {
        if ($user["id"] === $id_objetivo) {
            // Buscar al usuario suscriptor por su ID
            foreach ($users_data as &$suscriptor) {
                if ($suscriptor["id"] === $id_suscripcion) {
                    // Verificar si el UserIdentifier proporcionado coincide con el almacenado para el suscriptor
                    if ($suscriptor["UserIdentifier"] === $user_identifier_suscripcion) {
                        // Agregar el ID de la suscripción a la lista de suscripciones del usuario objetivo
                        $user["SuscripcionesList"][] = $id_suscripcion;

                        // Guardar los datos actualizados en el archivo JSON
                        file_put_contents($users_data_file, json_encode($users_data, JSON_PRETTY_PRINT));

                        return "Suscripción ($id_suscripcion) agregada con éxito al usuario ($id_objetivo)";
                    } else {
                        return "UserIdentifier incorrecto para el suscriptor ($id_suscripcion)";
                    }
                }
            }
            return "Suscriptor con ID ($id_suscripcion) no encontrado";
        }
    }

    return "Usuario objetivo no encontrado";
}


// Función para eliminar una suscripción de SuscripcionesList de un usuario por su ID y el ID del solicitante
function eliminar_suscripcion($request)
{
    global $users_data_file;

    $params = $request->get_params();
    $UserIdObjetivo = (int) $params['UserIdObjetivo'];
    $UserIdSolicitante = (int) $params['UserIdSolicitante'];
    $UserIdentifierSolicitante = sanitize_text_field($params['UserIdentifier']);

    // Cargar datos existentes o inicializar un array vacío
    $users_data = file_exists($users_data_file) ? json_decode(file_get_contents($users_data_file), true) : array();

    // Buscar al usuario objetivo por su ID
    foreach ($users_data as &$user) {
        if ($user["id"] === $UserIdObjetivo) {
            // Verificar si SuscripcionesList existe
            if (isset($user["SuscripcionesList"])) {
                // Buscar el ID del solicitante en la lista de suscripciones
                $solicitante_key = array_search($UserIdSolicitante, $user["SuscripcionesList"]);
                if ($solicitante_key !== false) {
                    // Buscar al solicitante por su ID
                    foreach ($users_data as &$solicitante) {
                        if ($solicitante["id"] === $UserIdSolicitante) {
                            // Verificar si el UserIdentifier proporcionado coincide con el almacenado para el solicitante
                            if ($solicitante["UserIdentifier"] === $UserIdentifierSolicitante) {
                                // Eliminar al solicitante de la lista de suscripciones
                                unset($user["SuscripcionesList"][$solicitante_key]);

                                // Reindexar el array para evitar espacios vacíos
                                $user["SuscripcionesList"] = array_values($user["SuscripcionesList"]);

                                // Guardar los datos actualizados en el archivo JSON
                                file_put_contents($users_data_file, json_encode($users_data, JSON_PRETTY_PRINT));

                                return "Suscripción ($UserIdSolicitante) eliminada con éxito del usuario ($UserIdObjetivo)";
                            } else {
                                return "UserIdentifier incorrecto para el solicitante ($UserIdSolicitante)";
                            }
                        }
                    }
                    return "Solicitante con ID ($UserIdSolicitante) no encontrado";
                }
            }
            return "Suscripción ($UserIdSolicitante) no encontrada en la lista de suscripciones del usuario ($UserIdObjetivo)";
        }
    }

    return "Usuario objetivo ($UserIdObjetivo) no encontrado";
}





// Videos






// Función para obtener el último VideoId utilizado
function get_last_videoid()
{
    global $videos_data_file;

    // Cargar datos existentes o inicializar un array vacío
    $videos_data = file_exists($videos_data_file) ? json_decode(file_get_contents($videos_data_file), true) : array();

    // Inicializa el último VideoId en 0
    $last_videoid = 0;

    // Encuentra el máximo VideoId actual
    foreach ($videos_data as $video) {
        if ($video["VideoId"] > $last_videoid) {
            $last_videoid = $video["VideoId"];
        }
    }

    return $last_videoid;
}


// Función para crear un nuevo video
function crear_video($request)
{
    global $videos_data_file;

    $params = $request->get_params();

    // Correcto, manteniendo UserIdentifier como una cadena
    $UserIdentifier = sanitize_text_field($params['UserIdentifier']);

    $AuthorId = (int) $params['AuthorId'];
    $VideoUrl = esc_url_raw($params['VideoUrl']);
    $ThumbnailUrl = esc_url_raw($params['ThumbnailUrl']);
    $Title = sanitize_text_field($params['Title']);
    $Description = sanitize_textarea_field($params['Description']);

    // Obtiene el último VideoId utilizado
    $last_videoid = get_last_videoid();

    // Asigna el siguiente VideoId secuencial
    $next_videoid = $last_videoid + 1;

    // Crea un nuevo video con los datos proporcionados
    $new_video = array(
        "UserIdentifier" => $UserIdentifier,
        "VideoId" => $next_videoid,
        "AuthorId" => $AuthorId,
        "VideoUrl" => $VideoUrl,
        "ThumbnailUrl" => $ThumbnailUrl,
        "Title" => $Title,
        "Description" => $Description,
        "ViewsList" => array(),
        "LikesList" => array()
    );

    // Cargar datos existentes o inicializar un array vacío
    $videos_data = file_exists($videos_data_file) ? json_decode(file_get_contents($videos_data_file), true) : array();

    // Agrega el nuevo video a la colección
    $videos_data[] = $new_video;

    // Guarda los datos en el archivo JSON
    file_put_contents($videos_data_file, json_encode($videos_data, JSON_PRETTY_PRINT));

    // Después de crear el video, llama a la función agregar_video para añadir este video al videolist del usuario
    $add_video_request = new WP_REST_Request('POST', '/Users/v1/add-video');
    $add_video_request->set_body_params([
        'UserIdentifier' => $UserIdentifier,
        // El identificador del usuario
        'video' => $next_videoid // El ID del video recién creado
    ]);
    agregar_video($add_video_request); // Llama a la función directamente con los parámetros

    // Devuelve una respuesta con el ID del video creado
    return "Video creado con éxito.";
}


// Función para eliminar un video por su ID y UserIdentifier
function eliminar_videoitem($request)
{
    global $videos_data_file;

    $videoid = (int) $request['videoid'];
    $UserIdentifier = sanitize_text_field($request['UserIdentifier']); // Nuevo parámetro UserIdentifier


    // Cargar datos existentes o inicializar un array vacío
    $videos_data = file_exists($videos_data_file) ? json_decode(file_get_contents($videos_data_file), true) : array();

    // Buscar el video por su ID
    foreach ($videos_data as $key => $video) {
        if ($video["VideoId"] === $videoid) {
            // Verificar si UserIdentifier coincide con el almacenado en el video
            if ($video["UserIdentifier"] === $UserIdentifier) {
                // Eliminar el video encontrado
                unset($videos_data[$key]);

                // Reindexa el array para evitar espacios vacíos
                $videos_data = array_values($videos_data);

                // Guardar los datos actualizados en el archivo JSON
                file_put_contents($videos_data_file, json_encode($videos_data, JSON_PRETTY_PRINT));

                return "Video eliminado con éxito";
            } else {
                return "UserIdentifier incorrecto para eliminar el video";
            }
        }
    }

    // Si no se encuentra el video, devuelve un mensaje de error
    return "Video no encontrado";
}


// Función para obtener la lista de videos sin incluir UserIdentifier en la respuesta
function obtener_lista_videos($request)
{
    global $videos_data_file;

    $params = $request->get_params();
    $count = (int) $params['count']; // Cantidad de videos que se desean

    // Cargar datos existentes o inicializar un array vacío
    $videos_data = file_exists($videos_data_file) ? json_decode(file_get_contents($videos_data_file), true) : array();

    // Verificar si count es igual a 0
    if ($count === 0) {
        // Obtener los 100 videos más recientes
        $videos_data_sorted = $videos_data;
        usort($videos_data_sorted, function ($a, $b) {
            return $b['VideoId'] - $a['VideoId'];
        });
        $videos_list = array_slice($videos_data_sorted, 0, 100);
    } else {
        // Mezclar aleatoriamente el array de videos
        shuffle($videos_data);

        // Obtener la cantidad de videos deseada o todos los disponibles si hay menos
        $videos_list = array_slice($videos_data, 0, min($count, count($videos_data)));
    }

    // Crear una lista temporal de videos sin UserIdentifier en la respuesta
    $videos_list_response = array();

    foreach ($videos_list as $video) {
        // Crear una copia del video sin UserIdentifier
        $video_without_useridentifier = $video;
        unset($video_without_useridentifier['UserIdentifier']);
        $videos_list_response[] = $video_without_useridentifier;
    }

    // Devolver la lista de videos sin UserIdentifier en la respuesta
    return $videos_list_response;
}


// Función para obtener los datos de uno o varios videos por su ID
function obtener_video_por_id($request)
{
    global $videos_data_file;

    $videoids = explode(',', $request['videoid']);

    // Cargar datos existentes o inicializar un array vacío
    $videos_data = file_exists($videos_data_file) ? json_decode(file_get_contents($videos_data_file), true) : array();

    $found_videos = array();

    foreach ($videoids as $videoid) {
        $videoid = (int) $videoid;
        foreach ($videos_data as $video) {
            if ($video["VideoId"] === $videoid) {
                // Crear una copia del video sin UserIdentifier
                $video_without_useridentifier = $video;
                unset($video_without_useridentifier['UserIdentifier']);
                $found_videos[] = $video_without_useridentifier;
                break;
            }
        }
    }

    if (!empty($found_videos)) {
        return $found_videos;
    } else {
        return "Videos no encontrados";
    }
}


// Función para agregar un valor a ViewsList de un video por su ID
function agregar_a_viewslist($request)
{
    global $videos_data_file;

    $videoid = (int) $request['videoid'];
    $params = $request->get_params();
    $view_value = (int) $params['view_value'];

    // Cargar datos existentes o inicializar un array vacío
    $videos_data = file_exists($videos_data_file) ? json_decode(file_get_contents($videos_data_file), true) : array();

    // Buscar el video por su ID
    foreach ($videos_data as &$video) {
        if ($video["VideoId"] === $videoid) {
            // Verificar si ViewsList ya existe, si no, inicializarlo como un array vacío
            if (!isset($video["ViewsList"])) {
                $video["ViewsList"] = array();
            }

            // Agregar el valor al array ViewsList
            $video["ViewsList"][] = $view_value;

            // Guardar los datos actualizados en el archivo JSON
            file_put_contents($videos_data_file, json_encode($videos_data, JSON_PRETTY_PRINT));

            return "Vista agregada a ViewsList del video con ID $videoid";
        }
    }

    // Si no se encuentra el video, devuelve un mensaje de error
    return "Video no encontrado";
}


// Función para agregar valores a ComentariosList de un video por su ID
function agregar_a_comentarioslist($request)
{
    global $videos_data_file;

    $videoid = (int) $request['videoid'];
    $params = $request->get_params();
    $comentarios = sanitize_text_field($params['comentarios']);

    // Cargar datos existentes o inicializar un array vacío
    $videos_data = file_exists($videos_data_file) ? json_decode(file_get_contents($videos_data_file), true) : array();

    // Buscar el video por su ID
    foreach ($videos_data as &$video) {
        if ($video["VideoId"] === $videoid) {
            // Verificar si ComentariosList ya existe, si no, inicializarlo como un array vacío
            if (!isset($video["ComentariosList"])) {
                $video["ComentariosList"] = array();
            }

            // Dividir los comentarios por comas y agregarlos a la lista de comentarios
            $comentarios_array = explode(',', $comentarios);
            foreach ($comentarios_array as $comentario) {
                $video["ComentariosList"][] = sanitize_text_field(trim($comentario));
            }

            // Guardar los datos actualizados en el archivo JSON
            file_put_contents($videos_data_file, json_encode($videos_data, JSON_PRETTY_PRINT));

            return "Comentarios agregados a ComentariosList del video";
        }
    }

    // Si no se encuentra el video, devuelve un mensaje de error
    return "Video no encontrado";
}


// Función para eliminar un comentario de ComentariosList de un video por su ID
function eliminar_comentario($request)
{
    global $videos_data_file;

    $videoid = (int) $request['videoid'];
    $params = $request->get_params();
    $comentarioid = sanitize_text_field($params['comentarioid']);

    // Cargar datos existentes o inicializar un array vacío
    $videos_data = file_exists($videos_data_file) ? json_decode(file_get_contents($videos_data_file), true) : array();

    // Buscar el video por su ID
    foreach ($videos_data as &$video) {
        if ($video["VideoId"] === $videoid) {
            // Verificar si ComentariosList existe
            if (isset($video["ComentariosList"])) {
                // Buscar el comentario en la lista de comentarios
                $comentario_key = array_search($comentarioid, $video["ComentariosList"]);
                if ($comentario_key !== false) {
                    // Eliminar el comentario encontrado
                    unset($video["ComentariosList"][$comentario_key]);

                    // Reindexa el array para evitar espacios vacíos
                    $video["ComentariosList"] = array_values($video["ComentariosList"]);

                    // Guardar los datos actualizados en el archivo JSON
                    file_put_contents($videos_data_file, json_encode($videos_data, JSON_PRETTY_PRINT));

                    return "Comentario eliminado de ComentariosList del video";
                }
            }

            return "Comentario no encontrado en ComentariosList del video";
        }
    }

    // Si no se encuentra el video, devuelve un mensaje de error
    return "Video con ID $videoid no encontrado";
}


// Función para agregar un valor a LikesList de un video por su ID
function agregar_a_likeslist($request)
{
    global $videos_data_file;

    $videoid = (int) $request['videoid'];
    $params = $request->get_params();
    $like_value = (int) $params['like_value'];

    // Cargar datos existentes o inicializar un array vacío
    $videos_data = file_exists($videos_data_file) ? json_decode(file_get_contents($videos_data_file), true) : array();

    // Buscar el video por su ID
    foreach ($videos_data as &$video) {
        if ($video["VideoId"] === $videoid) {
            // Verificar si LikesList ya existe, si no, inicializarlo como un array vacío
            if (!isset($video["LikesList"])) {
                $video["LikesList"] = array();
            }

            // Agregar el valor al array LikesList
            $video["LikesList"][] = $like_value;

            // Obtener la cantidad actual de likes
            $num_likes = count($video["LikesList"]);

            // Guardar los datos actualizados en el archivo JSON
            file_put_contents($videos_data_file, json_encode($videos_data, JSON_PRETTY_PRINT));

            // Devolver la cantidad de likes separados por comas
            return "$num_likes";
        }
    }

    // Si no se encuentra el video, devuelve un mensaje de error
    return "Video no encontrado";
}


// Función para eliminar un like de un video por su ID y el ID del usuario que dio like
function eliminar_like($request)
{
    global $videos_data_file;

    $videoid = (int) $request['videoid'];
    $UsuarioId = (int) $request['UsuarioId'];

    // Cargar datos existentes o inicializar un array vacío
    $videos_data = file_exists($videos_data_file) ? json_decode(file_get_contents($videos_data_file), true) : array();

    // Buscar el video por su ID
    foreach ($videos_data as &$video) {
        if ($video["VideoId"] === $videoid) {
            // Verificar si LikesList existe
            if (isset($video["LikesList"])) {
                // Buscar el ID del usuario en la lista de likes
                $like_key = array_search($UsuarioId, $video["LikesList"]);
                if ($like_key !== false) {
                    // Eliminar el like del usuario encontrado
                    unset($video["LikesList"][$like_key]);

                    // Obtener la cantidad actual de likes
                    $num_likes = count($video["LikesList"]);

                    // Guardar los datos actualizados en el archivo JSON
                    file_put_contents($videos_data_file, json_encode($videos_data, JSON_PRETTY_PRINT));

                    // Devolver la cantidad de likes separados por comas
                    return "$num_likes";
                }
            }

            return "Like del usuario no encontrado en el video";
        }
    }

    // Si no se encuentra el video, devuelve un mensaje de error
    return "Video con ID $videoid no encontrado";
}





// comentarios





// Función para crear un nuevo comentario
function crear_comentario($request)
{
    // Obtiene los parámetros del request
    $params = $request->get_params();

    // Valida si se proporcionaron los parámetros requeridos
    if (isset($params['UserIdentifier']) && isset($params['AuthorId']) && isset($params['Fecha']) && isset($params['Contenido'])) {
        // Correcto, manteniendo UserIdentifier como una cadena
        $UserIdentifier = sanitize_text_field($params['UserIdentifier']);

        $AuthorId = (int) $params['AuthorId'];
        $VideoId = (int) $params['VideoId'];
        $Fecha = sanitize_text_field($params['Fecha']);
        $Contenido = sanitize_textarea_field($params['Contenido']);

        // Carga los comentarios existentes o inicializa un array vacío
        $comentarios_data_file = plugin_dir_path(__FILE__) . 'ComentariosData.json';
        $comentarios_data = file_exists($comentarios_data_file) ? json_decode(file_get_contents($comentarios_data_file), true) : array();

        // Obtiene el último ID de comentario utilizado
        $last_comment_id = 0;

        foreach ($comentarios_data as $comentario) {
            if ($comentario["CommentId"] > $last_comment_id) {
                $last_comment_id = $comentario["CommentId"];
            }
        }

        // Asigna el siguiente ID de comentario secuencial
        $next_comment_id = $last_comment_id + 1;

        // Crea un nuevo comentario con los datos proporcionados
        $new_comment = array(
            "CommentId" => $next_comment_id,
            "UserIdentifier" => $UserIdentifier,
            "AuthorId" => $AuthorId,
            "VideoId" => $VideoId,
            "Fecha" => $Fecha,
            "Contenido" => $Contenido
        );

        // Agrega el nuevo comentario a la colección
        $comentarios_data[] = $new_comment;

        // Guarda los datos en el archivo JSON
        file_put_contents($comentarios_data_file, json_encode($comentarios_data, JSON_PRETTY_PRINT));

        $add_comment_request = new WP_REST_Request('POST', '/videos/v1/add-to-comentarios/' . $VideoId);
        $add_comment_request->set_body_params([
            'videoid' => $VideoId,
            // El ID del video al cual se agrega el comentario
            'comentarios' => $Contenido // El contenido del comentario
        ]);
        agregar_a_comentarioslist($add_comment_request); // Llama a la función directamente con los parámetros    

        // Devuelve una respuesta con el ID del comentario creado
        return "Comentario creado con éxito. ID del comentario: $next_comment_id";
    } else {
        // Si faltan parámetros requeridos, devuelve un mensaje de error.
        return "Faltan parámetros requeridos para crear el comentario.";
    }
}


// Función para eliminar un comentario por su ID
function eliminar_comentarioitem($request)
{
    global $comentarios_data_file;

    $params = $request->get_params();
    $commentid = (int) $params['commentid'];
    // Correcto, manteniendo UserIdentifier como una cadena
    $UserIdentifier = sanitize_text_field($params['UserIdentifier']);


    // Cargar datos existentes de comentarios o inicializar un array vacío
    $comentarios_data = file_exists($comentarios_data_file) ? json_decode(file_get_contents($comentarios_data_file), true) : array();

    // Buscar el comentario por su ID
    foreach ($comentarios_data as $key => $comentario) {
        if ($comentario["CommentId"] === $commentid) {
            // Verificar si el AuthorId del comentario coincide con el AuthorId proporcionado
            if ($comentario["UserIdentifier"] === $UserIdentifier) {
                // Eliminar el comentario encontrado
                unset($comentarios_data[$key]);

                // Reindexar el array para evitar espacios vacíos
                $comentarios_data = array_values($comentarios_data);

                // Guardar los datos actualizados en el archivo JSON
                file_put_contents($comentarios_data_file, json_encode($comentarios_data, JSON_PRETTY_PRINT));

                $videoId = $comentario['VideoId']; // Obtiene el ID del video del comentario

                // Creación correcta de la instancia WP_REST_Request para eliminar un comentario.
                $add_comment_request = new WP_REST_Request('DELETE', '/videos/v1/delete-comentario/');
                $add_comment_request->set_query_params([
                    'videoid' => $videoId,
                    'comentarioid' => $commentid
                ]);
                $result = eliminar_comentario($add_comment_request);


                return "Comentario eliminado con éxito.";
            } else {
                return "No eres el propietario de este comentario.";
            }
        }
    }

    // Si no se encuentra el comentario, devuelve un mensaje de error
    return "Comentario no encontrado.";
}


// Función para obtener la lista de comentarios por sus IDs
function obtener_lista_comentarios($request)
{
    // Obtiene los IDs de comentarios del parámetro
    $params = $request->get_params();
    $ids_comentarios = isset($params['IdsComentarios']) ? $params['IdsComentarios'] : '';

    // Verifica si se proporcionaron IDs de comentarios
    if (!empty($ids_comentarios)) {
        // Divide los IDs de comentarios por comas
        $ids_array = explode(',', $ids_comentarios);

        // Cargar datos existentes de comentarios o inicializar un array vacío
        $comentarios_data_file = plugin_dir_path(__FILE__) . 'ComentariosData.json';
        $comentarios_data = file_exists($comentarios_data_file) ? json_decode(file_get_contents($comentarios_data_file), true) : array();

        // Inicializa un arreglo para almacenar los comentarios encontrados
        $comentarios_encontrados = array();

        // Itera a través de los IDs de comentarios
        foreach ($ids_array as $id) {
            $commentid = (int) trim($id);

            // Busca el comentario por su ID
            foreach ($comentarios_data as $comentario) {
                if ($comentario["CommentId"] === $commentid) {
                    // Copia todos los campos del comentario excepto UserIdentifier
                    $comentario_sin_identifier = array_diff_key($comentario, array("UserIdentifier" => ""));

                    // Agrega el comentario encontrado al arreglo de comentarios
                    $comentarios_encontrados[] = $comentario_sin_identifier;
                }
            }
        }

        // Devuelve la lista de comentarios encontrados
        return $comentarios_encontrados;
    } else {
        // Si no se proporcionaron IDs de comentarios, devuelve un mensaje de error
        return "No se proporcionaron IDs de comentarios.";
    }
}





// subir y crear video


function handle_upload_media($files)
{
    require_once(ABSPATH . 'wp-admin/includes/admin.php');

    $file_return = wp_handle_upload($files, array('test_form' => false));

    if (isset($file_return['error']) || isset($file_return['upload_error_handler'])) {
        return array('success' => false, 'error' => $file_return['error']);
    } else {
        // El archivo se subió correctamente, devolver la URL
        return array('success' => true, 'url' => $file_return['url']);
    }
}

function subir_y_crear_video($request)
{
    // Asegurarse de que el archivo del video ha sido subido
    if (!empty($_FILES['video']['name'])) {
        $uploaded_video = handle_upload_media($_FILES['video']);
    } else {
        return new WP_Error('video_required', 'Es necesario subir un video.', array('status' => 400));
    }

    // Asegurarse de que el archivo de la miniatura ha sido subido
    if (!empty($_FILES['thumbnail']['name'])) {
        $uploaded_thumbnail = handle_upload_media($_FILES['thumbnail']);
    } else {
        return new WP_Error('thumbnail_required', 'Es necesario subir una miniatura.', array('status' => 400));
    }

    if ($uploaded_video['success'] && $uploaded_thumbnail['success']) {
        // Crear un nuevo WP_REST_Request para pasar a la función crear_video
        $crear_video_request = new WP_REST_Request('POST');
        $crear_video_request->set_body_params([
            'UserIdentifier' => $request->get_param('UserIdentifier'),
            'AuthorId' => $request->get_param('AuthorId'),
            'VideoUrl' => $uploaded_video['url'],
            'ThumbnailUrl' => $uploaded_thumbnail['url'],
            // Usar la URL de la miniatura subida
            'Title' => $request->get_param('Title'),
            'Description' => $request->get_param('Description')
        ]);

        // Llamar a la función crear_video con el nuevo request
        return crear_video($crear_video_request);
    } else {
        // Manejar los casos de fallo en la carga del video o de la miniatura
        $error_message = 'La carga ha fallado: ';
        if (!$uploaded_video['success']) {
            $error_message .= 'Video: ' . $uploaded_video['error'];
        }
        if (!$uploaded_thumbnail['success']) {
            $error_message .= ' Miniatura: ' . $uploaded_thumbnail['error'];
        }
        return new WP_Error('upload_failed', $error_message, array('status' => 500));
    }
}
