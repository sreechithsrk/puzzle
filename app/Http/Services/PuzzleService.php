<?php

namespace App\Http\Services;

use App\Models\Score;
use App\Models\Students;
use App\Models\WordSubmissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PuzzleService
{
    /**
     * Generates a shuffled puzzle string that includes a valid word and random additional letters.
     *
     * @param string $validWord
     * @param int $length
     * @return string
     */
    public function generatePuzzleString(string $validWord = 'up', int $length = 12): string
    {
        $additionalLetters = $this->generateRandomLetters($length);
        return str_shuffle($validWord . $additionalLetters);
    }

    private function generateRandomLetters(int $length = 12): string
    {
        $letters = 'abcdefghijklmnoqrstvwxyz';
        return substr(str_shuffle($letters), 0, $length);
    }

    /**
     * Checks if the given word is valid by an external dictionary API.
     *
     * @param string $word
     * @return bool
     */
    public function isValidWord(string $word): bool
    {
        if (strlen($word) < 2) {
            return false;
        }
        $response = Http::get("https://api.dictionaryapi.dev/api/v2/entries/en/{$word}");

        return $response->successful();
    }

    /**
     * Checks if a word can be formed from a given string of letters.
     * Returns an array with a boolean indicating success and the remaining letters.
     *
     * @param string $string
     * @param string|null $word
     * @return array
     */
    public function canFormWordFromString(string $string, ?string $word = ''): array
    {
        if (!$word) {
            return [false, $string];
        }
        $letterCount = $this->countLetters($string);
        $wordCount = $this->countLetters($word);

        foreach ($wordCount as $letter => $count) {
            if (!isset($letterCount[$letter]) || $letterCount[$letter] < $count) {
                return [false, $string];
            }
        }
        $remainingLetters = $this->removeUsedLetters($string, $wordCount);

        return [true, $remainingLetters];
    }

    /**
     * Counts the letter in a string.
     *
     * @param string $string
     * @return array
     */
    private function countLetters(string $string): array
    {
        return array_count_values(str_split($string));
    }

    /**
     * Removes the letters used in the word from the given string.
     *
     * @param string $letters
     * @param array $wordCount
     * @return string
     */
    private function removeUsedLetters(string $letters, array $wordCount): string
    {
        $lettersArray = str_split($letters);

        foreach ($wordCount as $letter => $count) {
            for ($i = 0; $i < $count; $i++) {
                $index = array_search($letter, $lettersArray);
                if ($index !== false) {
                    unset($lettersArray[$index]);
                }
            }
        }

        return implode('', $lettersArray);
    }

    /**
     * Generates valid word combinations from a string.
     *
     * @param string $string
     * @param bool $isReturnFirstValidWordOnly
     * @return array
     */
    public function generateCombinations(string $string, bool $isReturnFirstValidWordOnly = false): array
    {
        $words = [];

        while (strlen($string) > 0) {
            $len = strlen($string);
            $totalCombinations = 1 << $len; // 2^len

            for ($i = 1; $i < $totalCombinations; $i++) {
                $combination = '';

                for ($j = 0; $j < $len; $j++) {
                    if ($i & (1 << $j)) {
                        $combination .= $string[$j];
                    }
                }
                $isValidWord = $this->isValidWord($combination);

                if ($isValidWord) {
                    $words[] = $combination;
                    $wordCount = $this->countLetters($combination);
                    $string = $this->removeUsedLetters($string, $wordCount);

                    break;
                }
            }

            if (empty($combination) || !$isValidWord || ($isReturnFirstValidWordOnly && !empty($words))) {
                break;
            }
        }

        return $words;
    }

    public function storePuzzleScoreProcess(Request $request, string $remainingString = ''): array
    {
        $student = null;
        $score = null;

        DB::transaction(function () use ($request, $remainingString, &$student, &$score) {
            $word = $request->get('word');
            $string = $request->get('string');
            $name = $request->get('name');
            $studentId = $request->get('student_id');
            $wordScore = strlen($word);

            if (!$studentId) {
                $student = Students::create(['name' => $name]);
                $score = Score::create([
                    'student_id' => $student->id,
                    'string' => $string,
                ]);
            } else {
                $student = Students::findOrFail($studentId);
                $score = Score::whereStudentId($student->id)->first();
            }
            $isWordExist = WordSubmissions::whereStudentId($student->id)->whereWord($word)->exists();
            $score->total_score += $isWordExist ? 0 : $wordScore;
            $score->remaining_string = $remainingString;
            $score->save();

            if ($word) {
                WordSubmissions::create([
                    'student_id' => $student->id,
                    'word' => $word,
                    'score' => $isWordExist ? 0 : $wordScore
                ]);
            }
        });

        return [$student, $score];
    }

    /**
     * Process puzzle submission and return appropriate response.
     *
     * @param Request $request
     * @param bool $isEnd
     * @return JsonResponse
     */
    public function processPuzzleSubmission(Request $request, bool $isEnd): JsonResponse
    {
        $word = $request->get('word', '');
        $string = $request->get('string');

        if ($word && !$this->isValidWord($word)) {
            return $this->jsonErrorResponse('Invalid word, please try another');
        }
        [$canFormWord, $remainingString] = $this->canFormWordFromString($string, $word);

        if ($word && !$canFormWord) {
            return $this->jsonErrorResponse('Unable to form the word from the provided letters');
        }
        [$student, $score] = $this->storePuzzleScoreProcess($request, $remainingString);

        if ($isEnd) {
            $wordsFromRemainingString = $this->generateCombinations($remainingString);
            return $this->jsonEndPuzzleResponse($wordsFromRemainingString, $score->total_score, $student->id);
        }
        $wordFromRemainingString = $this->generateCombinations($remainingString, true);

        if (empty($wordFromRemainingString)) {
            return $this->jsonEndPuzzleResponse([], $score->total_score, $student->id, true);
        }

        return response()->json([
            'isComplete' => false,
            'studentId' => $student->id,
            'remainingString' => $remainingString,
            'message' => 'It\'s a valid word. Find and type the next word'
        ]);
    }

    /**
     * Generate a JSON error response.
     *
     * @param string $message
     * @return JsonResponse
     */
    private function jsonErrorResponse(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 422);
    }

    /**
     * Generate a JSON response for the end of a puzzle.
     *
     * @param array $remainingWords
     * @param int $totalScore
     * @param int $studentId
     * @param bool $isComplete
     * @return JsonResponse
     */
    private function jsonEndPuzzleResponse(array $remainingWords, int $totalScore, int $studentId, bool $isComplete = false): JsonResponse
    {
        return response()->json([
            'remainingWords' => implode(', ', $remainingWords),
            'isComplete' => $isComplete,
            'isEnd' => true,
            'totalScore' => $totalScore,
            'studentId' => $studentId,
            'message' => 'Puzzle completed successfully'
        ]);
    }
}
