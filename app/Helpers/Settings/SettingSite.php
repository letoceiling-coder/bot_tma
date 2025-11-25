<?php


namespace App\Helpers\Settings;




use App\Http\Resources\UserResource;
use GuzzleHttp\Client;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class SettingSite
{
    const TIME_CACHE = 60 * 60 * 24;

    /**
     * @var mixed|null
     */
    public $accessToken;
    public ?string $ip;
    /**
     * @var UserResource|Authenticatable|null
     */
    public UserResource|null|Authenticatable $user;

    /**
     * @var mixed
     */


    public function __construct()
    {

        $this->ip = $this->getIp();
        $this->accessToken = $_COOKIE['accessToken'] ?? null;

        $this->user = $this->getUser();

    }


    public function getIp()
    {

        if (App::environment('local')) {
            return config('dev.default-ip');
        } else {
            return request()->ip();

        }
    }


    public function settings(): array
    {


        return [
            'ip' => $this->ip,
            'host' => env('APP_URL'),
            'accessToken' => $this->accessToken,
            'user' => $this->user,

        ];
    }


    public function getUser(): UserResource|Authenticatable|null
    {

        if (auth()->user()) {
            if (!$this->accessToken) {
                $authToken = 'authToken';
                $this->accessToken = auth()->user()->createToken($authToken)->accessToken;
            }

            return new UserResource(auth()->user());

        } elseif ($this->accessToken) {


            $url = asset('/api/v1/user');

            $client = new Client();
            try {
                $response = $client->request('GET', $url, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->accessToken,
                    ],
                ]);
                $user = json_decode($response->getBody())->user ?? null;
                if ($user) {
                    Auth::loginUsingId($user->id);

                    return new UserResource($user);
                }


            } catch (GuzzleException $e) {
                setcookie('accessToken', '', time() - 3600);
                unset($_COOKIE['accessToken']);
                $this->accessToken = '';
                Log::error('Failed to load user by access token', [
                    'exception' => $e,
                ]);
            }

        }
        if (auth()->user() && !auth()->user()->token()) {
            Auth::logout();

        }
        return null;
    }


}
