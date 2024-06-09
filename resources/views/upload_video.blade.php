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
                    Upload a Video on Youtube
                </div>
                <div class="card-body">
                    <form id='add_video' name='add_video' action="javascript:void(0)" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label>Title</label>
                                <input type="text" id="title" name="title" class="form-control" placeholder="Enter Title" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label>Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                <p id="counter" style="color: red">Words remaining: 255</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="video">Video</label>
                                <input type="file" class="form-control file-upload-info" id="video" name="video">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <button type="submit" class="btn btn-primary mr-2">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function() {
                var createVideosUrl = "{{route('youtube.store')}}";

                var validator = $('#add_video').validate({
                    ignore: [],
                    errorElement: "span",
                    errorClass: "invalid error",
                    highlight: function(e) {
                        $(e).addClass("required")
                    },
                    unhighlight: function(e) {
                        $(e).removeClass("required")
                    },
                    rules: {
                        title: {
                            required: true
                        },
                        description : {
                            required: true
                        },
                        video: {
                            required: true,
                            fileExtension: "mp4|mov|ogg|qt"
                        }
                    },
                    messages: {
                        title: {
                            required: 'Please enter title',
                        },
                        description: {
                            required: 'Please enter description',
                        },
                        video: {
                            required: 'Please select a video',
                            accept: 'Only support format are allowed'
                        }
                    },
                    invalidHandler: function() {

                    },
                    submitHandler: function(e) {
                        let formdata = new FormData($('#add_video')[0]);
                        console.log('Form data:', formdata);
                        $.ajax({
                            type: "POST",
                            url: createVideosUrl,
                            data: formdata,
                            dataType: "JSON",
                            processData: false, // Prevent jQuery from processing data
                            contentType: false,
                            beforeSend: function(xhr) {
                            },
                            complete: function() {},
                            success: function(response) {
                                if (response.status == 'success') {
                                    Swal.fire({
                                        position: 'center',
                                        icon: 'success',
                                        title: response.message,
                                        showConfirmButton: false,
                                        timer: 1000
                                    }) .then((result) => {
                                        window.location.href = response.redirect;
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Oops...',
                                        text: response.message,
                                    })
                                }
                            // console.log(response);
                            },
                            error: function(error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: response.message,
                                })
                            }
                        })
                    }
                });

                $.validator.addMethod("fileExtension", function(value, element, param) {
                    param = typeof param === "string" ? param.replace(/,/g, "|") : "mp4";
                    var regex = new RegExp("\\.(" + param + ")$", "i");
                    return this.optional(element) || regex.test(value);
                }, "Please select a file with a valid extension.");

                document.addEventListener("DOMContentLoaded", function() {
                    // Prepopulate textarea and update word count if text is already present (for edit form)
                    var textarea = document.getElementById("description");
                    var text = textarea.value.trim();
                    var characters = text.length;
                    var remainingCharacters = 255 - characters;
                    document.getElementById("counter").textContent = "Characters remaining: " + remainingCharacters;

                    // Event listener for input
                    textarea.addEventListener("input", function(event) {
                        var text = event.target.value.trim();
                        var characters = text.length;
                        var remainingCharacters = 255 - characters;
                        document.getElementById("counter").textContent = "Characters remaining: " + remainingCharacters;

                        // Restrict input to 255 characters
                        if (characters > 255) {
                            event.target.value = text.substring(0, 255);
                            remainingCharacters = 0;
                            document.getElementById("counter").textContent = "Characters remaining: " + remainingCharacters;
                        }
                    });
                });
            });
        </script>
    </body>
</html>
