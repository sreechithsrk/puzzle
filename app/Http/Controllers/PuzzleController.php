<?php

namespace App\Http\Controllers;

use App\Http\Services\PuzzleService;
use App\Models\Score;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PuzzleController extends Controller
{
    private PuzzleService $puzzleService;

    public function __construct(PuzzleService $puzzleService)
    {
        $this->puzzleService = $puzzleService;
    }

    public function startPuzzle()
    {
        $puzzleString = $this->puzzleService->generatePuzzleString();

        return view('puzzle.puzzle', compact('puzzleString'));
    }

    public function submitWord(Request $request): JsonResponse
    {
        return $this->puzzleService->processPuzzleSubmission($request, false);
    }

    public function endPuzzle(Request $request): JsonResponse
    {
        return $this->puzzleService->processPuzzleSubmission($request, true);
    }

    public function topScorers()
    {
        $topScorers = Score::with('student')->orderByDesc('total_score')->take(10)->get();

        return view('puzzle.topScore', compact('topScorers'));
    }
}
