<?php

use App\Http\Controllers\YoutubeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('youtube', [YoutubeController::class, 'index'])->name('youtube');
Route::get('youtube/auth', [YoutubeController::class, 'googleAuth'])->name('youtube.auth');
Route::get('youtube/video-details', [YoutubeController::class, 'getVideoDetails'])->name('youtube.video.details');
Route::get('youtube/add', [YoutubeController::class, 'create'])->name('youtube.add');
Route::post('youtube/store', [YoutubeController::class, 'store'])->name('youtube.store');
Route::get('youtube/edit', [YoutubeController::class, 'edit'])->name('youtube.edit');
Route::post('youtube/delete', [YoutubeController::class, 'delete'])->name('youtube.delete');
