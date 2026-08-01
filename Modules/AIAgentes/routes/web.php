<?php

use Illuminate\Support\Facades\Route;
use Modules\AIAgentes\Http\Controllers\AIAgentesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('aiagentes', AIAgentesController::class)->names('aiagentes');
});
