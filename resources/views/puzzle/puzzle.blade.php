@extends('layout')
@section('content')
    <form method="POST" action="{{ route('submitWord') }}" id="submitWord">
        @csrf
        <h2>Word Puzzle</h2><br>
        <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">Name:</label>
            <div class="col-sm-4">
                <input type="text" class="form-control" name="name" required>
                <div class="invalid-feedback">
                    Please enter a valid name.
                </div>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">Letters Available:</label>
            <div class="col-sm-4">
                <input class="form-control" type="text" name="string" value="{{ $puzzleString }}" disabled>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-2 col-form-label">Word:</label>
            <div class="col-sm-4">
                <input type="text" name="word" class="form-control" required>
                <div class="invalid-feedback">
                    Please enter a valid word.
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-dark" name="submit">Submit Word</button>
        <button type="button" class="btn btn-dark" name="end">End Puzzle</button>
        <div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255, 255, 255, 0.7); z-index: 9999;">
            <div class="d-flex justify-content-center align-items-center" style="height: 100%;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>

        <input class="form-control" type="hidden" name="string" value="{{ $puzzleString }}" required>
        <input class="form-control" type="hidden" name="student_id" value="">
    </form>
@endsection

@push('scripts')
    <script>
        $(function () {
            $('button[name="submit"]').on('click', function (e) {
                e.preventDefault();
                let studentIdInput = $('input[name="student_id"]')
                let studentId = studentIdInput.val();
                let nameInput = $('input[name="name"]')
                let name = nameInput.val();
                let wordInput = $('input[name="word"]')
                let word = wordInput.val();
                let validationFlag = false;

                if (!studentId) {
                    if (!name) {
                        nameInput.addClass('is-invalid')
                        validationFlag = true;
                    } else {
                        nameInput.removeClass('is-invalid');
                    }
                }

                if (!word) {
                    wordInput.addClass('is-invalid');
                    validationFlag = true;
                } else {
                    wordInput.removeClass('is-invalid');
                }

                if (validationFlag) {
                    return;
                }
                $('#loadingOverlay').show();

                $.ajax({
                    url: "{{ route('submitWord') }}",
                    method: "POST",
                    data: $('#submitWord').serialize(),
                    success: function (response) {
                        $('#loadingOverlay').hide();
                        studentIdInput.val(response.studentId);
                        nameInput.prop('disabled', true);
                        wordInput.val('');
                        $('input[name="string"]').val(response.remainingString);

                        if(response.isEnd) {
                            return Swal.fire({
                                icon: "success",
                                title: "Puzzle Completed Successfully.",
                                text: "Your total score is : " + response.totalScore,
                                allowOutsideClick: false,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "{{ route('topScorers') }}";
                                }
                            });
                        }
                        return Swal.fire({
                            icon: "success",
                            title: "Its a valid word",
                            text: response.message,
                        });
                    },
                    error: function (xhr) {
                        $('#loadingOverlay').hide();
                        let errors = xhr.responseJSON.message;

                        return Swal.fire({
                            icon: "error",
                            title: "Please enter a valid word",
                            text: errors,
                        });
                    }
                });
            });


            $('button[name="end"]').on('click', function (e) {
                e.preventDefault();
                let studentIdInput = $('input[name="student_id"]')
                let studentId = studentIdInput.val();
                let nameInput = $('input[name="name"]')
                let name = nameInput.val();
                let wordInput = $('input[name="word"]')
                let validationFlag = false;

                if (!studentId) {
                    if (!name) {
                        nameInput.addClass('is-invalid')
                        validationFlag = true;
                    } else {
                        nameInput.removeClass('is-invalid');
                    }
                }

                if (validationFlag) {
                    return;
                }
                $('#loadingOverlay').show();
                $.ajax({
                    url: "{{ route('endPuzzle') }}",
                    method: "POST",
                    data: $('#submitWord').serialize(),
                    success: function (response) {
                        $('#loadingOverlay').hide();
                        studentIdInput.val(response.studentId);
                        nameInput.prop('disabled', true);
                        wordInput.val('');

                        if(response.isEnd) {
                            return Swal.fire({
                                icon: "success",
                                title: "Puzzle Completed Successfully.",
                                html: "Your total score is : " + response.totalScore +`
                                    <p> Reamining Word: <b>`+response.remainingWords+`</b></p>`,
                                allowOutsideClick: false,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "{{ route('topScorers') }}";
                                }
                            });

                        }
                    },
                    error: function (xhr) {
                        $('#loadingOverlay').hide();
                        let errors = xhr.responseJSON.message;

                        return Swal.fire({
                            icon: "error",
                            title: "Please enter a valid word",
                            text: errors,
                        });
                    }
                });
            });
        });
    </script>
@endpush
