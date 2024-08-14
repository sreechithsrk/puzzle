<?php

use App\Http\Controllers\PuzzleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

//Route::get('/', function () {
//    return view('puzzle');
//});


Route::get('/', [PuzzleController::class, 'startPuzzle'])->name('startPuzzle');
Route::post('/submit-word', [PuzzleController::class, 'submitWord'])->name('submitWord');
Route::post('/end-puzzle', [PuzzleController::class, 'endPuzzle'])->name('endPuzzle');
Route::get('/top-scorers', [PuzzleController::class, 'topScorers'])->name('topScorers');
