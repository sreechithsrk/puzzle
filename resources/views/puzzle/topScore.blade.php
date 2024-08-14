@extends('layout')
@section('content')
    <h2>Top Scores</h2>
    <table class="table table-dark table-striped">
        <tr>
            <th>Position</th>
            <th>Name</th>
            <th>Score</th>
            <th>Words</th>
        </tr>

        @foreach($topScorers as $items)
            <tr>
                <td> {{ $loop->index + 1 }} </td>
                <td> {{ $items->student->name }} </td>
                <td> {{ $items->total_score }} </td>
                <td> {{ $items->words ?? '' }} </td>
            </tr>
        @endforeach
    </table>
@endsection

@push('scripts')
    <script>

    </script>
@endpush
