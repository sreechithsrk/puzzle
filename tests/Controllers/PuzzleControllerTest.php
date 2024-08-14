<?php

namespace Tests\Controllers;

use App\Http\Services\PuzzleService;
use App\Models\Score;
use App\Models\Students;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class PuzzleControllerTest extends TestCase
{
    use RefreshDatabase;

    private $puzzleServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->puzzleServiceMock = Mockery::mock(PuzzleService::class);
        $this->app->instance(PuzzleService::class, $this->puzzleServiceMock);
    }

    public function testStartPuzzle()
    {
        $puzzleString = 'example';
        $this->puzzleServiceMock->shouldReceive('generatePuzzleString')
            ->once()
            ->andReturn($puzzleString);

        $response = $this->get(route('startPuzzle'));

        $response->assertStatus(200);
        $response->assertViewIs('puzzle.puzzle');
        $response->assertViewHas('puzzleString', $puzzleString);
    }

    public function testSubmitWord()
    {
        $mockRequest = Request::create(route('submitWord'), 'POST', [
            'word' => 'example'
        ]);

        $this->puzzleServiceMock->shouldReceive('processPuzzleSubmission')
            ->once()
            ->with(Mockery::on(function($req) use ($mockRequest) {
                return $req->all() === $mockRequest->all();
            }), false)
            ->andReturn(response()->json(['status' => 'success']));

        $response = $this->post(route('submitWord'), $mockRequest->all());

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    public function testEndPuzzle()
    {
        $mockRequest = Request::create(route('endPuzzle'), 'POST', [
            'word' => 'example'
        ]);

        $this->puzzleServiceMock->shouldReceive('processPuzzleSubmission')
            ->once()
            ->with(Mockery::on(function($req) use ($mockRequest) {
                return $req->all() === $mockRequest->all();
            }), true)
            ->andReturn(response()->json(['status' => 'success']));

        $response = $this->post(route('endPuzzle'), $mockRequest->all());

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }


    public function testTopScorers()
    {
        $student = Students::factory()->create();
        Score::factory()->create([
            'student_id' => $student->id,
            'total_score' => 100
        ]);

        $response = $this->get(route('topScorers'));

        $response->assertStatus(200);
        $response->assertViewIs('puzzle.topScore');
        $response->assertViewHas('topScorers');
        $this->assertEquals(1, $response->viewData('topScorers')->count());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
