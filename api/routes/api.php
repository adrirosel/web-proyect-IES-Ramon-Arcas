<?php

declare(strict_types=1);

use App\Controllers\ArchivosController;
use App\Controllers\AuthController;
use App\Controllers\AvisosController;
use App\Controllers\ConfigController;
use App\Controllers\ContactoController;
use App\Controllers\CursosAcademicosController;
use App\Controllers\DepartamentosController;
use App\Controllers\DocumentosController;
use App\Controllers\EnlacesController;
use App\Controllers\EnsenanzasController;
use App\Controllers\EventosController;
use App\Controllers\HealthController;
use App\Controllers\MenusController;
use App\Controllers\NoticiasController;
use App\Controllers\OptativasController;
use App\Controllers\OrganizacionController;
use App\Controllers\PaginasController;
use App\Controllers\ProgramasController;
use App\Controllers\ServiciosController;
use App\Controllers\UsuariosController;
use App\Http\Router;
use App\Middleware\Middleware;

/**
 * Registro de todas las rutas /api/v1.
 * Middleware: auth / can(recurso) / csrf segun escritura admin.
 */
return static function (Router $router): void {
    $auth = new AuthController();
    $health = new HealthController();
    $config = new ConfigController();
    $noticias = new NoticiasController();
    $paginas = new PaginasController();
    $avisos = new AvisosController();
    $ensenanzas = new EnsenanzasController();
    $eventos = new EventosController();
    $programas = new ProgramasController();
    $deps = new DepartamentosController();
    $org = new OrganizacionController();
    $docs = new DocumentosController();
    $servicios = new ServiciosController();
    $contacto = new ContactoController();
    $archivos = new ArchivosController();
    $menus = new MenusController();
    $enlaces = new EnlacesController();
    $optativas = new OptativasController();
    $cursos = new CursosAcademicosController();
    $usuarios = new UsuariosController();

    $csrf = Middleware::csrf();
    $authMw = Middleware::auth();

    // Salud (tambien /health sin prefijo se registra en index)
    $router->get('/api/v1/health', $health);
    $router->get('/health', $health);

    // Auth
    $router->get('/api/v1/admin/csrf', [$auth, 'csrf']);
    $router->post('/api/v1/admin/login', [$auth, 'login']);
    $router->post('/api/v1/admin/logout', [$auth, 'logout'], [$authMw, $csrf]);
    $router->get('/api/v1/admin/me', [$auth, 'me'], [$authMw]);
    $router->patch('/api/v1/admin/me', [$auth, 'patchMe'], [$authMw, $csrf]);

    $router->get('/api/v1/admin/usuarios', [$usuarios, 'index'], [$authMw, Middleware::can('usuarios')]);
    $router->post('/api/v1/admin/usuarios', [$usuarios, 'store'], [$authMw, $csrf, Middleware::can('usuarios')]);
    $router->patch('/api/v1/admin/usuarios/{id}', [$usuarios, 'patch'], [$authMw, $csrf, Middleware::can('usuarios')]);

    // Publico
    $router->get('/api/v1/configuracion', [$config, 'publicIndex']);
    $router->get('/api/v1/menus/{clave}', [$menus, 'publicShow']);
    $router->get('/api/v1/paginas/{slug}', [$paginas, 'publicShow']);
    $router->get('/api/v1/enlaces', [$enlaces, 'publicIndex']);
    $router->get('/api/v1/noticias', [$noticias, 'publicIndex']);
    $router->get('/api/v1/noticias/{slug}', [$noticias, 'publicShow']);
    $router->get('/api/v1/avisos', [$avisos, 'publicIndex']);
    $router->get('/api/v1/avisos/{id}', [$avisos, 'publicShow']);
    $router->get('/api/v1/ensenanzas', [$ensenanzas, 'publicIndex']);
    $router->get('/api/v1/ensenanzas/{slug}', [$ensenanzas, 'publicShow']);
    $router->get('/api/v1/ensenanzas/{slug}/modulos', [$ensenanzas, 'publicModulos']);
    $router->get('/api/v1/ensenanzas/{slug}/documentos', [$ensenanzas, 'publicDocumentos']);
    $router->get('/api/v1/materias-optativas', [$optativas, 'publicMaterias']);
    $router->get('/api/v1/materias-optativas/{slug}', [$optativas, 'publicMateria']);
    $router->get('/api/v1/ofertas-optativas', [$optativas, 'publicOfertas']);
    $router->get('/api/v1/departamentos', [$deps, 'publicIndex']);
    $router->get('/api/v1/departamentos/{slug}', [$deps, 'publicShow']);
    $router->get('/api/v1/departamentos/{slug}/profesorado', [$deps, 'publicProfesorado']);
    $router->get('/api/v1/organos', [$org, 'publicOrganos']);
    $router->get('/api/v1/organos/{slug}', [$org, 'publicOrgano']);
    $router->get('/api/v1/personas', [$org, 'publicPersonas']);
    $router->get('/api/v1/programas', [$programas, 'publicIndex']);
    $router->get('/api/v1/programas/{slug}', [$programas, 'publicShow']);
    $router->get('/api/v1/eventos', [$eventos, 'publicIndex']);
    $router->get('/api/v1/eventos/{slug}', [$eventos, 'publicShow']);
    $router->get('/api/v1/cursos-academicos', [$cursos, 'publicIndex']);
    $router->get('/api/v1/cursos-academicos/{id}', [$cursos, 'publicShow']);
    $router->get('/api/v1/categorias-documentos', [$docs, 'publicCategorias']);
    $router->get('/api/v1/documentos', [$docs, 'publicIndex']);
    $router->get('/api/v1/documentos/{id}', [$docs, 'publicShow']);
    $router->get('/api/v1/servicios', [$servicios, 'publicIndex']);
    $router->get('/api/v1/servicios/{slug}', [$servicios, 'publicShow']);
    $router->post('/api/v1/contacto', [$contacto, 'store']);

    // Admin config
    $router->get('/api/v1/admin/configuracion', [$config, 'adminIndex'], [$authMw, Middleware::can('configuracion')]);
    $router->patch('/api/v1/admin/configuracion', [$config, 'adminPatch'], [$authMw, $csrf, Middleware::can('configuracion')]);

    // Helper para CRUD editorial repetitivo
    $crud = static function (string $base, object $c, string $recurso) use ($router, $authMw, $csrf): void {
        $can = Middleware::can($recurso);
        $router->get($base, [$c, 'adminIndex'], [$authMw, $can]);
        $router->get($base . '/{id}', [$c, 'adminShow'], [$authMw, $can]);
        $router->post($base, [$c, 'adminStore'], [$authMw, $csrf, $can]);
        $router->patch($base . '/{id}', [$c, 'adminPatch'], [$authMw, $csrf, $can]);
        $router->delete($base . '/{id}', [$c, 'adminDelete'], [$authMw, $csrf, $can]);
    };

    $crud('/api/v1/admin/noticias', $noticias, 'noticias');
    $crud('/api/v1/admin/paginas', $paginas, 'paginas');
    $router->put('/api/v1/admin/paginas/{id}/bloques', [$paginas, 'adminBloquesPut'], [$authMw, $csrf, Middleware::can('paginas')]);

    $crud('/api/v1/admin/avisos', $avisos, 'avisos');
    $crud('/api/v1/admin/ensenanzas', $ensenanzas, 'ensenanzas');
    $router->put('/api/v1/admin/ensenanzas/{id}/modulos', [$ensenanzas, 'adminModulosPut'], [$authMw, $csrf, Middleware::can('ensenanzas')]);
    $crud('/api/v1/admin/eventos', $eventos, 'eventos');
    $crud('/api/v1/admin/programas', $programas, 'programas');
    $router->put('/api/v1/admin/programas/{id}/departamentos', [$programas, 'adminSyncDepartamentos'], [$authMw, $csrf, Middleware::can('programas')]);
    $crud('/api/v1/admin/departamentos', $deps, 'departamentos');
    $crud('/api/v1/admin/servicios', $servicios, 'servicios');

    $router->get('/api/v1/admin/menus', [$menus, 'adminIndex'], [$authMw, Middleware::can('menus')]);
    $router->post('/api/v1/admin/menus', [$menus, 'adminStore'], [$authMw, $csrf, Middleware::can('menus')]);
    $router->put('/api/v1/admin/menus/{id}/items', [$menus, 'adminReplaceItems'], [$authMw, $csrf, Middleware::can('menus')]);

    $router->get('/api/v1/admin/enlaces', [$enlaces, 'adminIndex'], [$authMw, Middleware::can('enlaces')]);
    $router->post('/api/v1/admin/enlaces', [$enlaces, 'adminStore'], [$authMw, $csrf, Middleware::can('enlaces')]);
    $router->patch('/api/v1/admin/enlaces/{id}', [$enlaces, 'adminPatch'], [$authMw, $csrf, Middleware::can('enlaces')]);
    $router->delete('/api/v1/admin/enlaces/{id}', [$enlaces, 'adminDelete'], [$authMw, $csrf, Middleware::can('enlaces')]);

    $router->get('/api/v1/admin/cursos-academicos', [$cursos, 'adminIndex'], [$authMw, Middleware::can('cursos_academicos')]);
    $router->post('/api/v1/admin/cursos-academicos', [$cursos, 'adminStore'], [$authMw, $csrf, Middleware::can('cursos_academicos')]);
    $router->patch('/api/v1/admin/cursos-academicos/{id}', [$cursos, 'adminPatch'], [$authMw, $csrf, Middleware::can('cursos_academicos')]);

    $router->get('/api/v1/admin/materias-optativas', [$optativas, 'adminMateriasIndex'], [$authMw, Middleware::can('materias_optativas')]);
    $router->post('/api/v1/admin/materias-optativas', [$optativas, 'adminMateriasStore'], [$authMw, $csrf, Middleware::can('materias_optativas')]);
    $router->patch('/api/v1/admin/materias-optativas/{id}', [$optativas, 'adminMateriasPatch'], [$authMw, $csrf, Middleware::can('materias_optativas')]);
    $router->get('/api/v1/admin/ofertas-optativas', [$optativas, 'adminOfertasIndex'], [$authMw, Middleware::can('ofertas_optativas')]);
    $router->post('/api/v1/admin/ofertas-optativas', [$optativas, 'adminOfertasStore'], [$authMw, $csrf, Middleware::can('ofertas_optativas')]);
    $router->patch('/api/v1/admin/ofertas-optativas/{id}', [$optativas, 'adminOfertasPatch'], [$authMw, $csrf, Middleware::can('ofertas_optativas')]);
    $router->delete('/api/v1/admin/ofertas-optativas/{id}', [$optativas, 'adminOfertasDelete'], [$authMw, $csrf, Middleware::can('ofertas_optativas')]);

    $router->get('/api/v1/admin/personas', [$org, 'adminPersonasIndex'], [$authMw, Middleware::can('personas')]);
    $router->post('/api/v1/admin/personas', [$org, 'adminPersonasStore'], [$authMw, $csrf, Middleware::can('personas')]);
    $router->patch('/api/v1/admin/personas/{id}', [$org, 'adminPersonasPatch'], [$authMw, $csrf, Middleware::can('personas')]);
    $router->delete('/api/v1/admin/personas/{id}', [$org, 'adminPersonasDelete'], [$authMw, $csrf, Middleware::can('personas')]);
    $router->put('/api/v1/admin/personas/{id}/departamentos', [$org, 'adminSyncDepartamentos'], [$authMw, $csrf, Middleware::can('personas')]);

    $router->get('/api/v1/admin/organos', [$org, 'adminOrganosIndex'], [$authMw, Middleware::can('organos')]);
    $router->post('/api/v1/admin/organos', [$org, 'adminOrganosStore'], [$authMw, $csrf, Middleware::can('organos')]);
    $router->patch('/api/v1/admin/organos/{id}', [$org, 'adminOrganosPatch'], [$authMw, $csrf, Middleware::can('organos')]);
    $router->delete('/api/v1/admin/organos/{id}', [$org, 'adminOrganosDelete'], [$authMw, $csrf, Middleware::can('organos')]);
    $router->put('/api/v1/admin/organos/{id}/miembros', [$org, 'adminSyncMiembros'], [$authMw, $csrf, Middleware::can('organos')]);

    $router->get('/api/v1/admin/categorias-documentos', [$docs, 'adminCategoriasIndex'], [$authMw, Middleware::can('categorias_documentos')]);
    $router->post('/api/v1/admin/categorias-documentos', [$docs, 'adminCategoriasStore'], [$authMw, $csrf, Middleware::can('categorias_documentos')]);
    $router->patch('/api/v1/admin/categorias-documentos/{id}', [$docs, 'adminCategoriasPatch'], [$authMw, $csrf, Middleware::can('categorias_documentos')]);
    $router->get('/api/v1/admin/documentos', [$docs, 'adminIndex'], [$authMw, Middleware::can('documentos')]);
    $router->post('/api/v1/admin/documentos', [$docs, 'adminStore'], [$authMw, $csrf, Middleware::can('documentos')]);
    $router->patch('/api/v1/admin/documentos/{id}', [$docs, 'adminPatch'], [$authMw, $csrf, Middleware::can('documentos')]);
    $router->delete('/api/v1/admin/documentos/{id}', [$docs, 'adminDelete'], [$authMw, $csrf, Middleware::can('documentos')]);

    $router->get('/api/v1/admin/archivos', [$archivos, 'index'], [$authMw, Middleware::can('archivos')]);
    $router->post('/api/v1/admin/archivos', [$archivos, 'store'], [$authMw, $csrf, Middleware::can('archivos')]);
    $router->patch('/api/v1/admin/archivos/{id}', [$archivos, 'patch'], [$authMw, $csrf, Middleware::can('archivos')]);
    $router->delete('/api/v1/admin/archivos/{id}', [$archivos, 'delete'], [$authMw, $csrf, Middleware::can('archivos')]);

    $router->get('/api/v1/admin/contactos', [$contacto, 'adminIndex'], [$authMw, Middleware::can('contactos')]);
    $router->patch('/api/v1/admin/contactos/{id}', [$contacto, 'adminPatch'], [$authMw, $csrf, Middleware::can('contactos')]);
};
