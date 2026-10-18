@extends('layouts.login', [
    'panel'     => 'admin',
    'title'     => 'Acceso Admin',
    'heading'   => 'Acceso Admin',
    'intro'     => 'Ingresa tus credenciales de administrador del sistema',
    'icon'      => 'fa-shield-halved',
    'sideTitle' => 'Panel de Administración',
    'sideText'  => 'Registra las tiendas que usan el sistema y su usuario de acceso.',
    'features'  => [['fa-building', 'Empresas'], ['fa-users', 'Usuarios'], ['fa-gear', 'Sistema']],
])
