<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Google\Client;
use Google\Service\YouTube;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Common\Services\FileUploadServices;

use App\Models\Videos;

class YoutubeController extends Controller
{
    protected $FileUploadServices;

    public function __construct(){
        $this->FileUploadServices = new FileUploadServices();
    }

    public function index(Request $request){

        // Initialize Google Client
        $client = new Client();
        if (Session::has('google_oauth_token')) {
            $client->setAccessToken(Session::get('google_oauth_token'));
            if ($client->isAccessTokenExpired()) {
                Session::forget('google_oauth_token');
            }
        } else {
            return redirect()->route('youtube.auth');
        }

        // Initialize YouTube service
        $service = new YouTube($client);

        // Fetch the authenticated user's channel ID
        $channelsResponse = $service->channels->listChannels('snippet,contentDetails', [
            'mine' => true,
        ]);

        if (empty($channelsResponse->items)) {
            return response()->json(['message' => 'No channels found for the authenticated user.']);
        }
        // dump($channelsResponse);
        $channelId = $channelsResponse->items[0]->id;

        // Fetch the list of videos from the authenticated user's channel
        $queryParams = [
            'channelId' => $channelId,
            'maxResults' => 50
        ];

        $response = $service->search->listSearch('snippet', $queryParams);
        // dump($response->items);die;
        // Pass the video list to the view
        return view('index', ['videos' => $response->items]);
    }

    public function googleAuth(Request $request){
        // print_r(config('app.youtube_api_key'));die;

        # Determines where the API server redirects the user after the user completes the authorization flow
        # This value must exactly match one of the authorized redirect URIs for the OAuth 2.0 client, which you configured in your client’s API Console Credentials page.
        $redirectUrl = 'http://localhost:90/laravel-youtube-api/youtube/auth';

        # Create an configure client
        $client = new Client();
        $client->setAuthConfig(base_path('youtube.json'));
        $client->setRedirectUri($redirectUrl);
        $client->addScope('https://www.googleapis.com/auth/youtube');

        // dd(Session::all());
        # === SCENARIO 1: PREPARE FOR AUTHORIZATION ===
        if(!$request->has('code') && !Session::has('google_oauth_token')) {
            Session::put('code_verifier',$client->getOAuth2Service()->generateCodeVerifier());
            # Get the URL to Google’s OAuth server to initiate the authentication and authorization process
            $authUrl = $client->createAuthUrl();
            $connected = false;
            return redirect($authUrl);
        }


        # === SCENARIO 2: COMPLETE AUTHORIZATION ===
        # If we have an authorization code, handle callback from Google to get and store access token
        if ($request->has('code')) {
            # Exchange the authorization code for an access token
            $token = $client->fetchAccessTokenWithAuthCode($request->input('code'), Session::get('code_verifier'));
            $client->setAccessToken($token);
            Session::put('google_oauth_token',$token);

            return redirect($redirectUrl);
        }


        # === SCENARIO 3: ALREADY AUTHORIZED ===
        # If we’ve previously been authorized, we’ll have an access token in the session
        if (Session::has('google_oauth_token')) {
            $client->setAccessToken(Session::get('google_oauth_token'));
            if ($client->isAccessTokenExpired()) {
                Session::forget('google_oauth_token');
                $connected = false;
            }
            $connected = true;
        }

        # === SCENARIO 4: TERMINATE AUTHORIZATION ===
        if(isset($_GET['disconnect'])) {
            Session::forget('google_oauth_token');
            Session::forget('code_verifier');

            return redirect($redirectUrl);
        }

        return view('youtube')->with(['connected' => $connected, 'authUrl' => $authUrl ?? null]);
    }

    public function getVideoDetails(){
        # Configs
        $apiKey = config('app.youtube_api_key');

        # Initialize YouTube API client
        $client = new Client();
        $client->setDeveloperKey($apiKey);
        $service = new YouTube($client);

        # Example query just to make sure we can connect to the API
        $response = $service->videos->listVideos('snippet', ['id' => 'lTxn2BuqyzU']);

        # Output the response to confirm it worked
        dump($response);
    }

    public function create(){
        return view('upload_video');
    }

    public function store(Request $request){
        $file_path = $this->FileUploadServices->uploadVideos($request,$module = 'youtube_video');
        DB::beginTransaction();
        // try {
            if($file_path==false){
                return json_encode(array('status'=>'error','message'=>'Something went wrong!'));
            }else{
                $arr_rules = array();
                $arr_rules['title'] = "required";
                $arr_rules['description'] = "required";

                // Check Validation
                $validator = validator::make($request->all(), $arr_rules);
                if ($validator->fails()) {
                    return json_encode(array('status' => 'error', 'message' => $validator->messages()));
                }

                $arr_data['title'] = $request->input('title');
                $arr_data['youtube_link'] = $file_path[0];
                $arr_data['description'] = $request->input('description');
                $arr_data['youtube_status'] = 'uploaded on local';

                $videos = Videos::create($arr_data);
                $id = $videos->id;

                if($videos){
                    $this->uploadVideoOnYoutube($arr_data['youtube_link'], $arr_data, $id);
                    DB::commit();
                    return json_encode(array('status'=>'success','message'=>'Video added successfully!','redirect'=>route('youtube')));
                }
            }
        // } catch (Exception $e) {
        //     Log::debug($e);
        //     DB::rollback();
        //     return json_encode(array('status'=>'error','message'=>'Something went wrong!'));
        // }
    }

    public function uploadVideoOnYoutube($videoPath, $videoDetails,$id){
        $client = new Client();
        $service = new YouTube($client);
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client; // This will handle the redirection if needed
        }

        if (Session::has('google_oauth_token')) {
            $accessToken = Session::get('google_oauth_token');
            $client->setAccessToken($accessToken);
        } else {
            # If not authorized, redirect back to index
            return redirect()->route('youtube.auth');
        }

        # New video details
        $newTitle = $videoDetails['title'];
        $newDescription = $videoDetails['title'];
        $fullVideoPath = storage_path('app/public/' . $videoPath);

        # Create a snippet with title, description, and tags
        $snippet = new YouTube\VideoSnippet();
        $snippet->setTitle($newTitle);
        $snippet->setDescription($newDescription);
        $snippet->setCategoryId('22');

        # Create a video status with privacy status
        $status = new YouTube\VideoStatus();
        $status->setPrivacyStatus('public'); // Set the video privacy status

        # Associate the snippet and status with a new video object
        $video = new YouTube\Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        # Specify the size of each chunk of data in bytes. Set a higher value for reliable connection and smaller for unstable connection.
        $chunkSizeBytes = 1 * 1024 * 1024;

        # Setting the defer flag to true tells the client to return a request which can be called with ->execute();
        $client->setDefer(true);

        # Create a request for the API's videos.insert method to create and upload the video
        $insertRequest = $service->videos->insert('status,snippet', $video);
        # Create a MediaFileUpload object for resumable uploads
        $media = new \Google\Http\MediaFileUpload(
            $client,
            $insertRequest,
            'video/*',
            null,
            true,
            $chunkSizeBytes
        );
        $media->setFileSize(filesize($fullVideoPath));

        # Read the media file and upload it chunk by chunk
        $status = false;
        $handle = fopen($fullVideoPath, "rb");
        while (!$status && !feof($handle)) {
            $chunk = fread($handle, $chunkSizeBytes);
            $status = $media->nextChunk($chunk);
        }
        fclose($handle);

        # Video has successfully been uploaded, now finalize the request
        $client->setDefer(false);

        $youtubeVideoId = $status->getId();

        # Update database record with the YouTube link
        $videoModel = Videos::find($id); // Assuming you have the ID of the video record in $videoId
        $videoModel->youtube_link = 'https://youtu.be/' . $youtubeVideoId;
        $videoModel->youtube_status = 'Uploaded';
        $videoModel->save();

        # Output the snippet details after the upload
        echo "End-----";
        // print_r($status->snippet);die;

        # Return a response, redirect, or perform any other necessary action
        return response()->json(['youtube_link' => $videoModel->youtube_link]);
    }

    public function edit(Request $request){
        # Edit details
        $videoId = 'Yjml49zWRus'; # Must be a video that belongs to the currently auth’d user
        $newTitle = 'SHORT STORY OF BITTU';

        # Set up client and service
        $client = new Client();
        $service = new YouTube($client);

        # Authorize client
        # This assumes the auth process has already happened via the code
        # available here: https://codewithsusan.com/notes/youtube-api-php-oauth-connection#the-code
        if (Session::has('google_oauth_token')) {
            $client->setAccessToken(Session::get('google_oauth_token'));
        } else {
            # If not authorized, redirect back to index
            return redirect('youtube.auth');
        }

        # Get the existing snippet details for this video pre-edit
        $response = $service->videos->listVideos(
            'snippet',
            ['id' => $videoId]
        );
        $video = $response[0];
        $snippet = $video->snippet;

        # Output the snippet details before the edits
        dump($snippet);

        # Set the edits
        $snippet->setTitle($newTitle);

        # Set the snippet
        $video->setSnippet($snippet);

        # Do the update
        $response = $service->videos->update('snippet', $video);
        dump($response->snippet);
    }

    public function delete(Request $request){

    }
}
