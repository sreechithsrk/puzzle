<?php

namespace Tests\Unit\Services;

use App\Http\Services\PuzzleService;
use App\Models\Score;
use App\Models\Students;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PuzzleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PuzzleService $puzzleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->puzzleService = new PuzzleService();
    }

    public function test_generate_puzzle_string()
    {
        $validWord = 'up';
        $length = 12;

        $puzzleString = $this->puzzleService->generatePuzzleString($validWord, $length);

        $this->assertIsString($puzzleString);
        $this->assertEquals(14, strlen($puzzleString));
    }


    public function test_is_valid_word()
    {
        Http::fake([
            'https://api.dictionaryapi.dev/*' => Http::response([], 200),
        ]);

        $this->assertTrue($this->puzzleService->isValidWord('test'));
    }

    public function test_generate_combinations()
    {
        $string = 'abc';
        $combinations = $this->puzzleService->generateCombinations($string);

        $this->assertNotEmpty($combinations);
        $this->assertContains('ab', $combinations);
    }

    /** @test */
    public function it_returns_error_when_word_is_invalid()
    {
        // Arrange
        $invalidWord = 'invalidword';
        $string = 'upxyz';
        $request = Request::create('/puzzle', 'POST', [
            'word' => $invalidWord,
            'string' => $string,
            'name' => 'Test Student'
        ]);

        $response = $this->puzzleService->processPuzzleSubmission($request, false);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Invalid word, please try another', json_decode($response->getContent())->message);
    }

    /** @test */
    public function it_returns_error_when_word_cannot_be_formed_from_string()
    {
        $validWord = 'up';
        $string = 'xyz';
        $request = Request::create('/puzzle', 'POST', [
            'word' => $validWord,
            'string' => $string,
            'name' => 'Test Student'
        ]);

        $response = $this->puzzleService->processPuzzleSubmission($request, false);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Unable to form the word from the provided letters', json_decode($response->getContent())->message);
    }

    /** @test */
    public function it_processes_puzzle_submission_successfully()
    {
        $validWord = 'up';
        $string = 'upxyz';
        $request = Request::create('/puzzle', 'POST', [
            'word' => $validWord,
            'string' => $string,
            'name' => 'Test Student'
        ]);

        $response = $this->puzzleService->processPuzzleSubmission($request, false);

        $responseContent = $response->getContent();

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($responseContent, true);

        $this->assertArrayHasKey('isComplete', $responseData);
        $this->assertArrayHasKey('studentId', $responseData);
        $this->assertArrayHasKey('message', $responseData);

        $student = Students::first();
        $this->assertNotNull($student);
        $score = Score::where('student_id', $student->id)->first();
        $this->assertNotNull($score);
        $this->assertEquals(2, $score->total_score);
    }


    /** @test */
    public function it_returns_end_response_when_game_is_completed()
    {
        $validWord = 'up';
        $string = 'upxyz';
        $request = Request::create('/puzzle', 'POST', [
            'word' => $validWord,
            'string' => $string,
            'name' => 'Test Student'
        ]);

        $this->puzzleService->processPuzzleSubmission($request, false);
        $response = $this->puzzleService->processPuzzleSubmission($request, true);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('remainingWords', $responseData);
        $this->assertArrayHasKey('isComplete', $responseData);
        $this->assertArrayHasKey('isEnd', $responseData);
        $this->assertArrayHasKey('totalScore', $responseData);
        $this->assertArrayHasKey('studentId', $responseData);
        $this->assertArrayHasKey('message', $responseData);
    }
}
