<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

        <title>Upload Video on Youtube</title>
        <style>
            .error {
                color: red;
            }
        </style>
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>

        {{-- SweetAlert JS --}}
        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- Include jQuery Validation plugin -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50">
        <div class="container mt-5">
            <div class="card">
                <div class="card-header">
                    YouTube Video List
                </div>
                <div class="card-body">
                    <div class="table-responsive pt-3">
                    <table class="table table-bordered" id="videoTable" class="display">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Published At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($videos as $video)
                                <tr>
                                    <td>{{ $video->snippet->title }}</td>
                                    <td>{{ $video->snippet->description }}</td>
                                    <td>{{ $video->snippet->publishedAt }}</td>
                                    <td>
                                        <a href="https://www.youtube.com/watch?v={{ $video->id->videoId }}" target="_blank">Watch</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
            $(document).ready(function() {
                $('#videoTable').DataTable();
            });
        </script>
    </body>
</html>
