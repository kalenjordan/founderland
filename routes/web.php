<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('{any?}', function () {
    return view('app');
});

Route::get('tag/{thing}', function () {
    return view('app');
});

Route::get('tags', function () {
    return view('app');
});

Route::get('city/{thing}', function () {
    return view('app');
});

Route::get('cities', function () {
    return view('app');
});
