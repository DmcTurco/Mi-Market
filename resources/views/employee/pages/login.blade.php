@extends('layouts.login', [
    'panel'     => 'employee',
    'title'     => 'Acceso Empleado',
    'heading'   => 'Hola',
    'intro'     => 'Ingresa con el correo que te dio tu tienda',
    'icon'      => 'fa-cash-register',
    'sideTitle' => 'Portal de Empleados',
    'sideText'  => 'Registra ventas y consulta el stock del día.',
    'features'  => [['fa-cash-register', 'Ventas'], ['fa-boxes-stacked', 'Stock'], ['fa-receipt', 'Caja']],
])
